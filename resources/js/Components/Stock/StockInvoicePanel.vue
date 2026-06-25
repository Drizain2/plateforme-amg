<!-- resources/js/Components/Stock/StockInvoicePanel.vue -->
<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import CustomerController from '@/actions/App/Http/Controllers/Customer/CustomerController'
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController'
import PartStockPicker from '@/Components/Stock/PartStockPicker.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import { usePermission } from '@/Composables/usePermission'
import type { StockSearchResult } from '@/types'

interface FoundCustomer { id: number; name: string; email?: string; phone?: string }
interface CartLine {
  part_id: number | null
  type: 'service' | 'part'
  label: string
  quantity: number
  unit_price: number
  max_quantity?: number
}

const props = defineProps<{ show: boolean }>()
const emit = defineEmits<{ close: [] }>()

const page = usePage()
const { can } = usePermission()
const canCreateCustomer = computed(() => can('customers.create'))
const depotActive = computed(() => page.props.auth.depotActive)

// Client
const customerSearch  = ref('')
const foundCustomers  = ref<FoundCustomer[]>([])
const selectedCustomer = ref<FoundCustomer | null>(null)
const newCustomerName  = ref('')
const newCustomerPhone = ref('')
const newCustomerEmail = ref('')

async function searchCustomers() {
  if (customerSearch.value.length < 2) {
    foundCustomers.value = []

    return
  }

  const res = await fetch(CustomerController.search.url({ query: { q: customerSearch.value } }), {
    headers: { Accept: 'application/json' },
  })
  foundCustomers.value = await res.json()
}

function selectCustomer(c: FoundCustomer) {
  selectedCustomer.value = c
  foundCustomers.value   = []
  customerSearch.value   = ''
}

// Lignes de facture
const lines = ref<CartLine[]>([])

function addPart(result: StockSearchResult) {
  const existing = lines.value.find(l => l.part_id === result.id)

  if (existing) {
    if (existing.quantity < (existing.max_quantity ?? result.quantity)) {
      existing.quantity++
    }

    return
  }

  lines.value.push({
    part_id: result.id,
    type: 'part',
    label: result.name,
    quantity: 1,
    unit_price: result.sell_price,
    max_quantity: result.quantity,
  })
}

function addFreeLine() {
  lines.value.push({ part_id: null, type: 'service', label: '', quantity: 1, unit_price: 0 })
}

function removeLine(index: number) {
  lines.value.splice(index, 1)
}

// Totaux
const taxRate = ref(20)
const totalHT  = computed(() => lines.value.reduce((sum, l) => sum + l.quantity * l.unit_price, 0))
const taxAmount = computed(() => totalHT.value * (taxRate.value / 100))
const totalTTC  = computed(() => totalHT.value + taxAmount.value)

const fmtXof = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(v)

// Soumission
const form = useForm({
  customer_id: '' as number | '',
  customer_name: '',
  customer_phone: '',
  customer_email: '',
  tax_rate: 20,
  lines: [] as { type: string; label: string; quantity: number; unit_price: number; part_id: number | null }[],
})

const submitError = ref<string | null>(null)

function submit() {
  submitError.value    = null
  form.customer_id     = selectedCustomer.value?.id ?? ''
  form.customer_name   = selectedCustomer.value?.name || newCustomerName.value
  form.customer_phone  = selectedCustomer.value?.phone || newCustomerPhone.value
  form.customer_email  = selectedCustomer.value?.email || newCustomerEmail.value
  form.tax_rate        = taxRate.value
  form.lines           = lines.value.map(l => ({
    type: l.type,
    label: l.label,
    quantity: l.quantity,
    unit_price: l.unit_price,
    part_id: l.part_id,
  }))

  form.post(InvoiceController.store.url(), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      // Une facture créée avec succès redirige vers sa page de détail ;
      // si on est encore sur le stock, c'est qu'une erreur métier (ex.
      // stock insuffisant) a renvoyé ici avec un message flash.
      if (page.props.flash.error) {
        submitError.value = page.props.flash.error
      } else {
        emit('close')
      }
    },
  })
}

function resetState() {
  selectedCustomer.value = null
  customerSearch.value   = ''
  foundCustomers.value   = []
  newCustomerName.value  = ''
  newCustomerPhone.value = ''
  newCustomerEmail.value = ''
  lines.value            = []
  taxRate.value          = 20
  submitError.value      = null
  form.reset()
  form.clearErrors()
}

watch(() => props.show, (shown) => {
  if (shown) {
    resetState()
  }
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="$emit('close')" />

        <div class="relative w-full max-w-6xl h-[90vh] bg-white rounded-xl shadow-xl flex flex-col overflow-hidden">
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b shrink-0">
            <h3 class="text-base font-semibold text-gray-900">Facturer depuis le stock</h3>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div class="flex-1 grid grid-cols-2 divide-x divide-gray-200 overflow-hidden">

            <!-- Gauche : facture en cours -->
            <div class="flex flex-col overflow-y-auto p-6 space-y-5">
              <div v-if="submitError" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ submitError }}
              </div>

              <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Client</p>

                <div v-if="selectedCustomer" class="flex items-center justify-between p-3 bg-indigo-50 rounded-lg">
                  <span class="text-sm font-medium text-indigo-700">{{ selectedCustomer.name }}</span>
                  <Button variant="ghost" size="sm" @click="selectedCustomer = null">Changer</Button>
                </div>

                <template v-else>
                  <div class="relative">
                    <Input
                      v-model="customerSearch"
                      placeholder="Rechercher un client (nom, téléphone)..."
                      @input="searchCustomers"
                    />
                    <div
                      v-if="foundCustomers.length"
                      class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg divide-y max-h-48 overflow-y-auto"
                    >
                      <button
                        v-for="c in foundCustomers"
                        :key="c.id"
                        type="button"
                        class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 transition"
                        @click="selectCustomer(c)"
                      >
                        <span class="font-medium">{{ c.name }}</span>
                        <span v-if="c.phone" class="text-gray-400 ml-2 text-xs">{{ c.phone }}</span>
                      </button>
                    </div>
                  </div>

                  <div v-if="canCreateCustomer" class="grid grid-cols-2 gap-2 mt-2">
                    <Input
                      v-model="newCustomerName"
                      placeholder="Nom du client *"
                      class="col-span-2"
                      :error="form.errors.customer_name"
                    />
                    <Input v-model="newCustomerPhone" placeholder="Téléphone" />
                    <Input v-model="newCustomerEmail" placeholder="Email" />
                  </div>
                  <p v-else class="text-xs text-gray-400 mt-2">
                    Aucun client trouvé. Recherchez un client existant.
                  </p>
                </template>
              </div>

              <div class="flex-1">
                <div class="flex items-center justify-between mb-2">
                  <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Lignes</p>
                  <Button variant="ghost" size="sm" type="button" @click="addFreeLine">+ Ligne libre</Button>
                </div>

                <div v-if="lines.length === 0" class="text-center text-sm text-gray-400 py-8 border border-dashed border-gray-200 rounded-lg">
                  Cliquez sur un article à droite pour l'ajouter à la facture.
                </div>

                <div v-else class="space-y-2">
                  <div
                    v-for="(line, i) in lines"
                    :key="i"
                    class="flex items-center gap-2 p-2 rounded-lg border border-gray-100"
                  >
                    <div class="flex-1 min-w-0">
                      <Input v-if="!line.part_id" v-model="line.label" placeholder="Désignation" />
                      <p v-else class="text-sm font-medium text-gray-900 px-1 truncate">{{ line.label }}</p>
                    </div>
                    <Input v-model.number="line.quantity" type="number" min="1" :max="line.max_quantity" class="w-20" />
                    <Input v-model.number="line.unit_price" type="number" step="0.01" min="0" class="w-24" />
                    <span class="text-sm font-medium text-gray-700 w-24 text-right shrink-0">
                      {{ fmtXof(line.quantity * line.unit_price) }}
                    </span>
                    <button type="button" class="text-gray-400 hover:text-red-500 text-lg leading-none" @click="removeLine(i)">×</button>
                  </div>
                </div>
              </div>

              <div v-if="lines.length > 0" class="border-t border-gray-100 pt-4 space-y-1 text-sm">
                <div class="flex justify-between text-gray-600">
                  <span>Sous-total HT</span>
                  <span>{{ fmtXof(totalHT) }}</span>
                </div>
                <div class="flex justify-between items-center text-gray-600">
                  <span class="flex items-center gap-2">
                    TVA
                    <Input v-model.number="taxRate" type="number" step="0.01" min="0" class="w-16" />
                    %
                  </span>
                  <span>{{ fmtXof(taxAmount) }}</span>
                </div>
                <div class="flex justify-between font-semibold text-gray-900 text-base pt-1 border-t border-gray-100">
                  <span>Total TTC</span>
                  <span>{{ fmtXof(totalTTC) }}</span>
                </div>
              </div>
            </div>

            <!-- Droite : stock disponible -->
            <div class="flex flex-col overflow-y-auto p-6 space-y-3">
              <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Stock disponible — {{ depotActive?.name ?? '—' }}</p>
              <PartStockPicker :depot-id="depotActive?.id" @select="addPart" />
            </div>

          </div>

          <!-- Footer -->
          <div class="px-6 py-4 border-t flex justify-end gap-2 shrink-0">
            <Button variant="secondary" type="button" @click="$emit('close')">Annuler</Button>
            <Button :loading="form.processing" :disabled="lines.length === 0" @click="submit">
              Créer la facture
            </Button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
