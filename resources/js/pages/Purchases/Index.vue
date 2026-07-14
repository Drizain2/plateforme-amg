<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import PurchaseController from '@/actions/App/Http/Controllers/PurchaseController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Select from '@/Components/UI/Select.vue'
import { useFilters } from '@/Composables/useFilters'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { BadgeVariant, Purchase } from '@/types/models'
import type { PaginatedResource } from '@/types/pagination'

const props = defineProps<{
  purchases: PaginatedResource<Purchase>
  filters: Record<string, string>
  statuses: { value: string; label: string }[]
}>()

const { applyFilter } = useFilters(PurchaseController.index.url())

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')

watch([search, status], () => {
  applyFilter({
    search: search.value || undefined,
    status: status.value || undefined,
  })
})

const fmt = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(v)

function goToPage(url: string | null) {
  if (!url) {
    return
  }

  router.visit(url, { preserveScroll: true, preserveState: true })
}

const statusOptions = computed(() => props.statuses.map(s => ({ value: s.value, label: s.label })))
</script>

<template>
  <AppLayout title="Achats">
    <div class="max-w-7xl m-auto space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Achats</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ purchases.meta.total }} achat{{ purchases.meta.total > 1 ? 's' : '' }}</p>
        </div>
        <Link :href="PurchaseController.create.url()">
          <Button>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouvel achat
          </Button>
        </Link>
      </div>

      <!-- Filtres -->
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <Input v-model="search" placeholder="N° achat, fournisseur..." />
          <Select v-model="status" :options="statusOptions" placeholder="Tous statuts" />
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Numéro</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Fournisseur</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Dépôt</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Statut</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Total TTC</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Commandé le</th>
                <th class="px-4 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="purchases.data.length === 0">
                <td colspan="7" class="px-4 py-12 text-center text-gray-400">Aucun achat trouvé</td>
              </tr>
              <tr
                v-for="purchase in purchases.data"
                :key="purchase.id"
                class="hover:bg-gray-50 transition"
              >
                <td class="px-4 py-3 font-mono text-xs font-medium text-gray-900">
                  {{ purchase.number }}
                </td>
                <td class="px-4 py-3 text-gray-700">{{ purchase.supplier?.name }}</td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ purchase.depot?.name ?? '—' }}</td>
                <td class="px-4 py-3">
                  <Badge :variant="purchase.status_color as BadgeVariant">
                    {{ purchase.status_label }}
                  </Badge>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-gray-900">
                  {{ fmt(purchase.total_ttc) }}
                </td>
                <td class="px-4 py-3 text-gray-400 text-xs">{{ purchase.ordered_at ?? '—' }}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-1">
                    <Link :href="PurchaseController.show.url(purchase.id)">
                      <Button variant="ghost" size="sm">Ouvrir</Button>
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="purchases.meta.last_page > 1"
          class="px-4 py-3 border-t flex items-center justify-between text-sm text-gray-600"
        >
          <span>{{ purchases.meta.from }}–{{ purchases.meta.to }} sur {{ purchases.meta.total }}</span>
          <div class="flex gap-1">
            <Button variant="secondary" size="sm" :disabled="!purchases.links.prev" @click="goToPage(purchases.links.prev)">← Précédent</Button>
            <Button variant="secondary" size="sm" :disabled="!purchases.links.next" @click="goToPage(purchases.links.next)">Suivant →</Button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
