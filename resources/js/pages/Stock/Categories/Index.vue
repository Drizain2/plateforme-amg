<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import CategorieController from '@/actions/App/Http/Controllers/Stock/CategorieController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import { usePermission } from '@/Composables/usePermission'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { Category } from '@/types'

defineProps<{
  categories: Pick<Category, 'id' | 'name' | 'is_active'>[]
}>()

const { can } = usePermission()

// Création
const showCreateModal = ref(false)
const createForm = useForm({ name: '', is_active: true })

function submitCreate() {
  createForm.post(CategorieController.store.url(), {
    preserveScroll: true,
    onSuccess: () => {
 showCreateModal.value = false; createForm.reset() 
},
  })
}

// Édition
const editingCategory = ref<Pick<Category, 'id' | 'name' | 'is_active'> | null>(null)
const editForm = useForm({ name: '', is_active: true })

function openEdit(cat: Pick<Category, 'id' | 'name' | 'is_active'>) {
  editingCategory.value = cat
  editForm.name = cat.name
  editForm.is_active = cat.is_active
}

function submitEdit() {
  if (!editingCategory.value) {
 return 
}

  editForm.put(CategorieController.update.url(editingCategory.value.id), {
    preserveScroll: true,
    onSuccess: () => {
 editingCategory.value = null 
},
  })
}

// Suppression
const deletingId = ref<number | null>(null)

function confirmDelete(cat: Pick<Category, 'id' | 'name'>) {
  if (!confirm(`Supprimer "${cat.name}" ?`)) {
 return 
}

  deletingId.value = cat.id
  router.delete(CategorieController.destroy.url(cat.id), {
    preserveScroll: true,
    onFinish: () => {
 deletingId.value = null 
},
  })
}
</script>

<template>
  <AppLayout title="Catégories">
    <div class="space-y-6 max-w-7xl mx-auto">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Catégories d'articles</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ categories.length }} catégorie{{ categories.length > 1 ? 's' : '' }}</p>
        </div>
        <Button v-if="can('stock.create')" @click="showCreateModal = true">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nouvelle catégorie
        </Button>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Nom</th>
              <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Statut</th>
              <th class="px-4 py-3" />
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="categories.length === 0">
              <td colspan="3" class="px-4 py-12 text-center text-gray-400">Aucune catégorie</td>
            </tr>

            <!-- Ligne en édition -->
            <template v-for="cat in categories" :key="cat.id">
              <tr v-if="editingCategory?.id === cat.id" class="bg-indigo-50">
                <td class="px-4 py-2">
                  <Input v-model="editForm.name" :error="editForm.errors.name" autofocus />
                </td>
                <td class="px-4 py-2">
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="editForm.is_active"
                      class="w-4 h-4 rounded border-gray-300 text-indigo-600" />
                    <span class="text-sm text-gray-700">Active</span>
                  </label>
                </td>
                <td class="px-4 py-2">
                  <div class="flex items-center justify-end gap-1">
                    <Button size="sm" :loading="editForm.processing" @click="submitEdit">Sauvegarder</Button>
                    <Button variant="ghost" size="sm" @click="editingCategory = null">Annuler</Button>
                  </div>
                </td>
              </tr>

              <!-- Ligne normale -->
              <tr v-else class="hover:bg-gray-50 transition" :class="{ 'opacity-50': !cat.is_active }">
                <td class="px-4 py-3 font-medium text-gray-900">{{ cat.name }}</td>
                <td class="px-4 py-3">
                  <Badge :variant="cat.is_active ? 'success' : 'default'">
                    {{ cat.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </td>
                <td class="px-4 py-3">
                  <div v-if="can('stock.edit') || can('stock.delete')" class="flex items-center justify-end gap-1">
                    <Button
                      v-if="can('stock.edit')"
                      title="Modifier la catégorie"
                      variant="ghost"
                      size="sm"
                      @click="openEdit(cat)"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                      </svg>
                    </Button>
                    <Button
                      v-if="can('stock.delete')"
                      title="Supprimer la catégorie"
                      variant="ghost"
                      size="sm"
                      :loading="deletingId === cat.id"
                      class="text-red-500 hover:text-red-700 hover:bg-red-50"
                      @click="confirmDelete(cat)"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </Button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

    </div>

    <!-- Modal création -->
    <Modal :show="showCreateModal" title="Nouvelle catégorie" @close="showCreateModal = false">
      <form @submit.prevent="submitCreate" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
          <Input v-model="createForm.name" placeholder="Ex: Écrans" :error="createForm.errors.name" autofocus />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button variant="secondary" @click="showCreateModal = false">Annuler</Button>
          <Button type="submit" :loading="createForm.processing">Créer</Button>
        </div>
      </form>
    </Modal>

  </AppLayout>
</template>
