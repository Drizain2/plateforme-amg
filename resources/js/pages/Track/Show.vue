<!-- resources/js/Pages/Track/Show.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import Button from '@/Components/UI/Button.vue'
import { track } from '@/routes'
import type { TrackingTicket, TrackingStep } from '@/types'

const props = defineProps<{ ticket: TrackingTicket }>()
const fmt = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(v)

const statusColors: Record<string, string> = {
  success: 'bg-green-100 text-green-700 border-green-200',
  info:    'bg-blue-100 text-blue-700 border-blue-200',
  warning: 'bg-yellow-100 text-yellow-800 border-yellow-200',
  danger:  'bg-red-100 text-red-700 border-red-200',
  default: 'bg-gray-100 text-gray-700 border-gray-200',
}

const statusClass = computed(() =>
  statusColors[props.ticket.status_color] ?? statusColors.default
)

function stepClasses(step: TrackingStep) {
  return {
    circle: {
      done:      'bg-indigo-600 border-indigo-600 text-white',
      current:   'bg-white border-indigo-600 text-indigo-600 ring-4 ring-indigo-100',
      pending:   'bg-white border-gray-300 text-gray-300',
      cancelled: 'bg-red-100 border-red-400 text-red-500',
    }[step.state],
    label: {
      done:      'text-indigo-600 font-medium',
      current:   'text-indigo-700 font-semibold',
      pending:   'text-gray-400',
      cancelled: 'text-red-500',
    }[step.state],
    line: {
      done:      'bg-indigo-600',
      current:   'bg-gray-200',
      pending:   'bg-gray-200',
      cancelled: 'bg-gray-200',
    }[step.state],
  }
}

function eventIcon(type: string): string {
  return ({
    status_changed: '🔄',
    note_added:     '📝',
    diagnosis_set:  '🔍',
  } as Record<string, string>)[type] ?? '•'
}

function eventLabel(type: string): string {
  return ({
    status_changed: 'Statut mis à jour',
    note_added:     'Note ajoutée',
    diagnosis_set:  'Diagnostic établi',
  } as Record<string, string>)[type] ?? type
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Header atelier -->
    <header class="bg-white border-b border-gray-200">
      <div class="max-w-2xl mx-auto px-4 py-4 flex items-center justify-between">
        <div>
          <p class="font-bold text-indigo-600 text-lg">{{ ticket.shop.name }}</p>
          <p class="text-xs text-gray-400">Portail de suivi de réparation</p>
        </div>
        <div class="text-right text-xs text-gray-400 space-y-0.5">
          <p v-if="ticket.shop.phone">📞 {{ ticket.shop.phone }}</p>
          <p v-if="ticket.shop.email">✉️ {{ ticket.shop.email }}</p>
        </div>
        <a
          :href="track.url(ticket.tracking_token)"
  target="_blank"
>
  <Button variant="secondary" size="sm">
    🔗 Lien client
  </Button>
</a>
      </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-8 space-y-6">

      <!-- Référence + statut -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
        <div class="flex items-start justify-between flex-wrap gap-3">
          <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Référence</p>
            <p class="text-2xl font-bold font-mono text-gray-900 mt-0.5">{{ ticket.reference }}</p>
            <p class="text-xs text-gray-400 mt-1">Déposé le {{ ticket.created_at }}</p>
          </div>
          <span
            :class="['px-3 py-1.5 rounded-full text-sm font-semibold border', statusClass]"
          >
            {{ ticket.status_label }}
          </span>
        </div>

        <!-- Infos appareil -->
        <div class="bg-gray-50 rounded-xl p-4 flex items-center gap-4">
          <span class="text-3xl">
            {{ ticket.device.type === 'smartphone' ? '📱'
             : ticket.device.type === 'tablette'   ? '📟'
             : ticket.device.type === 'pc'         ? '💻' : '🔧' }}
          </span>
          <div>
            <p class="font-semibold text-gray-900">{{ ticket.device.full_name }}</p>
            <p class="text-sm text-gray-500 capitalize">
              {{ ticket.device.type }}
              <span v-if="ticket.device.color"> · {{ ticket.device.color }}</span>
            </p>
            <p v-if="ticket.device.serial_number" class="text-xs text-gray-400 font-mono">
              {{ ticket.device.serial_number }}
            </p>
          </div>
        </div>

        <!-- Date retour + prix estimé -->
        <div v-if="ticket.estimated_return_date || ticket.estimated_price" class="grid grid-cols-2 gap-3">
          <div v-if="ticket.estimated_return_date" class="bg-indigo-50 rounded-xl p-3 text-center">
            <p class="text-xs text-indigo-400 uppercase tracking-wide">Retour estimé</p>
            <p class="text-base font-bold text-indigo-700 mt-0.5">{{ ticket.estimated_return_date }}</p>
          </div>
          <div v-if="ticket.estimated_price" class="bg-green-50 rounded-xl p-3 text-center">
            <p class="text-xs text-green-400 uppercase tracking-wide">Devis estimé</p>
            <p class="text-base font-bold text-green-700 mt-0.5">{{ fmt(ticket.estimated_price) }}</p>
          </div>
        </div>
      </div>

      <!-- Stepper progression -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <p class="text-sm font-semibold text-gray-700 mb-6">Progression de la réparation</p>

        <div class="relative flex items-start justify-between">
          <!-- Ligne de fond -->
          <div class="absolute top-4 left-0 right-0 h-0.5 bg-gray-200 z-0" />

          <div
            v-for="(step, index) in ticket.steps"
            :key="step.status"
            class="relative z-10 flex flex-col items-center"
            :style="{ width: `${100 / ticket.steps.length}%` }"
          >
            <!-- Ligne colorée gauche -->
            <div
              v-if="index > 0"
              :class="[
                'absolute top-4 right-1/2 h-0.5 w-full -translate-y-px',
                stepClasses(step).line
              ]"
            />

            <!-- Cercle -->
            <div
              :class="[
                'w-8 h-8 rounded-full border-2 flex items-center justify-center text-sm font-bold transition-all',
                stepClasses(step).circle
              ]"
            >
              <svg v-if="step.state === 'done'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
              </svg>
              <svg v-else-if="step.state === 'cancelled'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
              <span v-else class="w-2 h-2 rounded-full"
                :class="step.state === 'current' ? 'bg-indigo-600' : 'bg-gray-300'"
              />
            </div>

            <!-- Label -->
            <p
              :class="['text-xs text-center mt-2 leading-tight px-1', stepClasses(step).label]"
            >
              {{ step.label }}
            </p>
          </div>
        </div>
      </div>

      <!-- Description + diagnostic -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
        <div>
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">
            Problème signalé
          </p>
          <p class="text-sm text-gray-700">{{ ticket.description }}</p>
        </div>

        <div v-if="ticket.diagnosis" class="border-t pt-4">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">
            Diagnostic
          </p>
          <p class="text-sm text-gray-700">{{ ticket.diagnosis }}</p>
        </div>
      </div>

      <!-- Timeline événements -->
      <div v-if="ticket.events.length" class="bg-white rounded-2xl border border-gray-200 p-6">
        <p class="text-sm font-semibold text-gray-700 mb-5">Historique</p>

        <div class="relative space-y-6">
          <!-- Ligne verticale -->
          <div class="absolute left-3.5 top-0 bottom-0 w-px bg-gray-100" />

          <div
            v-for="event in ticket.events"
            :key="event.created_at"
            class="relative flex gap-4"
          >
            <!-- Icône -->
            <div class="w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center text-sm shrink-0 z-10">
              {{ eventIcon(event.type) }}
            </div>

            <div class="flex-1 pb-1">
              <p class="text-sm font-medium text-gray-800">{{ eventLabel(event.type) }}</p>
              <p v-if="event.note" class="text-sm text-gray-600 mt-0.5">{{ event.note }}</p>
              <p class="text-xs text-gray-400 mt-1">{{ event.created_at }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer contact -->
      <div class="text-center text-xs text-gray-400 space-y-1 pb-6">
        <p>Une question ? Contactez {{ ticket.shop.name }}</p>
        <div class="flex items-center justify-center gap-4">
          <a v-if="ticket.shop.phone" :href="`tel:${ticket.shop.phone}`"
            class="text-indigo-600 hover:underline">
            {{ ticket.shop.phone }}
          </a>
          <a v-if="ticket.shop.email" :href="`mailto:${ticket.shop.email}`"
            class="text-indigo-600 hover:underline">
            {{ ticket.shop.email }}
          </a>
        </div>
      </div>

    </main>
  </div>
</template>
