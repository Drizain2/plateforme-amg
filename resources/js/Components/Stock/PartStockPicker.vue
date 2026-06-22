<!-- resources/js/Components/Stock/PartStockPicker.vue -->
<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import PartController from '@/actions/App/Http/Controllers/Stock/PartController'
import Input from '@/Components/UI/Input.vue'
import type { StockSearchResult } from '@/types'

const props = defineProps<{ depotId?: number | null; mode?: 'sale' | 'purchase' }>()
const emit = defineEmits<{ select: [StockSearchResult] }>()

const search = ref('')
const results = ref<StockSearchResult[]>([])

async function runSearch() {
  const res = await fetch(PartController.search.url({ query: { q: search.value, depot_id: props.depotId ?? undefined, mode: props.mode ?? 'sale' } }), {
    headers: { Accept: 'application/json' },
  })
  results.value = await res.json()
}

const priceOf = (r: StockSearchResult) => props.mode === 'purchase' ? r.unit_price : r.sell_price

const isLossMaking = (r: StockSearchResult) =>
  props.mode !== 'purchase' && r.avg_cost_price > 0 && r.sell_price < r.avg_cost_price

const fmtXof = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(v)

const emptyLabel = computed(() => props.mode === 'purchase' ? 'Aucune pièce trouvée' : 'Aucune pièce en stock')

onMounted(runSearch)
watch(() => props.depotId, runSearch)

defineExpose({ reload: runSearch })
</script>

<template>
  <div class="space-y-3">
    <Input v-model="search" placeholder="Rechercher nom, SKU..." @input="runSearch" />

    <div v-if="results.length === 0" class="text-center text-sm text-gray-400 py-8">
      {{ emptyLabel }}
    </div>

    <div v-else class="space-y-1.5">
      <button
        v-for="r in results"
        :key="`${r.id}-${r.depot_id}`"
        type="button"
        class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-lg border border-gray-100 hover:border-indigo-300 hover:bg-indigo-50 transition text-left disabled:opacity-50 disabled:cursor-not-allowed"
        :disabled="mode !== 'purchase' && r.quantity === 0"
        @click="emit('select', r)"
      >
        <div class="min-w-0">
          <p class="text-sm font-medium text-gray-900 truncate">{{ r.name }}</p>
          <p class="text-xs text-gray-400 font-mono">{{ r.sku ?? '—' }}</p>
        </div>
        <div class="text-right shrink-0">
          <p class="text-xs text-gray-500">{{ r.quantity }} en stock</p>
          <p
            class="text-sm font-medium"
            :class="isLossMaking(r) ? 'text-red-600' : 'text-indigo-600'"
            :title="isLossMaking(r) ? `Coût moyen : ${fmtXof(r.avg_cost_price)}` : undefined"
          >
            {{ fmtXof(priceOf(r)) }}
            <span v-if="isLossMaking(r)">⚠</span>
          </p>
        </div>
      </button>
    </div>
  </div>
</template>
