<!-- resources/js/Pages/Invoices/Show.vue -->
<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController'
import TicketController from '@/actions/App/Http/Controllers/Ticket/TicketController'
import PartStockPicker from '@/Components/Stock/PartStockPicker.vue'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import Select from '@/Components/UI/Select.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { StockSearchResult } from '@/types'
import type { Invoice, BadgeVariant } from '@/types/models'

const props = defineProps<{ invoice: Invoice }>()

const page = usePage()
const depotActive = computed(() => page.props.auth.depotActive)

const fmt = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(v)

// Transition statut
const transitionForm = useForm({ status: '' })

function submitTransition(status: string) {
  if (!confirm('Confirmer ce changement de statut ?')) {
return
}

  transitionForm.status = status
  transitionForm.post(InvoiceController.transition.url(props.invoice.id), {
    preserveScroll: true,
  })
}

// Ajout ligne
const showLineModal = ref(false)
const maxQuantity = ref<number | null>(null)
const lineForm = useForm({
  type:       'service' as 'service' | 'part',
  part_id:    null as number | null,
  label:      '',
  quantity:   1,
  unit_price: 0,
})

const lineTypeOptions = [
  { value: 'service', label: 'Main d\'œuvre' },
  { value: 'part',    label: 'Pièce' },
]

watch(() => lineForm.type, () => {
  lineForm.part_id    = null
  lineForm.label      = ''
  lineForm.unit_price = 0
  maxQuantity.value   = null
})

function selectPartForLine(result: StockSearchResult) {
  lineForm.part_id    = result.id
  lineForm.label      = result.name
  lineForm.unit_price = result.sell_price
  lineForm.quantity   = 1
  maxQuantity.value   = result.quantity
}

function submitLine() {
  lineForm.post(InvoiceController.storeLine.url(props.invoice.id), {
    preserveScroll: true,
    onSuccess: () => closeLineModal(),
  })
}

function closeLineModal() {
  showLineModal.value = false
  lineForm.reset()
  maxQuantity.value = null
}

// Suppression ligne
const deletingLineId = ref<number | null>(null)

function deleteLine(lineId: number) {
  if (!confirm('Supprimer cette ligne ?')) {
return
}

  deletingLineId.value = lineId

  useForm({}).delete(InvoiceController.destroyLine.url({ invoice: props.invoice.id, line: lineId }), {
    preserveScroll: true,
    onFinish: () => deletingLineId.value = null,
  })
}
</script>

<template>
  <AppLayout :title="`Facture ${invoice.number}`">
    <div class="max-w-7xl m-auto space-y-6">

      <!-- Header -->
      <div class="flex items-start justify-between">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-xl font-semibold text-gray-900 font-mono">{{ invoice.number }}</h1>
            <Badge :variant="invoice.status_color as BadgeVariant">{{ invoice.status_label }}</Badge>
          </div>
          <p class="text-sm text-gray-400 mt-1">
            Émise le {{ invoice.issued_at }}
            <span v-if="invoice.due_at"> · Échéance {{ invoice.due_at }}</span>
            <span v-if="invoice.paid_at" class="text-green-600"> · Payée le {{ invoice.paid_at }}</span>
          </p>
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
          <a :href="InvoiceController.pdf.url(invoice.id)" target="_blank">
            <Button variant="secondary" size="sm">Télécharger PDF</Button>
          </a>
          <Button
            v-for="s in invoice.next_statuses"
            :key="s.value"
            size="sm"
            :variant="s.value === 'paid' ? 'primary' : 'secondary'"
            @click="submitTransition(s.value)"
          >
            → {{ s.label }}
          </Button>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-6">

        <!-- Contenu principal -->
        <div class="col-span-2 space-y-6">

          <!-- Infos client + ticket -->
          <div class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-2 gap-6">
            <div>
              <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Client</p>
              <p class="font-medium text-gray-900">{{ invoice.customer?.name }}</p>
              <p v-if="invoice.customer?.email" class="text-sm text-gray-500">{{ invoice.customer.email }}</p>
              <p v-if="invoice.customer?.phone" class="text-sm text-gray-500">{{ invoice.customer.phone }}</p>
            </div>
            <div v-if="invoice.ticket">
              <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Ticket associé</p>
              <a
                :href="TicketController.show.url(invoice.ticket.id)"
                class="font-mono text-sm text-indigo-600 hover:underline"
              >
                {{ invoice.ticket.reference }}
              </a>
            </div>
          </div>

          <!-- Lignes -->
          <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b flex items-center justify-between">
              <p class="text-sm font-medium text-gray-700">Lignes de facturation</p>
              <Button
                v-if="invoice.can_edit"
                variant="ghost"
                size="sm"
                @click="showLineModal = true"
              >
                + Ajouter
              </Button>
            </div>

            <table class="min-w-full divide-y divide-gray-100 text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Désignation</th>
                  <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Type</th>
                  <th class="px-4 py-3 text-right text-xs text-gray-500 uppercase">Qté</th>
                  <th class="px-4 py-3 text-right text-xs text-gray-500 uppercase">PU HT</th>
                  <th class="px-4 py-3 text-right text-xs text-gray-500 uppercase">Total HT</th>
                  <th v-if="invoice.can_edit" class="px-4 py-3" />
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-if="!invoice.lines?.length">
                  <td colspan="6" class="px-4 py-8 text-center text-gray-400">Aucune ligne</td>
                </tr>
                <tr
                  v-for="line in invoice.lines"
                  :key="line.id"
                  class="hover:bg-gray-50"
                >
                  <td class="px-4 py-3 text-gray-900">{{ line.label }}</td>
                  <td class="px-4 py-3">
                    <Badge :variant="line.type === 'service' ? 'info' : 'default'">
                      {{ line.type === 'service' ? 'Main d\'œuvre' : 'Pièce' }}
                    </Badge>
                  </td>
                  <td class="px-4 py-3 text-right text-gray-600">{{ line.quantity }}</td>
                  <td class="px-4 py-3 text-right text-gray-600">{{ fmt(line.unit_price) }}</td>
                  <td class="px-4 py-3 text-right font-medium text-gray-900">{{ fmt(line.total) }}</td>
                  <td v-if="invoice.can_edit" class="px-4 py-3 text-right">
                    <Button
                      variant="ghost"
                      size="sm"
                      :loading="deletingLineId === line.id"
                      @click="deleteLine(line.id)"
                      class="text-red-500 hover:text-red-700 hover:bg-red-50"
                    >
                      ✕
                    </Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>

        <!-- Sidebar totaux -->
        <div class="space-y-4">
          <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Totaux</p>

            <div class="flex justify-between text-sm text-gray-600">
              <span>Total HT</span>
              <span>{{ fmt(invoice.total_ht) }}</span>
            </div>
            <div class="flex justify-between text-sm text-gray-600">
              <span>TVA ({{ invoice.tax_rate }}%)</span>
              <span>{{ fmt(invoice.tax_amount) }}</span>
            </div>
            <div class="flex justify-between text-base font-bold text-gray-900 border-t pt-3">
              <span>Total TTC</span>
              <span>{{ fmt(invoice.total_ttc) }}</span>
            </div>
          </div>

          <div v-if="invoice.notes" class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Notes</p>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ invoice.notes }}</p>
          </div>
        </div>

      </div>
    </div>

    <!-- Modal ajout ligne -->
    <Modal :show="showLineModal" title="Ajouter une ligne" max-width="sm" @close="closeLineModal">
      <form @submit.prevent="submitLine" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
          <Select v-model="lineForm.type" :options="lineTypeOptions" :error="lineForm.errors.type" />
        </div>
        <div v-if="lineForm.type === 'service'">
          <label class="block text-sm font-medium text-gray-700 mb-1">Désignation *</label>
          <Input v-model="lineForm.label" placeholder="Main d'œuvre iPhone 14..." :error="lineForm.errors.label" />
        </div>

        <template v-else>
          <div v-if="!lineForm.part_id">
            <label class="block text-sm font-medium text-gray-700 mb-1">Pièce *</label>
            <p v-if="!depotActive" class="text-xs text-amber-600">
              Sélectionnez un dépôt actif (en haut) pour choisir une pièce en stock.
            </p>
            <PartStockPicker v-else :depot-id="depotActive.id" @select="selectPartForLine" />
            <p v-if="lineForm.errors.part_id" class="text-xs text-red-600 mt-1">{{ lineForm.errors.part_id }}</p>
          </div>
          <div v-else class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 bg-gray-50">
            <span class="text-sm font-medium text-gray-900">{{ lineForm.label }}</span>
            <button type="button" class="text-xs text-indigo-600 hover:underline" @click="lineForm.part_id = null; maxQuantity = null">
              Changer
            </button>
          </div>
        </template>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Quantité *</label>
            <Input v-model="lineForm.quantity" type="number" min="1" :max="maxQuantity ?? undefined" :error="lineForm.errors.quantity" />
            <p v-if="maxQuantity != null" class="text-xs text-gray-400 mt-1">{{ maxQuantity }} en stock</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Prix unitaire HT *</label>
            <Input v-model="lineForm.unit_price" type="number" step="0.01" :error="lineForm.errors.unit_price" />
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button variant="secondary" @click="closeLineModal">Annuler</Button>
          <Button
            type="submit"
            :loading="lineForm.processing"
            :disabled="lineForm.type === 'part' && !lineForm.part_id"
          >
            Ajouter
          </Button>
        </div>
      </form>
    </Modal>

  </AppLayout>
</template>