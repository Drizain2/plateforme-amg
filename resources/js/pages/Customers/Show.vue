<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import CustomerController from '@/actions/App/Http/Controllers/Customer/CustomerController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import { useToast } from '@/Composables/useToast'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { BadgeVariant, Customer } from '@/types'

const props = defineProps<{
  customer: Customer
  total_spent: number
}>()

const { success, error } = useToast()
const page = usePage()

watch(() => page.props.flash, (flash) => {
  if (flash.success) {
 success(flash.success) 
}

  if (flash.error) {
 error(flash.error) 
}
}, { immediate: true })

const fmt = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' })

// Édition
const showEditModal = ref(false)
const editForm = useForm({
  name: props.customer.name,
  email: props.customer.email ?? '',
  phone: props.customer.phone ?? '',
  address: props.customer.address ?? '',
  notes: props.customer.notes ?? '',
})

function submitEdit() {
  editForm.put(CustomerController.update.url(props.customer.id), {
    preserveScroll: true,
    onSuccess: () => {
 showEditModal.value = false 
},
  })
}

// Suppression
const deleting = ref(false)
function confirmDelete() {
  if (!confirm(`Supprimer le client "${props.customer.name}" ?`)) {
 return 
}

  deleting.value = true
  router.delete(CustomerController.destroy.url(props.customer.id), {
    onFinish: () => {
 deleting.value = false 
},
  })
}

function statusBadge(color: BadgeVariant) {
 return color 
}
</script>

<template>
  <AppLayout :title="customer.name">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-start justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">{{ customer.name }}</h1>
          <div class="flex items-center gap-4 mt-1 text-sm text-gray-500">
            <a v-if="customer.email" :href="`mailto:${customer.email}`" class="hover:text-indigo-600">
              {{ customer.email }}
            </a>
            <a v-if="customer.phone" :href="`tel:${customer.phone}`" class="hover:text-indigo-600">
              {{ customer.phone }}
            </a>
          </div>
        </div>
        <div class="flex gap-2">
          <Button variant="secondary" @click="showEditModal = true">Modifier</Button>
          <Button variant="ghost" :loading="deleting" class="text-red-500 hover:bg-red-50" @click="confirmDelete">
            Supprimer
          </Button>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <div class="text-2xl font-bold text-indigo-700">{{ customer.tickets_count ?? 0 }}</div>
          <div class="text-xs text-gray-500 mt-0.5">Tickets</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <div class="text-2xl font-bold text-gray-800">{{ customer.devices_count ?? 0 }}</div>
          <div class="text-xs text-gray-500 mt-0.5">Appareils</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <div class="text-2xl font-bold text-green-700">{{ fmt.format(total_spent) }}</div>
          <div class="text-xs text-gray-500 mt-0.5">Total dépensé</div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Tickets récents -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Tickets récents</h2>
          </div>
          <div v-if="!customer.tickets?.length" class="px-4 py-8 text-center text-sm text-gray-400">
            Aucun ticket
          </div>
          <ul v-else class="divide-y divide-gray-100">
            <li v-for="ticket in customer.tickets" :key="ticket.id" class="px-4 py-3 flex items-center justify-between gap-3">
              <div>
                <div class="text-sm font-medium text-gray-900">{{ ticket.reference }}</div>
                <div class="text-xs text-gray-400">{{ ticket.device_name }} · {{ ticket.created_at }}</div>
              </div>
              <Badge :variant="statusBadge(ticket.status_color)">{{ ticket.status_label }}</Badge>
            </li>
          </ul>
        </div>

        <!-- Appareils -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Appareils</h2>
          </div>
          <div v-if="!customer.devices?.length" class="px-4 py-8 text-center text-sm text-gray-400">
            Aucun appareil
          </div>
          <ul v-else class="divide-y divide-gray-100">
            <li v-for="device in customer.devices" :key="device.id" class="px-4 py-3">
              <div class="text-sm font-medium text-gray-900">{{ device.full_name }}</div>
              <div class="text-xs text-gray-400 mt-0.5">
                {{ device.type }}
                <span v-if="device.serial_number"> · {{ device.serial_number }}</span>
                <span v-if="device.color"> · {{ device.color }}</span>
              </div>
            </li>
          </ul>
        </div>

      </div>

      <!-- Notes -->
      <div v-if="customer.address || customer.notes" class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
        <div v-if="customer.address">
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Adresse</div>
          <p class="text-sm text-gray-700">{{ customer.address }}</p>
        </div>
        <div v-if="customer.notes">
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Notes</div>
          <p class="text-sm text-gray-700 whitespace-pre-line">{{ customer.notes }}</p>
        </div>
      </div>

    </div>

    <!-- Modal édition -->
    <Modal :show="showEditModal" title="Modifier le client" @close="showEditModal = false">
      <form @submit.prevent="submitEdit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
          <Input v-model="editForm.name" :error="editForm.errors.name" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <Input v-model="editForm.email" type="email" :error="editForm.errors.email" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
            <Input v-model="editForm.phone" :error="editForm.errors.phone" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
          <Input v-model="editForm.address" :error="editForm.errors.address" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
          <textarea v-model="editForm.notes" rows="3"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button variant="secondary" @click="showEditModal = false">Annuler</Button>
          <Button type="submit" :loading="editForm.processing">Enregistrer</Button>
        </div>
      </form>
    </Modal>

  </AppLayout>
</template>
