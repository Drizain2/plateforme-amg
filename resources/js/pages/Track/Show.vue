<!-- resources/js/pages/Track/Show.vue -->
<script setup lang="ts">
import type { BadgeVariant, TicketEvent } from '@/types'

const props = defineProps<{
  ticket: {
    reference: string
    status: string
    status_label: string
    status_color: BadgeVariant
    description: string
    diagnosis?: string
    estimated_return_date?: string
    device?: string
    shop?: { name: string; phone?: string } | null
    events: (Pick<TicketEvent, 'id' | 'type' | 'note' | 'created_at'> & { by?: string })[]
  }
}>()

const colorMap: Record<BadgeVariant, string> = {
  default: 'bg-gray-100 text-gray-700',
  info: 'bg-blue-100 text-blue-700',
  success: 'bg-green-100 text-green-700',
  warning: 'bg-yellow-100 text-yellow-700',
  danger: 'bg-red-100 text-red-700',
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-xl mx-auto px-4 py-10 space-y-6">

      <div class="text-center">
        <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Suivi de réparation</p>
        <h1 class="text-2xl font-bold text-gray-900">{{ ticket.reference }}</h1>
        <p v-if="ticket.shop" class="text-sm text-gray-500 mt-1">{{ ticket.shop.name }}</p>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
        <div class="flex items-center justify-between">
          <span class="text-sm font-medium text-gray-700">Statut actuel</span>
          <span
            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold"
            :class="colorMap[ticket.status_color]"
          >
            {{ ticket.status_label }}
          </span>
        </div>

        <div v-if="ticket.device" class="flex items-center justify-between text-sm">
          <span class="text-gray-500">Appareil</span>
          <span class="font-medium text-gray-900">{{ ticket.device }}</span>
        </div>

        <div v-if="ticket.estimated_return_date" class="flex items-center justify-between text-sm">
          <span class="text-gray-500">Date de retour estimée</span>
          <span class="font-medium text-gray-900">{{ ticket.estimated_return_date }}</span>
        </div>

        <div v-if="ticket.shop?.phone" class="flex items-center justify-between text-sm">
          <span class="text-gray-500">Contact</span>
          <a :href="`tel:${ticket.shop.phone}`" class="font-medium text-indigo-600 hover:underline">
            {{ ticket.shop.phone }}
          </a>
        </div>
      </div>

      <div v-if="ticket.diagnosis" class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-800">
        <p class="font-medium mb-1">Diagnostic</p>
        <p>{{ ticket.diagnosis }}</p>
      </div>

      <div v-if="ticket.events.length > 0" class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Historique</h2>
        <ol class="relative border-l border-gray-200 space-y-4 ml-2">
          <li
            v-for="event in ticket.events"
            :key="event.id"
            class="pl-5"
          >
            <span class="absolute -left-1.5 mt-1 w-3 h-3 rounded-full bg-indigo-400 border-2 border-white" />
            <p class="text-xs text-gray-400">{{ event.created_at }}<span v-if="event.by"> · {{ event.by }}</span></p>
            <p class="text-sm text-gray-800 mt-0.5">{{ event.note ?? event.type }}</p>
          </li>
        </ol>
      </div>

    </div>
  </div>
</template>
