<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AdminPaymentController from '@/actions/App/Http/Controllers/Admin/PaymentController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import type { Payment } from '@/types'
import type { PaginatedResponse } from '@/types/pagination'

defineProps<{
  payments: PaginatedResponse<Payment>
  filters: { status?: string; search?: string }
}>()

const fmt = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v)

const fmtDate = (d: string) =>
  new Date(d).toLocaleDateString('fr-FR', { year: 'numeric', month: 'short', day: 'numeric' })

const statusBadge = (status: Payment['status']) =>
  ({
    pending: { variant: 'warning' as const, label: 'En attente' },
    validated: { variant: 'success' as const, label: 'Validé' },
    rejected: { variant: 'danger' as const, label: 'Rejeté' },
    refunded: { variant: 'default' as const, label: 'Remboursé' },
  })[status]

// ── Filtres ───────────────────────────────────────────────────────────────────
const search = ref('')
const statusFilter = ref('')

function applyFilter() {
  router.get(AdminPaymentController.index.url(), {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
  }, { preserveState: true, replace: true })
}

// ── Approbation ───────────────────────────────────────────────────────────────
const approving = ref<number | null>(null)

function approve(payment: Payment) {
  if (!confirm(`Valider le paiement ${payment.reference} ?`)) {
    return
  }

  approving.value = payment.id
  router.post(AdminPaymentController.approve.url(payment.id), {}, {
    preserveScroll: true,
    onFinish: () => { approving.value = null },
  })
}

// ── Rejet ─────────────────────────────────────────────────────────────────────
const rejectingPayment = ref<Payment | null>(null)
const rejectForm = useForm({ reason: '' })

function openReject(payment: Payment) {
  rejectingPayment.value = payment
  rejectForm.reset()
}

function submitReject() {
  if (!rejectingPayment.value) {
    return
  }

  rejectForm.post(AdminPaymentController.reject.url(rejectingPayment.value.id), {
    preserveScroll: true,
    onSuccess: () => { rejectingPayment.value = null },
  })
}
</script>

<template>
  <AdminLayout title="Paiements">
    <div class="space-y-6">

      <!-- Filtres -->
      <div class="flex gap-3 items-center">
        <Input v-model="search" placeholder="Référence ou atelier…" class="w-64" @keyup.enter="applyFilter" />
        <select v-model="statusFilter"
          class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
          @change="applyFilter">
          <option value="">Tous les statuts</option>
          <option value="pending">En attente</option>
          <option value="validated">Validés</option>
          <option value="rejected">Rejetés</option>
        </select>
        <Button variant="secondary" size="sm" @click="applyFilter">Filtrer</Button>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Référence</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Atelier</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Plan</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Montant</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Période</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Statut</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Date</th>
                <th class="px-4 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="payments.data.length === 0">
                <td colspan="8" class="px-4 py-12 text-center text-gray-400">Aucun paiement</td>
              </tr>
              <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ payment.reference }}</td>
                <td class="px-4 py-3 text-gray-700">{{ payment.shop?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ payment.plan?.name ?? '—' }}</td>
                <td class="px-4 py-3 font-medium text-gray-900">{{ fmt(payment.amount) }}</td>
                <td class="px-4 py-3 text-gray-500 text-xs">
                  {{ payment.billing_period === 'annual' ? 'Annuelle' : 'Mensuelle' }}
                </td>
                <td class="px-4 py-3">
                  <Badge :variant="statusBadge(payment.status).variant">
                    {{ statusBadge(payment.status).label }}
                  </Badge>
                </td>
                <td class="px-4 py-3 text-gray-400 text-xs">{{ fmtDate(payment.created_at) }}</td>
                <td class="px-4 py-3">
                  <div v-if="payment.status === 'pending'" class="flex items-center gap-1 justify-end">
                    <Button
                      variant="ghost" size="sm"
                      class="text-green-600 hover:text-green-800 hover:bg-green-50"
                      :loading="approving === payment.id"
                      @click="approve(payment)"
                    >
                      Valider
                    </Button>
                    <Button
                      variant="ghost" size="sm"
                      class="text-red-500 hover:text-red-700 hover:bg-red-50"
                      @click="openReject(payment)"
                    >
                      Rejeter
                    </Button>
                  </div>
                  <div v-else-if="payment.status === 'validated'" class="text-xs text-gray-400">
                    par {{ payment.validated_by?.name ?? '—' }}
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- Modal rejet -->
    <Modal :show="!!rejectingPayment" title="Motif de rejet" @close="rejectingPayment = null">
      <form @submit.prevent="submitReject" class="space-y-4">
        <p class="text-sm text-gray-600">
          Indiquez la raison du rejet pour le paiement
          <span class="font-mono font-medium">{{ rejectingPayment?.reference }}</span>.
        </p>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Motif *</label>
          <Input v-model="rejectForm.reason" :error="rejectForm.errors.reason" autofocus />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button variant="secondary" type="button" @click="rejectingPayment = null">Annuler</Button>
          <Button type="submit" :loading="rejectForm.processing" class="bg-red-600 hover:bg-red-700">Rejeter</Button>
        </div>
      </form>
    </Modal>
  </AdminLayout>
</template>
