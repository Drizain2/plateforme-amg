<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import PlanController from '@/actions/App/Http/Controllers/Admin/PlanController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import type { Plan } from '@/types'

defineProps<{
  plans: (Plan & { shops_count: number })[]
}>()

const fmt = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(v)

// -----------------------------------------------
// Modal création / édition
// -----------------------------------------------
const showModal = ref(false)
const editingPlan = ref<(Plan & { shops_count: number }) | null>(null)

const form = useForm({
  name: '',
  slug: '',
  description: '',
  price: 0,
  max_users: '',
  max_depots: '',
  features: '',
  sort_order: 0,
  is_active: true,
})

function openCreate() {
  editingPlan.value = null
  form.reset()
  showModal.value = true
}

function openEdit(plan: Plan & { shops_count: number }) {
  editingPlan.value = plan
  form.name = plan.name
  form.slug = plan.slug
  form.description = plan.description ?? ''
  form.price = plan.price
  form.max_users = plan.max_users?.toString() ?? ''
  form.max_depots = plan.max_depots?.toString() ?? ''
  form.features = (plan.features ?? []).join('\n')
  form.sort_order = plan.sort_order ?? 0
  form.is_active = plan.is_active ?? true
  showModal.value = true
}

function submit() {
  const payload = {
    ...form.data(),
    max_users: form.max_users === '' ? null : Number(form.max_users),
    max_depots: form.max_depots === '' ? null : Number(form.max_depots),
    features: form.features.split('\n').map(f => f.trim()).filter(Boolean),
  }

  if (editingPlan.value) {
    form.transform(() => payload).put(PlanController.update.url(editingPlan.value!.id), {
      preserveScroll: true,
      onSuccess: () => {
        showModal.value = false
      },
    })
  } else {
    form.transform(() => payload).post(PlanController.store.url(), {
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

function confirmDelete(plan: Plan & { shops_count: number }) {
  const msg = plan.shops_count > 0
    ? `Cette offre est utilisée par ${plan.shops_count} atelier(s). Elle sera désactivée. Continuer ?`
    : `Supprimer l'offre "${plan.name}" ?`

  if (!confirm(msg)) {
    return
  }

  deletingId.value = plan.id
  router.delete(PlanController.destroy.url(plan.id), {
    preserveScroll: true,
    onFinish: () => {
      deletingId.value = null
    },
  })
}
</script>

<template>
  <AdminLayout title="Offres d'abonnement">
    <div class="space-y-6">

      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">
          {{ plans.length }} offre{{ plans.length > 1 ? 's' : '' }}
        </p>
        <Button @click="openCreate">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nouvelle offre
        </Button>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Nom</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Prix</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Limites</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Ateliers</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Statut</th>
                <th class="px-4 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="plans.length === 0">
                <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                  Aucune offre pour le moment
                </td>
              </tr>
              <tr v-for="plan in plans" :key="plan.id" class="hover:bg-gray-50 transition"
                :class="{ 'opacity-60': !plan.is_active }">
                <td class="px-4 py-3">
                  <p class="font-medium text-gray-900">{{ plan.name }}</p>
                  <p class="text-xs text-gray-400">{{ plan.slug }}</p>
                </td>

                <td class="px-4 py-3 text-gray-700">
                  {{ plan.price > 0 ? `${fmt(plan.price)}/mois` : 'Gratuit' }}
                </td>

                <td class="px-4 py-3 text-gray-500 text-xs">
                  <p>{{ plan.max_users ? `${plan.max_users} utilisateurs` : 'Utilisateurs illimités' }}</p>
                  <p>{{ plan.max_depots ? `${plan.max_depots} dépôts` : 'Dépôts illimités' }}</p>
                </td>

                <td class="px-4 py-3">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                    {{ plan.shops_count }}
                  </span>
                </td>

                <td class="px-4 py-3">
                  <Badge :variant="plan.is_active ? 'success' : 'default'">
                    {{ plan.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </td>

                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-1">
                    <Button variant="ghost" size="sm" @click="openEdit(plan)">
                      Modifier
                    </Button>
                    <Button variant="ghost" size="sm" :loading="deletingId === plan.id" @click="confirmDelete(plan)"
                      class="text-red-500 hover:text-red-700 hover:bg-red-50">
                      {{ plan.shops_count > 0 ? 'Désactiver' : 'Supprimer' }}
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- Modal -->
    <Modal :show="showModal" :title="editingPlan ? 'Modifier l\'offre' : 'Nouvelle offre'" max-width="lg"
      @close="showModal = false">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
            <Input v-model="form.name" placeholder="Pro" :error="form.errors.name" autofocus />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Slug *</label>
            <Input v-model="form.slug" placeholder="pro" :error="form.errors.slug" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <Input v-model="form.description" :error="form.errors.description" />
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Prix (FCFA/mois) *</label>
            <Input v-model="form.price" type="number" step="1" min="0" :error="form.errors.price" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Max utilisateurs</label>
            <Input v-model="form.max_users" type="number" placeholder="Illimité" :error="form.errors.max_users" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Max dépôts</label>
            <Input v-model="form.max_depots" type="number" placeholder="Illimité" :error="form.errors.max_depots" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fonctionnalités (une par ligne)</label>
          <textarea v-model="form.features" rows="4"
            class="w-full rounded-lg border px-3 py-2 text-sm outline-none transition focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 border-gray-300"
            placeholder="Dépôts illimités&#10;10 utilisateurs&#10;Facturation" />
          <span v-if="form.errors.features" class="text-xs text-red-500">{{ form.errors.features }}</span>
        </div>

        <div class="grid grid-cols-2 gap-4 items-end">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
            <Input v-model="form.sort_order" type="number" :error="form.errors.sort_order" />
          </div>

          <label class="flex items-center gap-2 cursor-pointer select-none pb-2">
            <input type="checkbox" v-model="form.is_active"
              class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
            <span class="text-sm text-gray-600">Offre active (proposée à l'inscription)</span>
          </label>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <Button variant="secondary" type="button" @click="showModal = false">Annuler</Button>
          <Button type="submit" :loading="form.processing">
            {{ editingPlan ? 'Mettre à jour' : 'Créer' }}
          </Button>
        </div>
      </form>
    </Modal>
  </AdminLayout>
</template>
