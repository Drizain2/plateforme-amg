<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
interface LaravelPaginator<T> {
  data: T[]
  current_page: number
  last_page: number
  prev_page_url: string | null
  next_page_url: string | null
}

interface LogEntry {
  id: number
  log_name: string
  event: string | null
  subject_type: string
  subject_id: number
  description: string
  causer: { name: string } | null
  properties: { old?: Record<string, unknown>; attributes?: Record<string, unknown> }
  created_at: string
}

defineProps<{
  logs: LaravelPaginator<LogEntry>
}>()

const eventLabel: Record<string, string> = {
  created: 'Créé',
  updated: 'Modifié',
  deleted: 'Supprimé',
}

const eventVariant: Record<string, string> = {
  created: 'bg-green-100 text-green-700',
  updated: 'bg-blue-100 text-blue-700',
  deleted: 'bg-red-100 text-red-700',
}

const subjectLabel: Record<string, string> = {
  Ticket: 'Ticket',
  Invoice: 'Facture',
  User: 'Utilisateur',
  Payment: 'Paiement',
}

const fmtDate = (iso: string) =>
  new Date(iso).toLocaleString('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
</script>

<template>
  <AppLayout title="Journal d'audit">
    <div class="max-w-5xl">
      <!-- En-tête -->
      <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900">Journal d'activité</h2>
        <p class="text-sm text-gray-500 mt-1">Historique des actions effectuées sur votre atelier.</p>
      </div>

      <!-- Tableau -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div v-if="logs.data.length === 0" class="px-6 py-12 text-center text-sm text-gray-400">
          Aucune activité enregistrée pour l'instant.
        </div>

        <table v-else class="min-w-full divide-y divide-gray-100 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Date</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Utilisateur</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Action</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Objet</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Modifications</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">
                {{ fmtDate(log.created_at) }}
              </td>
              <td class="px-4 py-3 text-gray-700 font-medium">
                {{ log.causer?.name ?? '—' }}
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="eventVariant[log.event ?? ''] ?? 'bg-gray-100 text-gray-600'"
                >
                  {{ eventLabel[log.event ?? ''] ?? log.event }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-600">
                {{ subjectLabel[log.subject_type] ?? log.subject_type }}
                <span class="text-gray-400 text-xs">#{{ log.subject_id }}</span>
              </td>
              <td class="px-4 py-3">
                <div v-if="log.properties.attributes" class="space-y-0.5">
                  <div
                    v-for="(val, key) in log.properties.attributes"
                    :key="String(key)"
                    class="text-xs text-gray-500"
                  >
                    <span class="font-medium text-gray-700">{{ key }}</span>
                    <template v-if="log.properties.old && key in log.properties.old">
                      <span class="line-through text-red-400 mx-1">{{ log.properties.old[key as string] }}</span>→
                    </template>
                    <span class="text-green-700 ml-1">{{ val }}</span>
                  </div>
                </div>
                <span v-else class="text-xs text-gray-400">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="logs.last_page > 1" class="mt-4 flex items-center justify-between text-sm text-gray-500">
        <span>Page {{ logs.current_page }} / {{ logs.last_page }}</span>
        <div class="flex gap-2">
          <Link
            v-if="logs.prev_page_url"
            :href="logs.prev_page_url"
            class="px-3 py-1.5 rounded border border-gray-200 hover:bg-gray-50 transition"
          >
            ← Précédent
          </Link>
          <Link
            v-if="logs.next_page_url"
            :href="logs.next_page_url"
            class="px-3 py-1.5 rounded border border-gray-200 hover:bg-gray-50 transition"
          >
            Suivant →
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
