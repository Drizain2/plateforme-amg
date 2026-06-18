<!-- resources/js/Pages/Stock/Parts/Index.vue -->
<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
// import { useDebounceFn } from '@vueuse/core'
import { computed, ref, watch } from 'vue'
import PartController from '@/actions/App/Http/Controllers/Stock/PartController'
import LowStockBadge from '@/Components/Stock/LowStockBadge.vue'
import PartForm from '@/Components/Stock/PartForm.vue'
import StockMovementDrawer from '@/Components/Stock/StockMovementDrawer.vue'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import Select from '@/Components/UI/Select.vue'
import { useFilters } from '@/Composables/useFilters'
import { usePermission } from '@/Composables/usePermission'
import { useToast } from '@/Composables/useToast'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { PaginatedResource, Part, Category } from '@/types'

const props = defineProps<{
  parts: PaginatedResource<Part>
  categories: Pick<Category, 'id' | 'name'>[]
  filters: {
    search?: string
    category_id?: string
    critical?: string
  }
}>()

const { success, error } = useToast()
const { can } = usePermission()
const { applyFilter } = useFilters(PartController.index.url())
const page = usePage()

// Flash messages
watch(() => page.props.flash, (flash) => {
  if (flash.success){
    success(flash.success)
  }

  if (flash.error)   {
    error(flash.error)
  }
}, { immediate: true })

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
</script>

<template>
  <AppLayout title="Pièces détachées">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Pièces détachées</h1>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ parts.meta.total }} pièce{{ parts.meta.total > 1 ? 's' : '' }} au total
          </p>
        </div>
        <Button v-show="can('stock.create')" @click="openCreate">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Ajouter une pièce
        </Button>
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
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Pièce</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">SKU</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Catégorie</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Stock par dépôt</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Prix unit.</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Fournisseur</th>
                <th class="px-4 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="parts.data.length === 0">
                <td colspan="8" class="px-4 py-12 text-center text-gray-400">
                  Aucune pièce trouvée
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
                  <div v-if="part.stock_depots?.length" class="space-y-1">
                    <div
                      v-for="sd in part.stock_depots"
                      :key="sd.id"
                      class="flex items-center justify-between gap-3"
                    >
                      <span class="text-xs text-gray-500">{{ sd.depot_name ?? '—' }}</span>
                      <LowStockBadge :quantity="sd.quantity" :alert-quantity="sd.alert_quantity" />
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
                    <Button v-show="can('stock.edit')" variant="ghost" size="sm" @click="openEdit(part)">
                      Modifier
                    </Button>
                    <Button
                      v-show="can('stock.delete')"
                      variant="ghost"
                      size="sm"
                      :loading="deletingId === part.id"
                      @click="confirmDelete(part)"
                      class="text-red-500 hover:text-red-700 hover:bg-red-50"
                    >
                      Supprimer
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
      :title="editingPart ? 'Modifier la pièce' : 'Ajouter une pièce'"
      max-width="lg"
      @close="showModal = false"
    >
      <PartForm
        :part="editingPart"
        :categories="categories"
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

  </AppLayout>
</template>