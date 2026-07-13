<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import PurchaseController from '@/actions/App/Http/Controllers/PurchaseController'
import PartStockPicker from '@/Components/Stock/PartStockPicker.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Select from '@/Components/UI/Select.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { StockSearchResult } from '@/types'

interface SimpleSupplier { id: number; name: string }

const props = defineProps<{
  suppliers: SimpleSupplier[]
}>()

interface Line {
  label: string
  quantity: number
  unit_price: number
  part_id: number
  alert_quantity: number | ''
}

const page = usePage()
const depotActive = computed(() => page.props.auth.depotActive)

const form = useForm({
  supplier_id: '' as number | '',
  tax_rate: 0,
  notes: '',
  lines: [] as Line[],
})

const supplierOptions = computed(() => props.suppliers.map(s => ({ value: s.id, label: s.name })))

function addPartFromStock(result: StockSearchResult) {
  const existing = form.lines.find(l => l.part_id === result.id)

  if (existing) {
    existing.quantity++

    return
  }

  form.lines.push({
    part_id: result.id,
    label: result.name,
    quantity: 1,
    unit_price: result.unit_price,
    alert_quantity: result.alert_quantity ?? '',
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

  form.post(PurchaseController.store.url(), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      if (page.props.flash.error) {
        submitError.value = page.props.flash.error
      }
    },
  })
}
</script>

<template>
  <AppLayout title="Nouvel achat">
    <div class="w-full px-4 space-y-6">

      <!-- Header -->
      <div class="flex items-center gap-3">
        <Button variant="ghost" size="sm" @click="router.visit(PurchaseController.index.url())">
          ← Retour
        </Button>
        <h1 class="text-xl font-semibold text-gray-900">Nouvel achat</h1>
      </div>

      <div v-if="submitError" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        {{ submitError }}
      </div>

      <div v-if="!depotActive" class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-700">
        Sélectionnez un dépôt actif (en haut) pour créer un achat — le stock sera reçu dans ce dépôt.
      </div>

      <div v-else class="flex flex-row gap-6 items-start">

        <!-- Gauche : achat -->
        <form @submit.prevent="submit" class="space-y-6 w-4/3">

          <!-- Infos générales -->
          <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700">Informations générales</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Fournisseur *</label>
                <Select
                  v-model="form.supplier_id"
                  :options="supplierOptions"
                  placeholder="Sélectionner un fournisseur"
                  :error="form.errors.supplier_id"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">TVA (%)</label>
                <Input v-model="form.tax_rate" type="number" step="0.01" :error="form.errors.tax_rate" />
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
            <h2 class="text-sm font-semibold text-gray-700">Articles commandés</h2>

            <div v-if="form.lines.length === 0" class="text-center text-sm text-gray-400 py-6">
              Cliquez sur un article du catalogue à droite pour l'ajouter.
            </div>

           <div v-else class="space-y-2">
  <div class="grid grid-cols-12 gap-2 text-xs font-medium text-gray-500 uppercase tracking-wide px-1">
    <div class="col-span-5">Désignation</div>
    <div class="col-span-1">Seuil</div>
    <div class="col-span-2">Qté</div>
    <div class="col-span-2">Prix unit.</div>
    <div class="col-span-1">Total</div>
    <div class="col-span-12 sm:col-span-1" />
  </div>

  <div
    v-for="(line, i) in form.lines"
    :key="i"
    class="grid grid-cols-12 gap-2 items-center pb-2 border-b border-gray-50 last:border-0"
  >
    <p class="col-span-5 text-sm font-medium text-gray-900 px-1 truncate">{{ line.label }}</p>
    <div class="col-span-1 min-w-0">
      <Input v-model.number="line.alert_quantity" type="number" min="0" placeholder="Non modifié" class="w-full" />
    </div>
    <div class="col-span-2 min-w-0">
      <Input v-model.number="line.quantity" type="number" min="1" class="w-full" />
    </div>
    <div class="col-span-2 min-w-0">
      <Input v-model.number="line.unit_price" type="number" step="100" min="0" class="w-full" />
    </div>
    <div class="col-span-1 text-sm text-gray-700 font-medium whitespace-nowrap">
      {{ fmtXof(line.quantity * line.unit_price) }}
    </div>
    <button type="button" @click="removeLine(i)"
      class="col-span-12 sm:col-span-1 text-right text-gray-400 hover:text-red-500 transition text-lg leading-none">×</button>
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
            <Button variant="secondary" type="button" @click="router.visit(PurchaseController.index.url())">
              Annuler
            </Button>
            <Button type="submit" :loading="form.processing" :disabled="form.lines.length === 0">
              Créer l'achat
            </Button>
          </div>

        </form>

        <!-- Droite : catalogue -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4 lg:sticky lg:top-6 w-2/3">
          <h2 class="text-sm font-semibold text-gray-700">Catalogue d'articles</h2>
          <PartStockPicker :depot-id="depotActive.id" mode="purchase" @select="addPartFromStock" />
        </div>

      </div>
    </div>
  </AppLayout>
</template>
