<!-- resources/js/Pages/Stock/Movements/Index.vue -->
<script setup lang="ts">
import { router, usePage, Link, useForm } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import PartController from '@/actions/App/Http/Controllers/Stock/PartController'
import StockMovementController from '@/actions/App/Http/Controllers/Stock/StockMovementController'
import TicketController from '@/actions/App/Http/Controllers/Ticket/TicketController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import Select from '@/Components/UI/Select.vue'
import { useFilters } from '@/Composables/useFilters'
import { usePermission } from '@/Composables/usePermission'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { StockMovement, StockMovementType, PaginatedResource, BadgeVariant } from '@/types'

const props = defineProps<{
  movements: PaginatedResource<StockMovement>
  filters: Record<string, string>
  depots: { id: number; name: string }[]
  summary: { total_in: number; total_out: number; total_adjustments: number }
  types: { value: string; label: string }[]
}>()

const { applyFilter } = useFilters(StockMovementController.index.url())
const { can } = usePermission()
const page = usePage()

const activeDepot = computed(() => page.props.auth.depotActive)
// const isGlobalView = computed(() => page.props.auth.isGlobalView)
const activeDepotId = page.props.auth.depotActive ? String(page.props.auth.depotActive.id) : ''

// -----------------------------------------------
// Filtres
// -----------------------------------------------
const type = ref(props.filters.type ?? '')
const from = ref(props.filters.from ?? '')
const to = ref(props.filters.to ?? '')

watch([type, from, to], () => {
  applyFilter({
    type: type.value || undefined,
    from: from.value || undefined,
    to: to.value || undefined,
  })
})

function resetFilters() {
  type.value = ''
  from.value = ''
  to.value = ''
}

const typeOptions = computed(() =>
  props.types.map(t => ({ value: t.value, label: t.label }))
)
const depotOptions = computed(() =>
  props.depots.map(d => ({ value: d.id, label: d.name }))
)

// -----------------------------------------------
// Modal mouvement manuel
// -----------------------------------------------
const showModal = ref(false)

const movementForm = useForm({
  part_id: '',
  depot_id: activeDepotId,
  type: 'in' as 'in' | 'out' | 'adjustment',
  quantity: 1,
  note: '',
})

// Recherche pièce autocomplete
const partSearch = ref('')
const foundParts = ref<{ id: number; name: string; sku?: string; quantity: number; depot_id: number }[]>([])
const searchingPart = ref(false)

async function searchParts() {
  if (partSearch.value.length < 2) {
    foundParts.value = [];

    return
  }

  searchingPart.value = true
  const res = await fetch(PartController.search.url({ query: { q: partSearch.value } }), {
    headers: { Accept: 'application/json' }
  })
  foundParts.value = await res.json()
  searchingPart.value = false
}

function selectPart(p: typeof foundParts.value[0]) {
  movementForm.part_id = String(p.id)
  movementForm.depot_id = p.depot_id ? String(p.depot_id) : activeDepotId
  partSearch.value = `${p.name}${p.sku ? ` (${p.sku})` : ''}`
  foundParts.value = []
}

function clearPart() {
  movementForm.part_id = ''
  partSearch.value = ''
}

const manualTypes = computed(() =>
  props.types
    .filter(t => ['in', 'out', 'adjustment'].includes(t.value))
    .map(t => ({ value: t.value, label: t.label }))
)

function submitMovement() {
  movementForm.post(StockMovementController.store.url(), {
    preserveScroll: true,
    onSuccess: () => {
      showModal.value = false
      movementForm.reset()
      partSearch.value = ''
    },
    onError: (errors) => {
      console.log(errors)
    }
  })
}

// -----------------------------------------------
// Modal transfert
// -----------------------------------------------
const showTransferModal = ref(false)

const transferForm = useForm({
  part_id: '',
  from_depot_id: activeDepotId,
  to_depot_id: '',
  quantity: 1,
  note: '',
})

const transferPartSearch = ref('')
const foundTransferParts = ref<typeof foundParts.value>([])
const searchingTransfer = ref(false)

async function searchTransferParts() {
  if (transferPartSearch.value.length < 2) {
    foundTransferParts.value = [];

    return
  }

  searchingTransfer.value = true
  const params = new URLSearchParams({ q: transferPartSearch.value })

  if (transferForm.from_depot_id) {
    params.set('depot_id', transferForm.from_depot_id)
  }

  const res = await fetch(`/stock/parts/search?${params}`, {
    headers: { Accept: 'application/json' }
  })
  foundTransferParts.value = await res.json()
  searchingTransfer.value = false
}

function selectTransferPart(p: typeof foundParts.value[0]) {
  transferForm.part_id = String(p.id)
  transferForm.from_depot_id = String(p.depot_id)
  transferPartSearch.value = `${p.name}${p.sku ? ` (${p.sku})` : ''}`
  foundTransferParts.value = []
}

const toDepotOptions = computed(() =>
  props.depots
    .filter(d => String(d.id) !== transferForm.from_depot_id)
    .map(d => ({ value: d.id, label: d.name }))
)

function submitTransfer() {
  transferForm.post(StockMovementController.transfer.url(), {
    preserveScroll: true,
    onSuccess: () => {
      showTransferModal.value = false
      transferForm.reset()
      transferPartSearch.value = ''
    },
  })
}

// -----------------------------------------------
// Helpers UI
// -----------------------------------------------
function typeVariant(type: StockMovementType) {
  return ({
    in: 'success',
    out: 'danger',
    adjustment: 'warning',
    transfer_in: 'info',
    transfer_out: 'info',
  } as Record<string, string>)[type] ?? 'default'
}

function goToPage(url: string | null) {
  if (!url) {
    return
  }

  router.visit(url, { preserveScroll: true, preserveState: true })
}

const hasActiveFilters = computed(() =>
  !!(type.value || from.value || to.value)
)

const formatDate = (d: string) => {
  if (!d) {
return "Date invalide";
} // Gère les cas null/undefined/empty

  const date = new Date(d);

  if (isNaN(date.getTime())) {
return "Date invalide";
}

  try {
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
  } catch (e) {
    console.error(e);

    // Si 'fr-FR' n'est pas supporté, on essaie avec 'fr'
    return new Intl.DateTimeFormat('fr', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
  }
};
const fmtXof = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(v)

const isDebit = (m: StockMovement) => m.type === 'out' || m.type === 'transfer_out'
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
        <div class="flex gap-2">
          <Button v-if="can('stock.transfer')" variant="secondary" @click="showTransferModal = true">
            ↔ Transfert
          </Button>
          <Button v-if="can('stock.restock') || can('stock.adjust')" @click="showModal = true">
            + Mouvement
          </Button>
        </div>
      </div>

      <!-- Summary cards -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">
          <div
            class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-lg shrink-0">
            ↑
          </div>
          <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Entrées</p>
            <p class="text-2xl font-bold text-green-600">{{ summary.total_in }}</p>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">
          <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-lg shrink-0">
            ↓
          </div>
          <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Sorties</p>
            <p class="text-2xl font-bold text-red-600">{{ summary.total_out }}</p>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">
          <div
            class="w-10 h-10 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center text-lg shrink-0">
            ⚖
          </div>
          <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Ajustements</p>
            <p class="text-2xl font-bold text-yellow-600">{{ summary.total_adjustments }}</p>
          </div>
        </div>
      </div>

      <!-- Filtres -->
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <Select v-model="type" :options="typeOptions" placeholder="Tous les types" />
          <Input v-model="from" type="date" />
          <Input v-model="to" type="date" />
        </div>
        <div v-if="hasActiveFilters" class="mt-3">
          <button @click="resetFilters" class="text-xs text-indigo-600 hover:underline">
            Réinitialiser les filtres
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Pièce</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Dépôt</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Quantité</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Coût unit.</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Référence</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Opérateur</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Note</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="movements.data.length === 0">
                <td colspan="9" class="px-4 py-12 text-center text-gray-400">
                  Aucun mouvement trouvé
                </td>
              </tr>
              <tr v-for="movement in movements.data" :key="movement.id" class="hover:bg-gray-50 transition">
                <!-- Date -->
                <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">
                  {{ formatDate(movement.created_at) }}
                </td>

                <!-- Type -->
                <td class="px-4 py-3">
                  <Badge :variant="typeVariant(movement.type) as BadgeVariant">
                    {{ movement.type_label }}
                  </Badge>
                </td>

                <!-- Pièce -->
                <td class="px-4 py-3">
                  <p class="text-gray-900 font-medium text-xs">{{ movement.stock?.part?.name }}</p>
                  <p v-if="movement.stock?.part?.sku" class="text-gray-400 font-mono text-xs">
                    {{ movement.stock.part.sku }}
                  </p>
                </td>

                <!-- Dépôt -->
                <td class="px-4 py-3 text-xs text-gray-600">
                  <span>{{ movement.depot?.name }}</span>
                  <span v-if="movement.transfer_depot" class="text-gray-400">
                    → {{ movement.transfer_depot.name }}
                  </span>
                </td>

                <!-- Quantité -->
                <td class="px-4 py-3 text-right">
                  <span class="font-bold text-sm" :class="isDebit(movement) ? 'text-red-600' : 'text-green-600'">
                    {{ isDebit(movement) ? '-' : '+' }}{{ movement.quantity }}
                  </span>
                </td>

                <!-- Coût unitaire (CMP au moment du mouvement) -->
                <td class="px-4 py-3 text-right text-xs text-gray-500">
                  {{ movement.unit_cost != null ? fmtXof(movement.unit_cost) : '—' }}
                </td>

                <!-- Ticket lié -->
                <td class="px-4 py-3">
                  <Link v-if="movement.ticket" :href="TicketController.show.url(movement.ticket.id)"
                    class="text-xs font-mono text-indigo-600 hover:underline">
                    {{ movement.ticket.reference }}
                  </Link>
                  <span v-else class="text-gray-300 text-xs">—</span>
                </td>

                <!-- Opérateur -->
                <td class="px-4 py-3 text-xs text-gray-500">
                  {{ movement.user?.name ?? 'Système' }}
                </td>

                <!-- Note -->
                <td class="px-4 py-3 text-xs text-gray-400 max-w-xs truncate">
                  {{ movement.note ?? '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="movements.meta.last_page > 1"
          class="px-4 py-3 border-t flex items-center justify-between text-sm text-gray-600">
          <span>{{ movements.meta.from }}–{{ movements.meta.to }} sur {{ movements.meta.total }}</span>
          <div class="flex gap-1">
            <Button variant="secondary" size="sm" :disabled="!movements.links.prev"
              @click="goToPage(movements.links.prev)">← Précédent</Button>
            <Button variant="secondary" size="sm" :disabled="!movements.links.next"
              @click="goToPage(movements.links.next)">Suivant →</Button>
          </div>
        </div>
      </div>

    </div>

    <!-- -----------------------------------------------
         Modal mouvement manuel
    ----------------------------------------------- -->
    <Modal :show="showModal" title="Enregistrer un mouvement" max-width="md" @close="showModal = false">
      <form @submit.prevent="submitMovement" class="space-y-4">

        <!-- Recherche pièce -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pièce *</label>
          <div class="relative">
            <Input v-model="partSearch" placeholder="Rechercher une pièce..." @input="searchParts"
              :error="movementForm.errors.part_id" />
            <div v-if="foundParts.length"
              class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg divide-y max-h-48 overflow-y-auto">
              <button v-for="p in foundParts" :key="p.id" type="button"
                class="w-full px-4 py-2.5 text-left hover:bg-gray-50 transition" @click="selectPart(p)">
                <p class="text-sm font-medium text-gray-900">{{ p.name }}</p>
                <div class="flex items-center gap-3 mt-0.5">
                  <span v-if="p.sku" class="text-xs font-mono text-gray-400">{{ p.sku }}</span>
                  <span class="text-xs text-gray-400">Stock : {{ p.quantity }}</span>
                </div>
              </button>
            </div>
          </div>
          <button v-if="movementForm.part_id" type="button" @click="clearPart"
            class="text-xs text-indigo-600 hover:underline mt-1">
            Changer de pièce
          </button>
        </div>

        <!-- Dépôt -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Dépôt</label>
          <div v-if="activeDepot" class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-700">
            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
            </svg>
            {{ activeDepot.name }}
          </div>
          <Select v-else v-model="movementForm.depot_id" :options="depotOptions" :error="movementForm.errors.depot_id" />
        </div>

        <!-- Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
          <Select v-model="movementForm.type" :options="manualTypes" :error="movementForm.errors.type" />
        </div>

        <!-- Quantité -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Quantité *</label>
          <Input v-model="movementForm.quantity" type="number" :error="movementForm.errors.quantity" />
        </div>

        <!-- Note -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
          <textarea v-model="movementForm.note" rows="2" placeholder="Motif, référence commande..."
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <Button variant="secondary" @click="showModal = false">Annuler</Button>
          <Button type="submit" :loading="movementForm.processing">Enregistrer</Button>
        </div>
      </form>
    </Modal>

    <!-- -----------------------------------------------
         Modal transfert
    ----------------------------------------------- -->
    <Modal :show="showTransferModal" title="Transfert entre dépôts" max-width="md" @close="showTransferModal = false">
      <form @submit.prevent="submitTransfer" class="space-y-4">

        <!-- Dépôt source -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Dépôt source</label>
          <div v-if="activeDepot" class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-700">
            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
            </svg>
            {{ activeDepot.name }}
          </div>
          <Select v-else v-model="transferForm.from_depot_id" :options="depotOptions" placeholder="Choisir le dépôt source"
            :error="transferForm.errors.from_depot_id" />
        </div>

        <!-- Recherche pièce -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pièce *</label>
          <div class="relative">
            <Input v-model="transferPartSearch" placeholder="Rechercher une pièce..." @input="searchTransferParts"
              :error="transferForm.errors.part_id" />
            <div v-if="foundTransferParts.length"
              class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg divide-y max-h-48 overflow-y-auto">
              <button v-for="p in foundTransferParts" :key="p.id" type="button"
                class="w-full px-4 py-2.5 text-left hover:bg-gray-50 transition" @click="selectTransferPart(p)">
                <p class="text-sm font-medium text-gray-900">{{ p.name }}</p>
                <div class="flex items-center gap-3 mt-0.5">
                  <span v-if="p.sku" class="text-xs font-mono text-gray-400">{{ p.sku }}</span>
                  <span class="text-xs text-gray-400">Stock disponible : {{ p.quantity }}</span>
                </div>
              </button>
            </div>
          </div>
        </div>

        <!-- Dépôt cible -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Dépôt cible *</label>
          <Select v-model="transferForm.to_depot_id" :options="toDepotOptions" placeholder="Choisir le dépôt cible"
            :error="transferForm.errors.to_depot_id" />
          <p v-if="!transferForm.from_depot_id" class="text-xs text-gray-400 mt-1">
            Sélectionnez d'abord le dépôt source
          </p>
        </div>

        <!-- Quantité -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Quantité *</label>
          <Input v-model="transferForm.quantity" type="number" :error="transferForm.errors.quantity" />
        </div>

        <!-- Note -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
          <textarea v-model="transferForm.note" rows="2" placeholder="Motif du transfert..."
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <Button variant="secondary" @click="showTransferModal = false">Annuler</Button>
          <Button type="submit" :loading="transferForm.processing">Transférer</Button>
        </div>
      </form>
    </Modal>

  </AppLayout>
</template>