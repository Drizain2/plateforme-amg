<!-- resources/js/Pages/Dashboard/Index.vue -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import StockMovementController from '@/actions/App/Http/Controllers/Stock/StockMovementController'
import TicketController from '@/actions/App/Http/Controllers/Ticket/TicketController'
import DonutChart from '@/Components/Dashboard/DonutChart.vue'
import LineChart from '@/Components/Dashboard/LineChart.vue'
import StatCard from '@/Components/Dashboard/StatCard.vue'
import type { BadgeVariant } from '@/Components/UI/Badge.vue';
import Badge from '@/Components/UI/Badge.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { DashboardStats, DashboardCharts, DashboardRecent, DashboardAlerts } from '@/types'

const props = defineProps<{
  stats:   DashboardStats
  charts:  DashboardCharts
  recent:  DashboardRecent
  alerts:  DashboardAlerts
}>()

const formatCurrency = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(v)

const lineLabels = computed(() => props.charts.tickets_by_day.map(d => d.label))
const lineValues = computed(() => props.charts.tickets_by_day.map(d => d.total))

const statusLabels = computed(() => props.charts.tickets_by_status.map(d => d.status))
const statusValues = computed(() => props.charts.tickets_by_status.map(d => d.total))

const depotLabels  = computed(() => props.charts.tickets_by_depot.map(d => d.depot))
const depotValues  = computed(() => props.charts.tickets_by_depot.map(d => d.total))
</script>

<template>
  <AppLayout title="Dashboard">
    <div class="space-y-6">

      <h1 class="text-xl font-semibold text-gray-900">Tableau de bord</h1>

      <!-- KPIs -->
      <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <StatCard
          label="Tickets ouverts"
          :value="stats.tickets_open"
          sub="en cours"
        />
        <StatCard
          label="Créés aujourd'hui"
          :value="stats.tickets_today"
        />
        <StatCard
          label="Terminés ce mois"
          :value="stats.tickets_done_month"
          variant="success"
        />
        <StatCard
          label="Stock critique"
          :value="stats.low_stock_count"
          sub="pièces sous seuil"
          :variant="stats.low_stock_count > 0 ? 'danger' : 'default'"
        />
        <StatCard
          label="CA ce mois"
          :value="formatCurrency(stats.revenue_month)"
          variant="success"
        />
        <StatCard
          label="Délai moyen"
          :value="`${stats.avg_repair_days}j`"
          sub="de réparation"
        />
      </div>

      <!-- Alertes retard -->
      <div
        v-if="alerts.overdue.length"
        class="bg-red-50 border border-red-200 rounded-xl p-4 space-y-2"
      >
        <p class="text-sm font-semibold text-red-700">
          ⚠️ {{ alerts.overdue.length }} ticket{{ alerts.overdue.length > 1 ? 's' : '' }} en retard
        </p>
        <div class="flex flex-wrap gap-2">
          <Link
            v-for="t in alerts.overdue"
            :key="t.id"
            :href="TicketController.show.url(t.id)"
            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-medium hover:bg-red-200 transition"
          >
            {{ t.reference }} — {{ t.customer_name }}
            <span class="font-bold">+{{ t.overdue_days }}j</span>
          </Link>
        </div>
      </div>

      <!-- Graphiques -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
          <p class="text-sm font-medium text-gray-700 mb-4">Tickets créés — 30 derniers jours</p>
          <LineChart :labels="lineLabels" :values="lineValues" />
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <p class="text-sm font-medium text-gray-700 mb-4">Répartition par statut</p>
          <DonutChart :labels="statusLabels" :values="statusValues" />
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <p class="text-sm font-medium text-gray-700 mb-4">Tickets par dépôt</p>
          <DonutChart :labels="depotLabels" :values="depotValues" />
        </div>

      </div>

      <!-- Tableaux récents -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Derniers tickets -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <p class="text-sm font-medium text-gray-700">Derniers tickets</p>
            <Link :href="TicketController.index.url()" class="text-xs text-indigo-600 hover:underline">
              Voir tout
            </Link>
          </div>
          <div class="divide-y divide-gray-100">
            <div
              v-for="t in recent.tickets"
              :key="t.id"
              class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 transition"
            >
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <Link
                    :href="TicketController.show.url(t.id)"
                    class="text-sm font-mono font-medium text-gray-900 hover:text-indigo-600 transition"
                  >
                    {{ t.reference }}
                  </Link>
                  <Badge :variant="t.status_color as BadgeVariant" class="shrink-0">
                    {{ t.status_label }}
                  </Badge>
                </div>
                <p class="text-xs text-gray-400 mt-0.5 truncate">
                  {{ t.customer_name }} · {{ t.device_name }}
                </p>
              </div>
              <span class="text-xs text-gray-400 shrink-0 ml-3">{{ t.created_at }}</span>
            </div>
            <div v-if="!recent.tickets.length" class="px-5 py-8 text-center text-sm text-gray-400">
              Aucun ticket
            </div>
          </div>
        </div>

        <!-- Stock critique -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <p class="text-sm font-medium text-gray-700">Stock critique</p>
            <Link :href="StockMovementController.alerts.url()" class="text-xs text-indigo-600 hover:underline">
              Voir tout
            </Link>
          </div>
          <div class="divide-y divide-gray-100">
            <div
              v-for="p in recent.low_stock"
              :key="p.id"
              class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 transition"
            >
              <div class="min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ p.part_name }}</p>
                <p class="text-xs text-gray-400">{{ p.depot_name }}</p>
              </div>
              <div class="text-right shrink-0 ml-3">
                <span
                  class="text-sm font-bold"
                  :class="p.quantity === 0 ? 'text-red-600' : 'text-yellow-600'"
                >
                  {{ p.quantity }}
                </span>
                <span class="text-xs text-gray-400"> / {{ p.alert_quantity }}</span>
              </div>
            </div>
            <div v-if="!recent.low_stock.length" class="px-5 py-8 text-center text-sm text-gray-400">
              Aucune alerte stock
            </div>
          </div>
        </div>

      </div>

    </div>
  </AppLayout>
</template>