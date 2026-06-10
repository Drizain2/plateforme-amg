<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import StockMovementController from '@/actions/App/Http/Controllers/Stock/StockMovementController'
import PartController from '@/actions/App/Http/Controllers/Stock/PartController'
import Badge from '@/Components/UI/Badge.vue'
import AppLayout from '@/Layouts/AppLayout.vue'

interface Alert {
  id: number
  quantity: number
  alert_quantity: number
  is_critical: boolean
  part: { id: number; name: string; sku?: string } | null
  depot: { id: number; name: string } | null
}

defineProps<{
  alerts: Alert[]
}>()
</script>

<template>
  <AppLayout title="Alertes stock">
    <div class="space-y-6">

      <!-- Header -->
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Alertes de stock</h1>
        <p class="text-sm text-gray-500 mt-0.5">Pièces en dessous du seuil d'alerte</p>
      </div>

      <!-- Vide -->
      <div v-if="alerts.length === 0" class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <svg class="w-10 h-10 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-gray-500 font-medium">Aucune alerte — tous les stocks sont suffisants.</p>
      </div>

      <!-- Table des alertes -->
      <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Pièce</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Dépôt</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Stock</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Seuil</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Criticité</th>
                <th class="px-4 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="alert in alerts"
                :key="alert.id"
                class="hover:bg-gray-50 transition"
              >
                <td class="px-4 py-3">
                  <div class="font-medium text-gray-900">{{ alert.part?.name ?? '—' }}</div>
                  <div v-if="alert.part?.sku" class="text-xs text-gray-400 font-mono">{{ alert.part.sku }}</div>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ alert.depot?.name ?? '—' }}</td>
                <td class="px-4 py-3">
                  <span class="font-semibold" :class="alert.quantity === 0 ? 'text-red-600' : 'text-amber-600'">
                    {{ alert.quantity }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-500">{{ alert.alert_quantity }}</td>
                <td class="px-4 py-3">
                  <Badge :variant="alert.quantity === 0 ? 'danger' : 'warning'">
                    {{ alert.quantity === 0 ? 'Rupture' : 'Bas' }}
                  </Badge>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-1">
                    <Link
                      v-if="alert.part"
                      :href="StockMovementController.index.url({ query: { part_id: alert.part.id } })"
                    >
                      <button class="text-xs text-indigo-600 hover:underline">Mouvements</button>
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
