<!-- resources/js/Pages/Tickets/Create.vue -->
<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import CustomerController from '@/actions/App/Http/Controllers/Customer/CustomerController'
import TicketController from '@/actions/App/Http/Controllers/Ticket/TicketController'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Select from '@/Components/UI/Select.vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps<{
  depots: { id: number; name: string }[]
  technicians: { id: number; name: string }[]
  priorities: { value: string; label: string }[]
}>()

const page = usePage()
const activeDepot = computed(() => page.props.auth.depotActive)
const activeDepotId = page.props.auth.depotActive ? String(page.props.auth.depotActive.id) : ''

const form = useForm({
  // Client
  customer_id:    '',
  customer_name:  '',
  customer_email: '',
  customer_phone: '',
  // Appareil
  device_id:    '',
  device_type:  '',
  device_brand: '',
  device_model: '',
  device_serial:'',
  device_color: '',
  condition_in: '',
  // Ticket
  depot_id:              activeDepotId,
  technician_id:         '',
  priority:              'normal',
  description:           '',
  estimated_return_date: '',
})

// Recherche client existant
const customerSearch  = ref('')
const foundCustomers  = ref<{ id: number; name: string; email?: string; phone?: string }[]>([])
const searchingClient = ref(false)

async function searchCustomers() {
  if (customerSearch.value.length < 2) {
 foundCustomers.value = [];

 return 
}

  searchingClient.value = true
  const res  = await fetch(CustomerController.search.url({ query: { q: customerSearch.value } }), {
    headers: { Accept: 'application/json' }
  })
  foundCustomers.value  = await res.json()
  searchingClient.value = false
}

function selectCustomer(c: typeof foundCustomers.value[0]) {
  form.customer_id    = String(c.id)
  form.customer_name  = c.name
  form.customer_email = c.email ?? ''
  form.customer_phone = c.phone ?? ''
  customerSearch.value   = c.name
  foundCustomers.value   = []
}

function clearCustomer() {
  form.customer_id    = ''
  form.customer_name  = ''
  customerSearch.value= ''
}

const depotOptions      = computed(() => props.depots.map(d => ({ value: d.id, label: d.name })))
const technicianOptions = computed(() => props.technicians.map(t => ({ value: t.id, label: t.name })))
const priorityOptions   = computed(() => props.priorities.map(p => ({ value: p.value, label: p.label })))

const deviceTypes = [
  { value: 'smartphone', label: 'Smartphone' },
  { value: 'tablette',   label: 'Tablette' },
  { value: 'pc',         label: 'PC / Laptop' },
  { value: 'console',    label: 'Console' },
  { value: 'autre',      label: 'Autre' },
]

function submit() {
  form.post(TicketController.store.url({}))
}
</script>

<template>
  <AppLayout title="Nouveau ticket">
    <div class="max-w-3xl space-y-6">

      <h1 class="text-xl font-semibold text-gray-900">Nouveau ticket SAV</h1>

      <form @submit.prevent="submit" class="space-y-6">

        <!-- Client -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
          <h2 class="font-medium text-gray-900">Client</h2>

          <!-- Recherche client existant -->
          <div class="relative">
            <label class="block text-sm font-medium text-gray-700 mb-1">Rechercher un client existant</label>
            <Input
              v-model="customerSearch"
              placeholder="Nom, email..."
              @input="searchCustomers"
            />
            <div
              v-if="foundCustomers.length"
              class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg divide-y"
            >
              <button
                v-for="c in foundCustomers"
                :key="c.id"
                type="button"
                class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 transition"
                @click="selectCustomer(c)"
              >
                <span class="font-medium">{{ c.name }}</span>
                <span v-if="c.email" class="text-gray-400 ml-2 text-xs">{{ c.email }}</span>
              </button>
            </div>
          </div>

          <div v-if="form.customer_id" class="flex items-center justify-between p-3 bg-indigo-50 rounded-lg">
            <span class="text-sm font-medium text-indigo-700">{{ form.customer_name }}</span>
            <Button variant="ghost" size="sm" @click="clearCustomer">Changer</Button>
          </div>

          <!-- Nouveau client -->
          <template v-if="!form.customer_id">
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                <Input v-model="form.customer_name" placeholder="Jean Dupont" :error="form.errors.customer_name" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <Input v-model="form.customer_email" type="email" :error="form.errors.customer_email" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                <Input v-model="form.customer_phone" :error="form.errors.customer_phone" />
              </div>
            </div>
          </template>
        </div>

        <!-- Appareil -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
          <h2 class="font-medium text-gray-900">Appareil</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
              <Select v-model="form.device_type" :options="deviceTypes" :error="form.errors.device_type" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Marque *</label>
              <Input v-model="form.device_brand" placeholder="Apple, Samsung..." :error="form.errors.device_brand" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Modèle *</label>
              <Input v-model="form.device_model" placeholder="iPhone 14, Galaxy S22..." :error="form.errors.device_model" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Couleur</label>
              <Input v-model="form.device_color" placeholder="Noir, Blanc..." />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">N° série</label>
              <Input v-model="form.device_serial" placeholder="IMEI / SN" />
            </div>
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">État à l'entrée</label>
              <textarea
                v-model="form.condition_in"
                rows="2"
                placeholder="Écran fissuré, coque rayée..."
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
              />
            </div>
          </div>
        </div>

        <!-- Ticket -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
          <h2 class="font-medium text-gray-900">Informations ticket</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Dépôt *</label>
              <div v-if="activeDepot" class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-700">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
                </svg>
                {{ activeDepot.name }}
              </div>
              <Select v-else v-model="form.depot_id" :options="depotOptions" :error="form.errors.depot_id" />
              <p v-if="form.errors.depot_id" class="text-xs text-red-500 mt-1">{{ form.errors.depot_id }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Technicien</label>
              <Select v-model="form.technician_id" :options="technicianOptions" placeholder="Non assigné" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Priorité *</label>
              <Select v-model="form.priority" :options="priorityOptions" :error="form.errors.priority" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date retour estimée</label>
              <Input v-model="form.estimated_return_date" type="date" />
            </div>
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Description du problème *</label>
              <textarea
                v-model="form.description"
                rows="3"
                placeholder="Le client décrit le problème..."
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                :class="form.errors.description ? 'border-red-400' : ''"
              />
              <span v-if="form.errors.description" class="text-xs text-red-500">{{ form.errors.description }}</span>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-3">
          <Button variant="secondary" type="button" @click="router.visit(TicketController.index.url())">
            Annuler
          </Button>
          <Button type="submit" :loading="form.processing">
            Créer le ticket
          </Button>
        </div>

      </form>
    </div>
  </AppLayout>
</template>