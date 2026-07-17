<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController'
import PartStockPicker from '@/Components/Stock/PartStockPicker.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Select from '@/Components/UI/Select.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { StockSearchResult } from '@/types'

interface SimpleCustomer { id: number; name: string; email?: string }
interface SimpleTicket { id: number; reference: string; customer: string; device: string }

const props = defineProps<{
  customers: SimpleCustomer[]
  tickets: SimpleTicket[]
}>()

interface Line {
  type: 'service' | 'part'
  label: string
  quantity: number
  unit_price: number
  part_id: number | null
  max_quantity?: number
}

const page = usePage()
const depotActive = computed(() => page.props.auth.depotActive)

const form = useForm({
  customer_id: '' as number | '',
  ticket_id: '' as number | '',
  tax_rate: 20,
  due_at: '',
  notes: '',
  lines: [] as Line[],
})

const customerOptions = computed(() => props.customers.map(c => ({ value: c.id, label: c.name })))
const ticketOptions = computed(() => [
  { value: '', label: 'Aucun ticket associé' },
  ...props.tickets.map(t => ({ value: t.id, label: `${t.reference} — ${t.customer} — ${t.device}` })),
])

watch(() => form.ticket_id, (ticketId) => {
  if (!ticketId) {
 return
}

  const ticket = props.tickets.find(t => t.id === Number(ticketId))

  if (!ticket) {
 return
}

  const customer = props.customers.find(c => c.name === ticket.customer)

  if (customer) {
 form.customer_id = customer.id
}
})

function addServiceLine() {
  form.lines.push({ type: 'service', label: '', quantity: 1, unit_price: 0, part_id: null })
}

function addPartFromStock(result: StockSearchResult) {
  const existing = form.lines.find(l => l.part_id === result.id)

  if (existing) {
    if (existing.quantity < (existing.max_quantity ?? result.quantity)) {
      existing.quantity++
    }

    return
  }

  form.lines.push({
    type: 'part',
    label: result.name,
    quantity: 1,
    unit_price: result.sell_price,
    part_id: result.id,
    max_quantity: result.quantity,
  })
}

function removeLine(index: number) {
  form.lines.splice(index, 1)
}

const totalHT = computed(() =>
  form.lines.reduce((sum, l) => sum + l.quantity * l.unit_price, 0)
)
const taxAmount = computed(() => totalHT.value * (form.tax_rate / 100))
const totalTTC = computed(() => totalHT.value + taxAmount.value)

const fmtXof = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(v)

const submitError = ref<string | null>(null)

function submit() {
  submitError.value = null

  form.post(InvoiceController.store.url(), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      if (page.props.flash.error) {
        submitError.value = page.props.flash.error
      }
    },
  })
}
function goBack() {
  window.history.back()
}
</script>

<template>
  <AppLayout title="Nouvelle facture">
    <div class="max-w-6xl space-y-6">

      <!-- Header -->
      <div class="flex items-center gap-3">
        <Button variant="ghost" size="sm" @click="goBack()">
          ← Retour
        </Button>
        <h1 class="text-xl font-semibold text-gray-900">Nouvelle facture</h1>
      </div>

      <div v-if="submitError" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        {{ submitError }}
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

        <!-- Gauche : facture -->
        <form @submit.prevent="submit" class="space-y-6">

          <!-- Infos générales -->
          <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700">Informations générales</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client *</label>
                <Select
                  v-model="form.customer_id"
                  :options="customerOptions"
                  placeholder="Sélectionner un client"
                  :error="form.errors.customer_id"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ticket associé</label>
                <Select
                  v-model="form.ticket_id"
                  :options="ticketOptions"
                  :error="form.errors.ticket_id"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">TVA (%)</label>
                <Input v-model="form.tax_rate" type="number" step="0.01" :error="form.errors.tax_rate" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date d'échéance</label>
                <Input v-model="form.due_at" type="date" :error="form.errors.due_at" />
              </div>
              <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea v-model="form.notes" rows="2"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
              </div>
            </div>
          </div>

          <!-- Lignes -->
          <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-sm font-semibold text-gray-700">Lignes de facturation</h2>
              <Button variant="secondary" size="sm" type="button" @click="addServiceLine">+ Prestation</Button>
            </div>

            <div v-if="form.lines.length === 0" class="text-center text-sm text-gray-400 py-6">
              Ajoutez une prestation ou cliquez sur une pièce en stock à droite.
            </div>

            <div v-else class="space-y-2">
              <!-- En-tête colonnes -->
              <div class="grid grid-cols-12 gap-2 text-xs font-medium text-gray-500 uppercase tracking-wide px-1">
                <div class="col-span-1">Type</div>
                <div class="col-span-5">Désignation</div>
                <div class="col-span-2">Qté</div>
                <div class="col-span-2">Prix unit.</div>
                <div class="col-span-1">Total</div>
                <div class="col-span-1" />
              </div>

              <div
                v-for="(line, i) in form.lines"
                :key="i"
                class="grid grid-cols-12 gap-2 items-center"
              >
                <div class="col-span-1">
                  <span class="text-xs px-1.5 py-0.5 rounded font-medium"
                    :class="line.type === 'service' ? 'bg-indigo-50 text-indigo-700' : 'bg-orange-50 text-orange-700'">
                    {{ line.type === 'service' ? 'Prest.' : 'Pièce' }}
                  </span>
                </div>
                <div class="col-span-5">
                  <Input v-if="line.type === 'service'" v-model="line.label" placeholder="Désignation" />
                  <p v-else class="text-sm font-medium text-gray-900 px-1 truncate">{{ line.label }}</p>
                </div>
                <div class="col-span-2">
                  <Input v-model.number="line.quantity" type="number" min="1" :max="line.max_quantity" />
                </div>
                <div class="col-span-2">
                  <Input v-model.number="line.unit_price" type="number" step="0.01" min="0" />
                </div>
                <div class="col-span-1 text-sm text-gray-700 font-medium">
                  {{ fmtXof(line.quantity * line.unit_price) }}
                </div>
                <div class="col-span-1 text-right">
                  <button type="button" @click="removeLine(i)"
                    class="text-gray-400 hover:text-red-500 transition text-lg leading-none">×</button>
                </div>
              </div>
            </div>

            <!-- Totaux -->
            <div v-if="form.lines.length > 0" class="border-t border-gray-100 pt-4 space-y-1 text-sm">
              <div class="flex justify-between text-gray-600">
                <span>Sous-total HT</span>
                <span>{{ fmtXof(totalHT) }}</span>
              </div>
              <div class="flex justify-between text-gray-600">
                <span>TVA ({{ form.tax_rate }}%)</span>
                <span>{{ fmtXof(taxAmount) }}</span>
              </div>
              <div class="flex justify-between font-semibold text-gray-900 text-base pt-1 border-t border-gray-100">
                <span>Total TTC</span>
                <span>{{ fmtXof(totalTTC) }}</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3">
            <Button variant="secondary" type="button" @click="router.visit(InvoiceController.index.url())">
              Annuler
            </Button>
            <Button type="submit" :loading="form.processing" :disabled="form.lines.length === 0">
              Créer la facture
            </Button>
          </div>

        </form>

        <!-- Droite : pièces en stock -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4 lg:sticky lg:top-6">
          <h2 class="text-sm font-semibold text-gray-700">Pièces en stock</h2>
          <p v-if="!depotActive" class="text-sm text-amber-600">
            Sélectionnez un dépôt actif (en haut) pour ajouter des pièces depuis le stock.
          </p>
          <PartStockPicker v-else :depot-id="depotActive.id" @select="addPartFromStock" />
        </div>

      </div>
    </div>
  </AppLayout>
</template>
