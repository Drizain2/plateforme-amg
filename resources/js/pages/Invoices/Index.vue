<!-- resources/js/Pages/Invoices/Index.vue -->
<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Select from '@/Components/UI/Select.vue'
import { useFilters } from '@/Composables/useFilters'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { Invoice, BadgeVariant } from '@/types/models'
import type { PaginatedResource } from '@/types/pagination'

const props = defineProps<{
  invoices: PaginatedResource<Invoice>
  filters:  Record<string, string>
  statuses: { value: string; label: string }[]
  summary:  { total_draft: number; total_sent: number; total_paid: number }
}>()

const { applyFilter } = useFilters(InvoiceController.index.url())

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')
const from   = ref(props.filters.from ?? '')
const to     = ref(props.filters.to ?? '')

watch([search, status, from, to], () => {
  applyFilter({
    search: search.value || undefined,
    status: status.value || undefined,
    from:   from.value   || undefined,
    to:     to.value     || undefined,
  })
})

const fmt = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(v)

function goToPage(url: string | null) {
  if (!url) {
return
}

  router.visit(url, { preserveScroll: true, preserveState: true })
}

const statusOptions = computed(() => props.statuses.map(s => ({ value: s.value, label: s.label })))
</script>

<template>
  <AppLayout title="Facturation">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Facturation</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ invoices.meta.total }} facture{{ invoices.meta.total > 1 ? 's' : '' }}</p>
        </div>
        <Link :href="InvoiceController.create.url()">
          <Button>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouvelle facture
          </Button>
        </Link>
      </div>

      <!-- Summary cards -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-400 uppercase tracking-wide">Brouillons</p>
          <p class="text-2xl font-bold text-gray-700 mt-1">{{ fmt(summary.total_draft) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-400 uppercase tracking-wide">En attente</p>
          <p class="text-2xl font-bold text-blue-600 mt-1">{{ fmt(summary.total_sent) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-400 uppercase tracking-wide">Payées ce mois</p>
          <p class="text-2xl font-bold text-green-600 mt-1">{{ fmt(summary.total_paid) }}</p>
        </div>
      </div>

      <!-- Filtres -->
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
          <Input v-model="search" placeholder="N° facture, client..." />
          <Select v-model="status" :options="statusOptions" placeholder="Tous statuts" />
          <Input v-model="from" type="date" />
          <Input v-model="to"   type="date" />
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Numéro</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Client</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Ticket</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Statut</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Total TTC</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Émise le</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Échéance</th>
                <th class="px-4 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="invoices.data.length === 0">
                <td colspan="8" class="px-4 py-12 text-center text-gray-400">Aucune facture trouvée</td>
              </tr>
              <tr
                v-for="invoice in invoices.data"
                :key="invoice.id"
                class="hover:bg-gray-50 transition"
              >
                <td class="px-4 py-3 font-mono text-xs font-medium text-gray-900">
                  {{ invoice.number }}
                </td>
                <td class="px-4 py-3 text-gray-700">{{ invoice.customer?.name }}</td>
                <td class="px-4 py-3 text-xs text-gray-400 font-mono">
                  {{ invoice.ticket?.reference ?? '—' }}
                </td>
                <td class="px-4 py-3">
                  <Badge :variant="invoice.status_color as BadgeVariant">
                    {{ invoice.status_label }}
                  </Badge>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-gray-900">
                  {{ fmt(invoice.total_ttc) }}
                </td>
                <td class="px-4 py-3 text-gray-400 text-xs">{{ invoice.issued_at ?? '—' }}</td>
                <td class="px-4 py-3 text-xs" :class="invoice.status === 'sent' ? 'text-orange-500 font-medium' : 'text-gray-400'">
                  {{ invoice.due_at ?? '—' }}
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-1">
                    <Link :href="InvoiceController.show.url(invoice.id)">
                      <Button variant="ghost" size="sm">Ouvrir</Button>
                    </Link>
                    <a :href="InvoiceController.pdf.url(invoice.id)" target="_blank">
                      <Button variant="ghost" size="sm">PDF</Button>
                    </a>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="invoices.meta.last_page > 1"
          class="px-4 py-3 border-t flex items-center justify-between text-sm text-gray-600"
        >
          <span>{{ invoices.meta.from }}–{{ invoices.meta.to }} sur {{ invoices.meta.total }}</span>
          <div class="flex gap-1">
            <Button variant="secondary" size="sm" :disabled="!invoices.links.prev" @click="goToPage(invoices.links.prev)">← Précédent</Button>
            <Button variant="secondary" size="sm" :disabled="!invoices.links.next" @click="goToPage(invoices.links.next)">Suivant →</Button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>