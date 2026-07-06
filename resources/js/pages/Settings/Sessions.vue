<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import SessionController from '@/actions/App/Http/Controllers/SessionController'
import AppLayout from '@/Layouts/AppLayout.vue'

interface Session {
  id: string
  ip_address: string | null
  user_agent: string | null
  last_activity: string
  is_current: boolean
}

const props = defineProps<{
  sessions: Session[]
}>()

const parseAgent = (ua: string | null): string => {
  if (!ua) return 'Navigateur inconnu'
  if (ua.includes('iPhone') || ua.includes('Android')) return 'Mobile'
  if (ua.includes('Chrome')) return 'Chrome'
  if (ua.includes('Firefox')) return 'Firefox'
  if (ua.includes('Safari')) return 'Safari'
  if (ua.includes('Edge')) return 'Edge'
  return 'Autre navigateur'
}

const fmtDate = (iso: string) =>
  new Date(iso).toLocaleString('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })

const revoke = (sessionId: string) => {
  router.delete(SessionController.destroy.url({ session: sessionId }))
}

const revokeAll = () => {
  router.delete(SessionController.destroyAll.url())
}
</script>

<template>
  <AppLayout title="Sessions actives">
    <div class="max-w-3xl">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">Sessions actives</h2>
          <p class="text-sm text-gray-500 mt-1">Appareils et navigateurs connectés à votre compte.</p>
        </div>
        <button
          v-if="sessions.filter(s => !s.is_current).length > 0"
          class="text-sm font-medium text-red-600 hover:text-red-700 transition"
          @click="revokeAll"
        >
          Tout révoquer sauf cette session
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
        <div
          v-for="session in sessions"
          :key="session.id"
          class="px-5 py-4 flex items-center justify-between gap-4"
        >
          <div class="flex items-start gap-3">
            <!-- Icône appareil -->
            <div class="mt-0.5 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
              <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3" />
              </svg>
            </div>

            <div>
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-800">{{ parseAgent(session.user_agent) }}</span>
                <span
                  v-if="session.is_current"
                  class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700"
                >
                  Cette session
                </span>
              </div>
              <p class="text-xs text-gray-400 mt-0.5">
                {{ session.ip_address ?? 'IP inconnue' }} · Dernière activité : {{ fmtDate(session.last_activity) }}
              </p>
            </div>
          </div>

          <button
            v-if="!session.is_current"
            class="shrink-0 text-xs font-medium text-red-500 hover:text-red-700 transition"
            @click="revoke(session.id)"
          >
            Révoquer
          </button>
        </div>

        <div v-if="sessions.length === 0" class="px-6 py-10 text-center text-sm text-gray-400">
          Aucune session active trouvée.
        </div>
      </div>
    </div>
  </AppLayout>
</template>
