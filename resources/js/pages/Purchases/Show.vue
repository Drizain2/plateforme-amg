<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import PurchaseController from '@/actions/App/Http/Controllers/PurchaseController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { BadgeVariant, Purchase } from '@/types'

const props = defineProps<{ purchase: Purchase }>()

const fmt = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(v)

const transitionForm = useForm({ status: '' })

function submitTransition(status: string) {
  if (!confirm('Confirmer ce changement de statut ?')) {
    return
  }

  transitionForm.status = status
  transitionForm.post(PurchaseController.transition.url(props.purchase.id), {
    preserveScroll: true,
  })
}
</script>

<template>
  <AppLayout :title="`Achat ${purchase.number}`">
    <div class="max-w-4xl space-y-6">

      <!-- Header -->
      <div class="flex items-start justify-between">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-xl font-semibold text-gray-900 font-mono">{{ purchase.number }}</h1>
            <Badge :variant="purchase.status_color as BadgeVariant">{{ purchase.status_label }}</Badge>
          </div>
          <p class="text-sm text-gray-400 mt-1">
            Commandé le {{ purchase.ordered_at }}
            <span v-if="purchase.received_at"> · Reçu le {{ purchase.received_at }}</span>
            <span v-if="purchase.paid_at" class="text-green-600"> · Payé le {{ purchase.paid_at }}</span>
          </p>
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
          <Button
            v-for="s in purchase.next_statuses"
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

          <!-- Infos fournisseur + dépôt -->
          <div class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-2 gap-6">
            <div>
              <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Fournisseur</p>
              <p class="font-medium text-gray-900">{{ purchase.supplier?.name }}</p>
              <p v-if="purchase.supplier?.email" class="text-sm text-gray-500">{{ purchase.supplier.email }}</p>
              <p v-if="purchase.supplier?.phone" class="text-sm text-gray-500">{{ purchase.supplier.phone }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Dépôt de réception</p>
              <p class="font-medium text-gray-900">{{ purchase.depot?.name }}</p>
            </div>
          </div>

          <!-- Lignes -->
          <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b">
              <p class="text-sm font-medium text-gray-700">Articles commandés</p>
            </div>

            <table class="min-w-full divide-y divide-gray-100 text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Désignation</th>
                  <th class="px-4 py-3 text-right text-xs text-gray-500 uppercase">Qté</th>
                  <th class="px-4 py-3 text-right text-xs text-gray-500 uppercase">PU HT</th>
                  <th class="px-4 py-3 text-right text-xs text-gray-500 uppercase">Total HT</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-if="!purchase.lines?.length">
                  <td colspan="4" class="px-4 py-8 text-center text-gray-400">Aucune ligne</td>
                </tr>
                <tr
                  v-for="line in purchase.lines"
                  :key="line.id"
                  class="hover:bg-gray-50"
                >
                  <td class="px-4 py-3 text-gray-900">{{ line.label }}</td>
                  <td class="px-4 py-3 text-right text-gray-600">{{ line.quantity }}</td>
                  <td class="px-4 py-3 text-right text-gray-600">{{ fmt(line.unit_price) }}</td>
                  <td class="px-4 py-3 text-right font-medium text-gray-900">{{ fmt(line.total) }}</td>
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
              <span>{{ fmt(purchase.total_ht) }}</span>
            </div>
            <div class="flex justify-between text-sm text-gray-600">
              <span>TVA ({{ purchase.tax_rate }}%)</span>
              <span>{{ fmt(purchase.tax_amount) }}</span>
            </div>
            <div class="flex justify-between text-base font-bold text-gray-900 border-t pt-3">
              <span>Total TTC</span>
              <span>{{ fmt(purchase.total_ttc) }}</span>
            </div>
          </div>

          <div v-if="purchase.notes" class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Notes</p>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ purchase.notes }}</p>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>
