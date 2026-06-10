<!-- resources/js/Pages/Customers/Index.vue -->
<script setup lang="ts">
import { router, usePage, Link, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import CustomerController from '@/actions/App/Http/Controllers/Customer/CustomerController'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import { useFilters } from '@/Composables/useFilters'
import { useToast } from '@/Composables/useToast'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { Customer, PaginatedResource } from '@/types'

const props = defineProps<{
  customers: PaginatedResource<Customer>
  filters: { search?: string }
}>()

const { success, error } = useToast()
const { applyFilter } = useFilters('customers.index')
const page = usePage()

watch(() => page.props.flash, (flash) => {
  if (flash.success) {
    success(flash.success)
  }

  if (flash.error) {
    error(flash.error)
  }
}, { immediate: true })

const search = ref(props.filters.search ?? '')
watch(search, val => applyFilter({ search: val || undefined }))

// Modal création
const showModal = ref(false)
const form = useForm({
  name: '',
  email: '',
  phone: '',
  address: '',
  notes: '',
})

function submit() {
  form.post(CustomerController.store.url(), {
    onSuccess: () => {
      showModal.value = false; form.reset()
    },
  })
}

// Suppression
const deletingId = ref<number | null>(null)

function confirmDelete(customer: Customer) {
  if (!confirm(`Supprimer "${customer.name}" ?`)) {
    return
  }

  deletingId.value = customer.id
  router.delete(CustomerController.destroy.url(customer.id), {
    preserveScroll: true,
    onFinish: () => deletingId.value = null,
  })
}

function goToPage(url: string | null) {
  if (!url) {
    return
  }

  router.visit(url, { preserveScroll: true, preserveState: true })
}

const fmt = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(v)
</script>

<template>
  <AppLayout title="Clients">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Clients</h1>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ customers.meta.total }} client{{ customers.meta.total > 1 ? 's' : '' }}
          </p>
        </div>
        <Button @click="showModal = true">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nouveau client
        </Button>
      </div>

      <!-- Filtre -->
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <Input v-model="search" placeholder="Nom, email, téléphone..." class="max-w-sm" />
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
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">Tickets</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">Appareils
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Total dépensé
                </th>
                <th class="px-4 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="customers.data.length === 0">
                <td colspan="7" class="px-4 py-12 text-center text-gray-400">Aucun client</td>
              </tr>
              <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 font-medium text-gray-900">{{ customer.name }}</td>
                <td class="px-4 py-3 text-gray-500">
                  <a v-if="customer.email" :href="`mailto:${customer.email}`" class="hover:text-indigo-600 transition">
                    {{ customer.email }}
                  </a>
                  <span v-else class="text-gray-300">—</span>
                </td>
                <td class="px-4 py-3 text-gray-500">
                  <a v-if="customer.phone" :href="`tel:${customer.phone}`" class="hover:text-indigo-600 transition">
                    {{ customer.phone }}
                  </a>
                  <span v-else class="text-gray-300">—</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span
                    class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-50 text-indigo-700 text-xs font-bold">
                    {{ customer.tickets_count }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span
                    class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 text-gray-600 text-xs font-bold">
                    {{ customer.devices_count }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right font-medium text-gray-900">
                  {{ customer.total_spent ? fmt(customer.total_spent) : '—' }}
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-1">
                    <Link :href="CustomerController.show.url(customer.id)">
                      <Button variant="ghost" size="sm">Voir</Button>
                    </Link>
                    <Button variant="ghost" size="sm" :loading="deletingId === customer.id"
                      @click="confirmDelete(customer)" class="text-red-500 hover:text-red-700 hover:bg-red-50">
                      Supprimer
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="customers.meta.last_page > 1"
          class="px-4 py-3 border-t flex items-center justify-between text-sm text-gray-600">
          <span>{{ customers.meta.from }}–{{ customers.meta.to }} sur {{ customers.meta.total }}</span>
          <div class="flex gap-1">
            <Button variant="secondary" size="sm" :disabled="!customers.links.prev"
              @click="goToPage(customers.links.prev)">← Précédent</Button>
            <Button variant="secondary" size="sm" :disabled="!customers.links.next"
              @click="goToPage(customers.links.next)">Suivant →</Button>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal création -->
    <Modal :show="showModal" title="Nouveau client" @close="showModal = false">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
          <Input v-model="form.name" placeholder="Jean Dupont" :error="form.errors.name" autofocus />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <Input v-model="form.email" type="email" :error="form.errors.email" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
            <Input v-model="form.phone" :error="form.errors.phone" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
          <Input v-model="form.address" :error="form.errors.address" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
          <textarea v-model="form.notes" rows="2"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button variant="secondary" @click="showModal = false">Annuler</Button>
          <Button type="submit" :loading="form.processing">Créer</Button>
        </div>
      </form>
    </Modal>

  </AppLayout>
</template>