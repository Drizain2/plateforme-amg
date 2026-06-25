<!-- resources/js/Pages/Stock/Parts/Index.vue -->
<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3'
// import { useDebounceFn } from '@vueuse/core'
import { computed, ref, watch } from 'vue'
import PartController from '@/actions/App/Http/Controllers/Stock/PartController'
import StockMovementController from '@/actions/App/Http/Controllers/Stock/StockMovementController'
import StatCard from '@/Components/Dashboard/StatCard.vue'
import LowStockBadge from '@/Components/Stock/LowStockBadge.vue'
import PartForm from '@/Components/Stock/PartForm.vue'
import StockInvoicePanel from '@/Components/Stock/StockInvoicePanel.vue'
import StockMovementDrawer from '@/Components/Stock/StockMovementDrawer.vue'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import Select from '@/Components/UI/Select.vue'
import { useFilters } from '@/Composables/useFilters'
import { usePermission } from '@/Composables/usePermission'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { PaginatedResource, Part, Category, StockDepot, Supplier } from '@/types'

const props = defineProps<{
  parts: PaginatedResource<Part>
  categories: Pick<Category, 'id' | 'name'>[]
  suppliers: Pick<Supplier, 'id' | 'name'>[]
  filters: {
    search?: string
    category_id?: string
    critical?: string
  }
  stats: {
    purchase_value: number
    sale_value: number
    profit: number
    low_stock_count: number
  }
}>()

const { can } = usePermission()
const { applyFilter } = useFilters(PartController.index.url())
const page = usePage()
const depotActive = computed(() => page.props.auth.depotActive)
const isGlobalView = computed(() => page.props.auth.isGlobalView)

function visibleStockDepots(part: Part): StockDepot[] {
  if (isGlobalView.value || !depotActive.value) {
    return part.stock_depots ?? []
  }

  return (part.stock_depots ?? []).filter(sd => sd.depot_id === depotActive.value?.id)
}

// Filtres locaux
const search     = ref(props.filters.search ?? '')
const categoryId = ref(props.filters.category_id ?? '')
const critical   = ref(props.filters.critical === '1')

watch([search, categoryId, critical], () => {
  applyFilter({
    search:      search.value || undefined,
    category_id: categoryId.value || undefined,
    critical:    critical.value ? '1' : undefined,
  })
})

// Modal création / édition
const showModal  = ref(false)
const editingPart= ref<Part | null>(null)

function openCreate() {
  editingPart.value = null
  showModal.value   = true
}

function openEdit(part: Part) {
  editingPart.value = part
  showModal.value   = true
}

function onSaved() {
  showModal.value = false
  router.reload({ only: ['parts'] })
}

// Drawer mouvements
const showDrawer   = ref(false)
const selectedPart = ref<Part | null>(null)

function openMovements(part: Part) {
  selectedPart.value = part
  showDrawer.value   = true
}

// Ravitaillement rapide
const showRestockModal = ref(false)
const restockTarget     = ref<{ partName: string; depotName: string | null } | null>(null)
const restockForm = useForm({
  part_id:    '' as number | '',
  depot_id:   '' as number | '',
  stock_id:   '' as number | '',
  quantity:   1,
  unit_price: 0,
  note:       '',
})

function openRestock(part: Part, sd: StockDepot) {
  restockTarget.value     = { partName: part.name, depotName: sd.depot_name }
  restockForm.part_id     = part.id
  restockForm.depot_id    = sd.depot_id
  restockForm.stock_id    = sd.id
  restockForm.quantity    = 1
  restockForm.unit_price  = part.unit_price
  restockForm.note        = ''
  showRestockModal.value  = true
}

function submitRestock() {
  restockForm
    .transform(data => ({ ...data, type: 'in' }))
    .post(StockMovementController.store.url(), {
      preserveScroll: true,
      onSuccess: () => {
        showRestockModal.value = false
        router.reload({ only: ['parts'] })
      },
    })
}

// Facturation depuis le stock
const showInvoicePanel = ref(false)

// Suppression
const deletingId = ref<number | null>(null)

function confirmDelete(part: Part) {
  if (!confirm(`Supprimer "${part.name}" ?`)) {
    return
}

  deletingId.value = part.id

  router.delete(PartController.destroy.url(part.id), {
    preserveScroll: true,
    onFinish: () => deletingId.value = null,
  })
}

// Pagination
function goToPage(url: string | null) {
  if (!url) {
    return
}

  router.visit(url, { preserveScroll: true, preserveState: true })
}

// Options select
const categoryOptions = computed(() => props.categories.map(c => ({ value: c.id, label: c.name })))

const fmtXof = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(v)
</script>

<template>
  <AppLayout title="Articles">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Articles</h1>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ parts.meta.total }} article{{ parts.meta.total > 1 ? 's' : '' }} au total
          </p>
          <p v-if="!depotActive" class="text-xs text-amber-600 mt-1">
            Sélectionnez un dépôt actif (en haut) pour ravitailler ou facturer depuis le stock.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Button
            v-show="can('invoices.create') && depotActive"
            variant="secondary"
            @click="showInvoicePanel = true"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m-6 4h6m-6 4h4M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/>
            </svg>
            Facturer
          </Button>
          <Button v-show="can('stock.create')" @click="openCreate">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Ajouter un article
          </Button>
        </div>
      </div>

      <!-- Statistiques -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          label="Valeur d'achat"
          :value="fmtXof(stats.purchase_value)"
          sub="stock affiché, coût moyen pondéré"
        />
        <StatCard
          label="Valeur de vente"
          :value="fmtXof(stats.sale_value)"
          sub="stock affiché, prix de vente"
        />
        <StatCard
          label="Bénéfice potentiel"
          :value="fmtXof(stats.profit)"
          sub="si tout le stock est vendu"
          variant="success"
        />
        <StatCard
          label="Sous le seuil d'alerte"
          :value="stats.low_stock_count"
          sub="lignes de stock à ravitailler"
          :variant="stats.low_stock_count > 0 ? 'danger' : 'default'"
        />
      </div>

      <!-- Filtres -->
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <Input
            v-model="search"
            placeholder="Rechercher nom, SKU..."
          />
          <Select
            v-model="categoryId"
            :options="categoryOptions"
            placeholder="Toutes catégories"
          />
          <label class="flex items-center gap-2 cursor-pointer select-none">
            <input
              type="checkbox"
              v-model="critical"
              class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            />
            <span class="text-sm text-gray-700">Stock critique uniquement</span>
          </label>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Article</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">SKU</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Catégorie</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Stock par dépôt</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Dernier prix d'achat</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Fournisseur</th>
                <th class="px-4 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="parts.data.length === 0">
                <td colspan="8" class="px-4 py-12 text-center text-gray-400">
                  Aucun article trouvé
                </td>
              </tr>
              <tr
                v-for="part in parts.data"
                :key="part.id"
                class="hover:bg-gray-50 transition"
                :class="{ 'opacity-50': !part.is_active }"
              >
                <td class="px-4 py-3 font-medium text-gray-900">
                  {{ part.name }}
                  <Badge v-if="!part.is_active" variant="default" class="ml-2">Inactif</Badge>
                </td>
                <td class="px-4 py-3 text-gray-500 font-mono text-xs">
                  {{ part.sku ?? '—' }}
                </td>
                <td class="px-4 py-3">
                  <Badge v-if="part.category" variant="info">{{ part.category.name }}</Badge>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="px-4 py-3">
                  <div v-if="visibleStockDepots(part).length" class="space-y-1">
                    <div
                      v-for="sd in visibleStockDepots(part)"
                      :key="sd.id"
                      class="flex items-center justify-between gap-3"
                    >
                      <span class="text-xs text-gray-500">
                        <template v-if="isGlobalView">{{ sd.depot_name ?? '—' }} <span class="text-gray-400">·</span> </template>
                        <!-- <span class="text-gray-400">CMP {{ fmtXof(sd.avg_cost_price) }}</span> -->
                      </span>
                      <div class="flex items-center gap-1.5">
                        <LowStockBadge :quantity="sd.quantity" :alert-quantity="sd.alert_quantity" />
                        <Button
                          v-show="can('stock.restock')"
                          variant="ghost"
                          size="sm"
                          @click="openRestock(part, sd)"
                        >
                          Ravitailler
                        </Button>
                      </div>
                    </div>
                  </div>
                  <span v-else class="text-gray-400">Aucun stock</span>
                </td>
                <td class="px-4 py-3 text-gray-700">
                  {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(part.unit_price) }}
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">
                  {{ part.supplier?.name ?? '—' }}
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-1">
                    <Button variant="ghost" size="sm" @click="openMovements(part)">
                      Mouvements
                    </Button>
                    <Button
                      title="Modifier l'article"
                      v-show="can('stock.edit')"
                      variant="ghost"
                      size="sm"
                      @click="openEdit(part)"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                      </svg>
                    </Button>
                    <Button
                      title="Supprimer l'article"
                      v-show="can('stock.delete')"
                      variant="ghost"
                      size="sm"
                      :loading="deletingId === part.id"
                      @click="confirmDelete(part)"
                      class="text-red-500 hover:text-red-700 hover:bg-red-50"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="parts.meta.last_page > 1"
          class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600"
        >
          <span>
            {{ parts.meta.from }}–{{ parts.meta.to }} sur {{ parts.meta.total }}
          </span>
          <div class="flex gap-1">
            <Button
              variant="secondary"
              size="sm"
              :disabled="!parts.links.prev"
              @click="goToPage(parts.links.prev)"
            >
              ← Précédent
            </Button>
            <Button
              variant="secondary"
              size="sm"
              :disabled="!parts.links.next"
              @click="goToPage(parts.links.next)"
            >
              Suivant →
            </Button>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal création / édition -->
    <Modal
      :show="showModal"
      :title="editingPart ? 'Modifier l\'article' : 'Ajouter un article'"
      max-width="lg"
      @close="showModal = false"
    >
      <PartForm
        :part="editingPart"
        :categories="categories"
        :suppliers="suppliers"
        @saved="onSaved"
        @cancel="showModal = false"
      />
    </Modal>

    <!-- Drawer mouvements -->
    <StockMovementDrawer
      :show="showDrawer"
      :part="selectedPart"
      @close="showDrawer = false"
    />

    <!-- Modal ravitaillement rapide -->
    <Modal
      :show="showRestockModal"
      :title="`Ravitailler — ${restockTarget?.partName ?? ''}`"
      max-width="sm"
      @close="showRestockModal = false"
    >
      <form @submit.prevent="submitRestock" class="space-y-4">
        <p class="text-sm text-gray-500">Dépôt : {{ restockTarget?.depotName ?? '—' }}</p>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Quantité reçue *</label>
          <Input v-model.number="restockForm.quantity" type="number" min="1" :error="restockForm.errors.quantity" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Prix d'achat unitaire</label>
          <Input v-model.number="restockForm.unit_price" type="number" step="0.01" min="0" :error="restockForm.errors.unit_price" />
          <p class="text-xs text-gray-400 mt-1">À ajuster si le prix fournisseur a changé.</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
          <Input v-model="restockForm.note" placeholder="Ex: livraison fournisseur" :error="restockForm.errors.note" />
        </div>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" type="button" @click="showRestockModal = false">Annuler</Button>
          <Button type="submit" :loading="restockForm.processing">Ravitailler</Button>
        </div>
      </form>
    </Modal>

    <!-- Panneau facturation depuis le stock -->
    <StockInvoicePanel
      :show="showInvoicePanel"
      @close="showInvoicePanel = false"
    />

  </AppLayout>
</template>