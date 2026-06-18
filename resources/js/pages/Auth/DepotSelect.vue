<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/Components/UI/Button.vue'
import type { Depot } from '@/types/models'

const props = defineProps<{
  depots: Pick<Depot, 'id' | 'name' | 'address'>[]
}>()

const form = useForm({ depot_id: null as number | null })

function select(depotId: number) {
  form.depot_id = depotId
  form.post('/depot/select')
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
      <div class="text-center mb-8">
        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center mx-auto mb-4">
          <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900">Choisissez votre dépôt</h1>
        <p class="text-sm text-gray-500 mt-1">Sélectionnez le dépôt sur lequel vous souhaitez travailler</p>
      </div>

      <div class="space-y-3">
        <button
          v-for="depot in props.depots"
          :key="depot.id"
          type="button"
          :disabled="form.processing"
          class="w-full text-left px-5 py-4 rounded-xl border-2 border-gray-200 hover:border-indigo-400 hover:bg-indigo-50 transition-all group disabled:opacity-50"
          @click="select(depot.id)"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="font-semibold text-gray-900 group-hover:text-indigo-700">{{ depot.name }}</p>
              <p v-if="depot.address" class="text-sm text-gray-500 mt-0.5">{{ depot.address }}</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </button>
      </div>

      <div v-if="form.errors.depot_id" class="mt-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
        {{ form.errors.depot_id }}
      </div>
    </div>
  </div>
</template>
