<!-- resources/js/pages/Stock/Counts/Show.vue -->
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import StockCountController from '@/actions/App/Http/Controllers/Stock/StockCountController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { BadgeVariant, StockCount } from '@/types'

const props = defineProps<{ stockCount: StockCount }>()

const isDraft = computed(() => props.stockCount.status === 'draft')

const form = useForm({
  lines: (props.stockCount.lines ?? []).map(l => ({
    id: l.id,
    counted_quantity: l.counted_quantity,
    note: l.note ?? '',
  })),
})

function difference(lineId: number): number | null {
  const line = form.lines.find(l => l.id === lineId)

  if (!line || line.counted_quantity === null) {
    return null
  }

  const original = props.stockCount.lines?.find(l => l.id === lineId)

  return original ? line.counted_quantity - original.expected_quantity : null
}

function saveCounts() {
  form.transform(data => ({ lines: data.lines })).put(StockCountController.update.url(props.stockCount.id), {
    preserveScroll: true,
  })
}

function validateCount() {
  if (!confirm('Valider l\'inventaire ? Les écarts entre quantité comptée et quantité attendue seront appliqués au stock (ajustements). Cette action est définitive.')) {
    return
  }

  useForm({}).post(StockCountController.validateCount.url(props.stockCount.id), {
    preserveScroll: true,
  })
}

const uncountedCount = computed(() =>
  form.lines.filter(l => l.counted_quantity === null).length
)

const fmtXof = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(v)
</script>

<template>
  <AppLayout :title="`Inventaire ${stockCount.number}`">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-start justify-between">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-xl font-semibold text-gray-900 font-mono">{{ stockCount.number }}</h1>
            <Badge :variant="stockCount.status_color as BadgeVariant">{{ stockCount.status_label }}</Badge>
          </div>
          <p class="text-sm text-gray-400 mt-1">
            Dépôt {{ stockCount.depot?.name }} · démarré le {{ stockCount.started_at }}
            <span v-if="stockCount.user"> par {{ stockCount.user.name }}</span>
          </p>
          <p v-if="stockCount.note" class="text-sm text-gray-500 mt-1">{{ stockCount.note }}</p>
        </div>

        <div v-if="isDraft" class="flex gap-2">
          <Button variant="secondary" :loading="form.processing" @click="saveCounts">
            Enregistrer le comptage
          </Button>
          <Button @click="validateCount">
            Valider l'inventaire
          </Button>
        </div>
      </div>

      <p v-if="isDraft && uncountedCount > 0" class="text-xs text-amber-600">
        {{ uncountedCount }} ligne{{ uncountedCount > 1 ? 's' : '' }} non comptée{{ uncountedCount > 1 ? 's' : '' }} — elles seront ignorées à la validation (aucun ajustement appliqué).
      </p>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Pièce</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Attendu</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Compté</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Écart</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">CMP</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Note</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="!stockCount.lines?.length">
                <td colspan="6" class="px-4 py-12 text-center text-gray-400">Aucune ligne</td>
              </tr>
              <tr v-for="(original, i) in stockCount.lines" :key="original.id" class="hover:bg-gray-50">
                <td class="px-4 py-3">
                  <p class="text-gray-900 font-medium">{{ original.part?.name ?? '—' }}</p>
                  <p v-if="original.part?.sku" class="text-gray-400 font-mono text-xs">{{ original.part.sku }}</p>
                </td>
                <td class="px-4 py-3 text-right text-gray-600">{{ original.expected_quantity }}</td>
                <td class="px-4 py-3 text-right">
                  <Input
                    v-if="isDraft"
                    :model-value="form.lines[i].counted_quantity ?? ''"
                    @update:model-value="(v) => form.lines[i].counted_quantity = v === '' ? null : Number(v)"
                    type="number"
                    min="0"
                    class="w-24 text-right"
                  />
                  <span v-else class="text-gray-700">{{ original.counted_quantity ?? '—' }}</span>
                </td>
                <td class="px-4 py-3 text-right font-semibold"
                  :class="{
                    'text-green-600': (difference(original.id) ?? 0) > 0,
                    'text-red-600': (difference(original.id) ?? 0) < 0,
                    'text-gray-400': difference(original.id) === null || difference(original.id) === 0,
                  }"
                >
                  {{ difference(original.id) === null ? '—' : (difference(original.id)! > 0 ? '+' : '') + difference(original.id) }}
                </td>
                <td class="px-4 py-3 text-right text-gray-500 text-xs">{{ fmtXof(original.unit_cost) }}</td>
                <td class="px-4 py-3">
                  <Input
                    v-if="isDraft"
                    v-model="form.lines[i].note"
                    placeholder="Ex: carton endommagé"
                    class="text-xs"
                  />
                  <span v-else class="text-gray-400 text-xs">{{ original.note ?? '—' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
