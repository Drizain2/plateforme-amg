<!-- resources/js/Pages/Dashboard/Index.vue -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import StockMovementController from '@/actions/App/Http/Controllers/Stock/StockMovementController'
import StatCard from '@/Components/Dashboard/StatCard.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { DashboardStats, DashboardRecent } from '@/types'

defineProps<{
  stats:  DashboardStats
  recent: DashboardRecent
}>()
</script>

<template>
  <AppLayout title="Dashboard">
    <div class="space-y-6">

      <h1 class="text-xl font-semibold text-gray-900">Tableau de bord</h1>

      <!-- KPIs -->
      <div class="grid grid-cols-2 gap-4">
        <StatCard
          label="Créés aujourd'hui"
          :value="stats.tickets_today"
          sub="tickets"
        />
        <StatCard
          label="Stock critique"
          :value="stats.low_stock_count"
          sub="références sous seuil"
          :variant="stats.low_stock_count > 0 ? 'danger' : 'default'"
        />
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
            v-for="s in recent.low_stock"
            :key="s.id"
            class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 transition"
          >
            <div class="min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">{{ s.part_name }}</p>
              <p class="text-xs text-gray-400">{{ s.depot_name }}</p>
            </div>
            <div class="text-right shrink-0 ml-3">
              <span
                class="text-sm font-bold"
                :class="s.quantity === 0 ? 'text-red-600' : 'text-yellow-600'"
              >
                {{ s.quantity }}
              </span>
              <span class="text-xs text-gray-400"> / {{ s.alert_quantity }}</span>
            </div>
          </div>
          <div v-if="!recent.low_stock.length" class="px-5 py-8 text-center text-sm text-gray-400">
            Aucune alerte stock
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
