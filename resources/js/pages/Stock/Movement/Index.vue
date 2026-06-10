<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import StockMovementController from '@/actions/App/Http/Controllers/Stock/StockMovementController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Select from '@/Components/UI/Select.vue'
import { useFilters } from '@/Composables/useFilters'
import { useToast } from '@/Composables/useToast'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { BadgeVariant, Depot, PaginatedResource, StockMovement } from '@/types'

const props = defineProps<{
  movements: PaginatedResource<StockMovement>
  depots: Pick<Depot, 'id' | 'name'>[]
  filters: {
    depot_id?: string
    type?: string
    from?: string
    to?: string
  }
}>()

const { success, error } = useToast()
const { applyFilter } = useFilters(StockMovementController.index.url())
const page = usePage()

watch(() => page.props.flash, (flash) => {
  if (flash.success) { success(flash.success) }
  if (flash.error) { error(flash.error) }
}, { immediate: true })

const depotId = ref(props.filters.depot_id ?? '')
const type    = ref(props.filters.type ?? '')
const from    = ref(props.filters.from ?? '')
const to      = ref(props.filters.to ?? '')

watch([depotId, type, from, to], () => {
  applyFilter({
    depot_id: depotId.value || undefined,
    type:     type.value || undefined,
    from:     from.value || undefined,
    to:       to.value || undefined,
  })
})

function goToPage(url: string | null) {
  if (!url) { return }
  router.visit(url, { preserveScroll: true, preserveState: true })
}

const typeOptions = [
  { value: 'in',         label: 'Entrée' },
  { value: 'out',        label: 'Sortie' },
  { value: 'adjustment', label: 'Ajustement' },
]

const depotOptions = props.depots.map(d => ({ value: d.id, label: d.name }))

function typeBadgeVariant(type: string): BadgeVariant {
  const map: Record<string, BadgeVariant> = {
    in:           'success',
    out:          'danger',
    adjustment:   'warning',
    transfer_in:  'info',
    transfer_out: 'info',
  }
  return map[type] ?? 'default'
}

const fmt = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' })
function formatDate(iso: string) {
  return fmt.format(new Date(iso))
}
</script>

<template>
  <AppLayout title="Mouvements de stock">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Mouvements de stock</h1>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ movements.meta.total }} mouvement{{ movements.meta.total > 1 ? 's' : '' }}
          </p>
        </div>
      </div>

      <!-- Filtres -->
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <Select
            v-model="depotId"
            :options="depotOptions"
            placeholder="Tous les dépôts"
          />
          <Select
            v-model="type"
            :options="typeOptions"
            placeholder="Tous les types"
          />
          <div>
            <label class="block text-xs text-gray-500 mb-1">Du</label>
            <Input v-model="from" type="date" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Au</label>
            <Input v-model="to" type="date" />
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Date</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Type</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Pièce</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Dépôt</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Qté</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Note</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Opérateur</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="movements.data.length === 0">
                <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                  Aucun mouvement trouvé
                </td>
              </tr>
              <tr
                v-for="movement in movements.data"
                :key="movement.id"
                class="hover:bg-gray-50 transition"
              >
                <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                  {{ formatDate(movement.created_at) }}
                </td>
                <td class="px-4 py-3">
                  <Badge :variant="typeBadgeVariant(movement.type)">
                    {{ movement.type_label }}
                  </Badge>
                </td>
                <td class="px-4 py-3">
                  <div class="font-medium text-gray-900">{{ movement.stock?.part?.name ?? '—' }}</div>
                  <div v-if="movement.stock?.part?.sku" class="text-xs text-gray-400 font-mono">
                    {{ movement.stock.part.sku }}
                  </div>
                </td>
                <td class="px-4 py-3 text-gray-700">
                  <span>{{ movement.depot?.name ?? '—' }}</span>
                  <span v-if="movement.transfer_depot" class="text-gray-400">
                    → {{ movement.transfer_depot.name }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span
                    class="font-semibold"
                    :class="{
                      'text-green-600': movement.type === 'in' || movement.type === 'transfer_in',
                      'text-red-600':   movement.type === 'out' || movement.type === 'transfer_out',
                      'text-amber-600': movement.type === 'adjustment',
                    }"
                  >
                    {{ movement.type === 'out' || movement.type === 'transfer_out' ? '-' : '+' }}{{ movement.quantity }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs max-w-xs truncate">
                  {{ movement.note ?? '—' }}
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">
                  {{ movement.user?.name ?? '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="movements.meta.last_page > 1"
          class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600"
        >
          <span>{{ movements.meta.from }}–{{ movements.meta.to }} sur {{ movements.meta.total }}</span>
          <div class="flex gap-1">
            <Button
              variant="secondary"
              size="sm"
              :disabled="!movements.links.prev"
              @click="goToPage(movements.links.prev)"
            >
              ← Précédent
            </Button>
            <Button
              variant="secondary"
              size="sm"
              :disabled="!movements.links.next"
              @click="goToPage(movements.links.next)"
            >
              Suivant →
            </Button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
