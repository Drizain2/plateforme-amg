<!-- resources/js/Components/Stock/StockMovementDrawer.vue -->
<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import StockMovementController from '@/actions/App/Http/Controllers/Stock/StockMovementController';
import Badge from '@/Components/UI/Badge.vue'
import type { Part, StockMovement } from '@/types';

const props = defineProps<{
  show: boolean
  part: Part | null
}>()

defineEmits<{ close: [] }>()

const movements  = ref<StockMovement[]>([])
const loading    = ref(false)
const page       = usePage()

watch(() => props.part, async (part) => {
  if (!part) {
    return
  }

  loading.value = true

  // Requête Inertia "headless" : récupère les props de la page Mouvements
  // filtrée sur cette pièce, sans naviguer ni recharger la page courante.
  const res = await fetch(StockMovementController.index.url({ query: { part_id: part.id } }), {
    headers: {
      'X-Inertia': 'true',
      'X-Inertia-Version': page.version ?? '',
      Accept: 'application/json',
    },
  })
  const data = await res.json()
  movements.value = data.props.movements.data
  loading.value   = false
})

const typeVariant = (type: StockMovement['type']) => ({
  in:           'success',
  out:          'danger',
  adjustment:   'warning',
  transfer_in:  'info',
  transfer_out: 'info',
} as const)[type]
</script>

<template>
  <Teleport to="body">
    <Transition name="drawer">
      <div v-if="show" class="fixed inset-0 z-50 flex justify-end">
        <div class="absolute inset-0 bg-black/40" @click="$emit('close')" />

        <div class="relative w-full max-w-md bg-white shadow-xl flex flex-col">
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b">
            <div>
              <h3 class="font-semibold text-gray-900">Mouvements</h3>
              <p class="text-sm text-gray-500">{{ part?.name }}</p>
            </div>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Content -->
          <div class="flex-1 overflow-y-auto px-6 py-4">
            <div v-if="loading" class="flex justify-center py-8">
              <svg class="w-6 h-6 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
            </div>

            <div v-else-if="movements.length === 0" class="text-center text-gray-400 py-8">
              Aucun mouvement enregistré
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="m in movements"
                :key="m.id"
                class="flex items-start justify-between p-3 rounded-lg border border-gray-100 bg-gray-50"
              >
                <div class="space-y-1">
                  <Badge :variant="typeVariant(m.type)">{{ m.type_label }}</Badge>
                  <p class="text-xs text-gray-500">{{ m.user?.name ?? 'Système' }}</p>
                  <p v-if="m.note" class="text-xs text-gray-400 italic">{{ m.note }}</p>
                  <p v-if="m.transfer_depot" class="text-xs text-gray-500">
                    → {{ m.transfer_depot.name }}
                  </p>
                </div>
                <div class="text-right">
                  <span
                    class="font-semibold text-sm"
                    :class="['in','transfer_in'].includes(m.type) ? 'text-green-600' : 'text-red-600'"
                  >
                    {{ ['in','transfer_in'].includes(m.type) ? '+' : '-' }}{{ m.quantity }}
                  </span>
                  <p class="text-xs text-gray-400 mt-1">{{ m.created_at }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.drawer-enter-active, .drawer-leave-active { transition: transform 0.25s ease; }
.drawer-enter-from, .drawer-leave-to { transform: translateX(100%); }
</style>