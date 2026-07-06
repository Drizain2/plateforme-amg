<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import ImpersonationController from '@/actions/App/Http/Controllers/Admin/ImpersonationController'
import AdminPaymentController from '@/actions/App/Http/Controllers/Admin/PaymentController'
import Badge from '@/Components/UI/Badge.vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import type { Payment, Plan, Shop } from '@/types'

interface Kpis {
  mrr: number
  arr: number
  shops: { total: number; active: number; trial: number; churned: number; suspended: number }
  conversionRate: number
  churnedThisMonth: number
  pendingPaymentsCount: number
}

interface WeekData {
  label: string
  count: number
}

interface ShopWithAdmin extends Shop {
  plan?: Plan
  admin?: { id: number; name: string; email: string } | null
}

const props = defineProps<{
  kpis: Kpis
  acquisition: WeekData[]
  pendingPayments: (Payment & { shop?: Shop; plan?: Plan })[]
  recentShops: ShopWithAdmin[]
}>()

const fmt = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v)

const fmtDate = (d: string) =>
  new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })

const maxCount = Math.max(...props.acquisition.map((w) => w.count), 1)

const statusBadge = (status: Payment['status']) =>
  ({
    pending: { variant: 'warning' as const, label: 'En attente' },
    validated: { variant: 'success' as const, label: 'Validé' },
    rejected: { variant: 'danger' as const, label: 'Rejeté' },
    refunded: { variant: 'default' as const, label: 'Remboursé' },
  })[status]
</script>

<template>
  <AdminLayout title="Tableau de bord">
    <!-- ── KPI cards ──────────────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

      <!-- MRR -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">MRR</p>
        <p class="text-2xl font-bold text-gray-900">{{ fmt(kpis.mrr) }}</p>
        <p class="text-xs text-gray-400 mt-1">ARR : {{ fmt(kpis.arr) }}</p>
      </div>

      <!-- Ateliers -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Ateliers</p>
        <p class="text-2xl font-bold text-gray-900 mb-2">{{ kpis.shops.total }}</p>
        <div class="flex flex-wrap gap-1 text-xs">
          <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">{{ kpis.shops.active }} actifs</span>
          <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">{{ kpis.shops.trial }} essai</span>
          <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium">{{ kpis.shops.churned }} churned</span>
          <span class="bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-medium">{{ kpis.shops.suspended }} suspendus</span>
        </div>
      </div>

      <!-- Conversion -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Conversion</p>
        <p class="text-2xl font-bold text-gray-900">{{ kpis.conversionRate }}%</p>
        <p class="text-xs text-gray-400 mt-1">Essai → payant</p>
      </div>

      <!-- Paiements en attente -->
      <div
        class="rounded-xl border p-5 transition"
        :class="kpis.pendingPaymentsCount > 0
          ? 'bg-amber-50 border-amber-300'
          : 'bg-white border-gray-200'"
      >
        <p class="text-xs font-medium uppercase tracking-wide mb-1"
          :class="kpis.pendingPaymentsCount > 0 ? 'text-amber-600' : 'text-gray-400'">
          Paiements en attente
        </p>
        <p class="text-2xl font-bold" :class="kpis.pendingPaymentsCount > 0 ? 'text-amber-700' : 'text-gray-900'">
          {{ kpis.pendingPaymentsCount }}
        </p>
        <Link
          v-if="kpis.pendingPaymentsCount > 0"
          :href="AdminPaymentController.index.url()"
          class="text-xs font-medium text-amber-600 hover:text-amber-700 mt-1 inline-block"
        >
          Voir les paiements →
        </Link>
        <p v-else class="text-xs text-gray-400 mt-1">Churn ce mois : {{ kpis.churnedThisMonth }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

      <!-- ── Graphe acquisition ──────────────────────────────────────────── -->
      <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Nouveaux ateliers — 12 dernières semaines</h2>
        <div class="flex items-end gap-1.5 h-32">
          <div
            v-for="(week, i) in acquisition"
            :key="i"
            class="flex-1 flex flex-col items-center gap-1 group"
          >
            <span class="text-xs text-gray-400 opacity-0 group-hover:opacity-100 transition">{{ week.count }}</span>
            <div
              class="w-full rounded-t bg-indigo-500 transition-all duration-300"
              :style="{ height: `${Math.max(week.count / maxCount * 100, week.count > 0 ? 4 : 0)}%` }"
              :class="week.count === 0 ? 'bg-gray-100' : 'bg-indigo-500'"
            />
            <span class="text-xs text-gray-400 rotate-45 origin-left mt-1 hidden sm:block">{{ week.label }}</span>
          </div>
        </div>
      </div>

      <!-- ── Paiements en attente ────────────────────────────────────────── -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-gray-700">Paiements en attente</h2>
          <Link :href="AdminPaymentController.index.url()" class="text-xs text-indigo-600 hover:underline">Tout voir</Link>
        </div>

        <div v-if="pendingPayments.length === 0" class="px-5 py-8 text-center text-sm text-gray-400">
          Aucun paiement en attente
        </div>

        <ul v-else class="divide-y divide-gray-50">
          <li v-for="payment in pendingPayments" :key="payment.id" class="px-5 py-3">
            <div class="flex items-center justify-between gap-2">
              <div class="min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">{{ payment.shop?.name ?? '—' }}</p>
                <p class="text-xs text-gray-400 font-mono">{{ payment.reference }}</p>
              </div>
              <div class="shrink-0 text-right">
                <p class="text-sm font-semibold text-gray-700">
                  {{ new Intl.NumberFormat('fr-FR').format(payment.amount) }}
                </p>
                <p class="text-xs text-gray-400">{{ payment.plan?.name }}</p>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- ── Ateliers récents + impersonation ──────────────────────────────── -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="text-sm font-semibold text-gray-700">Ateliers récents</h2>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Atelier</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Plan</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Statut</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Créé le</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Admin</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="shop in recentShops" :key="shop.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-800">{{ shop.name }}</td>
              <td class="px-4 py-3 text-gray-500">{{ shop.plan?.name ?? '—' }}</td>
              <td class="px-4 py-3">
                <Badge v-if="!shop.is_active" variant="danger">Suspendu</Badge>
                <Badge v-else-if="shop.trial_ends_at && new Date(shop.trial_ends_at) > new Date()" variant="info">Essai</Badge>
                <Badge v-else variant="success">Actif</Badge>
              </td>
              <td class="px-4 py-3 text-gray-400 text-xs">{{ fmtDate(shop.created_at as string) }}</td>
              <td class="px-4 py-3 text-gray-500 text-xs">{{ shop.admin?.name ?? '—' }}</td>
              <td class="px-4 py-3">
                <Link
                  v-if="shop.admin"
                  :href="ImpersonationController.start.url({ shop: shop.id })"
                  method="post"
                  as="button"
                  class="text-xs font-medium text-indigo-600 hover:text-indigo-800 transition"
                >
                  Simuler
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>
