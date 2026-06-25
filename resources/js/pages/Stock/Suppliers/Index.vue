<!-- resources/js/Pages/Stock/Suppliers/Index.vue -->
<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import SupplierController from '@/actions/App/Http/Controllers/Stock/SupplierController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import { useFilters } from '@/Composables/useFilters'
import { usePermission } from '@/Composables/usePermission'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { Supplier,PaginatedResource } from '@/types'

const props = defineProps<{
  suppliers: PaginatedResource<Supplier & { parts_count: number }>
  filters: { search?: string }
}>()

const { isAdmin } = usePermission()
const { applyFilter } = useFilters(SupplierController.index.url())

// -----------------------------------------------
// Filtres
// -----------------------------------------------
const search = ref(props.filters?.search ?? '')

watch(search, (val) => {
  applyFilter({ search: val || undefined })
})

// -----------------------------------------------
// Modal création / édition
// -----------------------------------------------
const showModal      = ref(false)
const editingSupplier = ref<(Supplier & { parts_count: number }) | null>(null)

const form = useForm({
  name:    '',
  email:   '',
  phone:   '',
  address: '',
})

function openCreate() {
  editingSupplier.value = null
  form.reset()
  showModal.value = true
}

function openEdit(supplier: Supplier & { parts_count: number }) {
  editingSupplier.value = supplier
  form.name    = supplier.name
  form.email   = supplier.email ?? ''
  form.phone   = supplier.phone ?? ''
  form.address = supplier.address ?? ''
  showModal.value = true
}

function submit() {
  if (editingSupplier.value) {
    form.put(SupplierController.update.url(editingSupplier.value.id), {
      preserveScroll: true,
      onSuccess: () => {
 showModal.value = false 
},
    })
  } else {
    form.post(SupplierController.store.url(), {
      preserveScroll: true,
      onSuccess: () => {
 showModal.value = false 
},
    })
  }
}

// -----------------------------------------------
// Suppression / désactivation
// -----------------------------------------------
const deletingId = ref<number | null>(null)

function confirmDelete(supplier: Supplier & { parts_count: number }) {
  const msg = supplier.parts_count > 0
    ? `Ce fournisseur a ${supplier.parts_count} pièce(s) associée(s). Il sera désactivé. Continuer ?`
    : `Supprimer le fournisseur "${supplier.name}" ?`

  if (!confirm(msg)) {
return
}

  deletingId.value = supplier.id
  router.delete(SupplierController.destroy.url(supplier.id), {
    preserveScroll: true,
    onFinish: () => deletingId.value = null,
  })
}

// -----------------------------------------------
// Pagination
// -----------------------------------------------
function goToPage(url: string | null) {
  if (!url) {
return
}

  router.visit(url, { preserveScroll: true, preserveState: true })
}
</script>

<template>
  <AppLayout title="Fournisseurs">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Fournisseurs</h1>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ suppliers.meta.total }} fournisseur{{ suppliers.meta.total > 1 ? 's' : '' }}
          </p>
        </div>
        <Button v-if="isAdmin" @click="openCreate">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Nouveau fournisseur
        </Button>
      </div>

      <!-- Filtre -->
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <Input
          v-model="search"
          placeholder="Rechercher un fournisseur..."
          class="max-w-sm"
        />
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Nom</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Email</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Téléphone</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Adresse</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Pièces</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Statut</th>
                <th class="px-4 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="suppliers.data.length === 0">
                <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                  Aucun fournisseur trouvé
                </td>
              </tr>
              <tr
                v-for="supplier in suppliers.data"
                :key="supplier.id"
                class="hover:bg-gray-50 transition"
                :class="{ 'opacity-60': !supplier.is_active }"
              >
                <td class="px-4 py-3 font-medium text-gray-900">{{ supplier.name }}</td>

                <td class="px-4 py-3 text-gray-500">
                  <a
                    v-if="supplier.email"
                    :href="`mailto:${supplier.email}`"
                    class="hover:text-indigo-600 transition"
                  >
                    {{ supplier.email }}
                  </a>
                  <span v-else class="text-gray-300">—</span>
                </td>

                <td class="px-4 py-3 text-gray-500">
                  <a
                    v-if="supplier.phone"
                    :href="`tel:${supplier.phone}`"
                    class="hover:text-indigo-600 transition"
                  >
                    {{ supplier.phone }}
                  </a>
                  <span v-else class="text-gray-300">—</span>
                </td>

                <td class="px-4 py-3 text-gray-500 max-w-xs truncate">
                  {{ supplier.address ?? '—' }}
                </td>

                <td class="px-4 py-3">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                    {{ supplier.parts_count }}
                  </span>
                </td>

                <td class="px-4 py-3">
                  <Badge :variant="supplier.is_active ? 'success' : 'default'">
                    {{ supplier.is_active ? 'Actif' : 'Inactif' }}
                  </Badge>
                </td>

                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-1">
                    <Button
                      v-if="isAdmin"
                      variant="ghost"
                      size="sm"
                      @click="openEdit(supplier)"
                    >
                      Modifier
                    </Button>
                    <Button
                      v-if="isAdmin"
                      variant="ghost"
                      size="sm"
                      :loading="deletingId === supplier.id"
                      @click="confirmDelete(supplier)"
                      class="text-red-500 hover:text-red-700 hover:bg-red-50"
                    >
                      {{ supplier.parts_count > 0 ? 'Désactiver' : 'Supprimer' }}
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="suppliers.meta.last_page > 1"
          class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600"
        >
          <span>
            {{ suppliers.meta.from }}–{{ suppliers.meta.to }} sur {{ suppliers.meta.total }}
          </span>
          <div class="flex gap-1">
            <Button
              variant="secondary"
              size="sm"
              :disabled="!suppliers.links.prev"
              @click="goToPage(suppliers.links.prev)"
            >
              ← Précédent
            </Button>
            <Button
              variant="secondary"
              size="sm"
              :disabled="!suppliers.links.next"
              @click="goToPage(suppliers.links.next)"
            >
              Suivant →
            </Button>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal -->
    <Modal
      :show="showModal"
      :title="editingSupplier ? 'Modifier le fournisseur' : 'Nouveau fournisseur'"
      @close="showModal = false"
    >
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
          <Input
            v-model="form.name"
            placeholder="Ex: GSM Partner"
            :error="form.errors.name"
            autofocus
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <Input
            v-model="form.email"
            type="email"
            placeholder="contact@fournisseur.fr"
            :error="form.errors.email"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
          <Input
            v-model="form.phone"
            placeholder="01 23 45 67 89"
            :error="form.errors.phone"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
          <Input
            v-model="form.address"
            placeholder="12 rue de la Paix, Paris"
            :error="form.errors.address"
          />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <Button variant="secondary" @click="showModal = false">Annuler</Button>
          <Button type="submit" :loading="form.processing">
            {{ editingSupplier ? 'Mettre à jour' : 'Créer' }}
          </Button>
        </div>
      </form>
    </Modal>

  </AppLayout>
</template>