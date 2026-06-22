<!-- resources/js/pages/Stock/Counts/Index.vue -->
<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import StockCountController from '@/actions/App/Http/Controllers/Stock/StockCountController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import { useToast } from '@/Composables/useToast'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { BadgeVariant, PaginatedResource, StockCount } from '@/types'

defineProps<{
  counts: PaginatedResource<StockCount>
}>()

const { success, error } = useToast()
const page = usePage()
const depotActive = computed(() => page.props.auth.depotActive)

watch(() => page.props.flash, (flash) => {
  if (flash.success) {
    success(flash.success)
  }

  if (flash.error) {
    error(flash.error)
  }
}, { immediate: true })

const showStartModal = ref(false)
const startForm = useForm({ note: '' })

function submitStart() {
  startForm.post(StockCountController.store.url(), {
    onSuccess: () => {
      showStartModal.value = false
      startForm.reset()
    },
  })
}

function goToPage(url: string | null) {
  if (!url) {
    return
  }

  router.visit(url, { preserveScroll: true, preserveState: true })
}
</script>

<template>
  <AppLayout title="Inventaires">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Inventaires physiques</h1>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ counts.meta.total }} inventaire{{ counts.meta.total > 1 ? 's' : '' }}
          </p>
        </div>
        <Button :disabled="!depotActive" @click="showStartModal = true">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Démarrer un inventaire
        </Button>
      </div>

      <p v-if="!depotActive" class="text-xs text-amber-600">
        Sélectionnez un dépôt actif (en haut) pour démarrer un inventaire.
      </p>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Numéro</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Dépôt</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Statut</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Lignes</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Démarré le</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Par</th>
                <th class="px-4 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="counts.data.length === 0">
                <td colspan="7" class="px-4 py-12 text-center text-gray-400">Aucun inventaire trouvé</td>
              </tr>
              <tr
                v-for="count in counts.data"
                :key="count.id"
                class="hover:bg-gray-50 transition"
              >
                <td class="px-4 py-3 font-mono text-xs font-medium text-gray-900">{{ count.number }}</td>
                <td class="px-4 py-3 text-gray-700">{{ count.depot?.name ?? '—' }}</td>
                <td class="px-4 py-3">
                  <Badge :variant="count.status_color as BadgeVariant">{{ count.status_label }}</Badge>
                </td>
                <td class="px-4 py-3 text-right text-gray-600">{{ count.lines_count }}</td>
                <td class="px-4 py-3 text-gray-400 text-xs">{{ count.started_at ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ count.user?.name ?? '—' }}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-1">
                    <Link :href="StockCountController.show.url(count.id)">
                      <Button variant="ghost" size="sm">Ouvrir</Button>
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="counts.meta.last_page > 1"
          class="px-4 py-3 border-t flex items-center justify-between text-sm text-gray-600"
        >
          <span>{{ counts.meta.from }}–{{ counts.meta.to }} sur {{ counts.meta.total }}</span>
          <div class="flex gap-1">
            <Button variant="secondary" size="sm" :disabled="!counts.links.prev" @click="goToPage(counts.links.prev)">← Précédent</Button>
            <Button variant="secondary" size="sm" :disabled="!counts.links.next" @click="goToPage(counts.links.next)">Suivant →</Button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal démarrage -->
    <Modal :show="showStartModal" title="Démarrer un inventaire" max-width="sm" @close="showStartModal = false">
      <form @submit.prevent="submitStart" class="space-y-4">
        <p class="text-sm text-gray-500">
          Un instantané de la quantité et du coût moyen actuels de chaque pièce du dépôt actif sera créé. Vous pourrez ensuite saisir les quantités comptées.
        </p>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
          <Input v-model="startForm.note" placeholder="Ex: inventaire trimestriel" :error="startForm.errors.note" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button variant="secondary" type="button" @click="showStartModal = false">Annuler</Button>
          <Button type="submit" :loading="startForm.processing">Démarrer</Button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>
