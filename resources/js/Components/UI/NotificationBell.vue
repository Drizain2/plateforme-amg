<!-- resources/js/Components/UI/NotificationBell.vue -->
<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted, computed } from 'vue'
import NotificationController from '@/actions/App/Http/Controllers/NotificationController'
import type { AppNotification } from '@/types'



const page = usePage()
const open = ref(false)
const loading = ref(false)
const notifications = ref<AppNotification[]>([])

const unreadCount = computed(() => page.props.auth?.unread_count as unknown as number)

async function fetchNotifications() {
  if (loading.value) {
    return
  }

  loading.value = true

  const res = await fetch(NotificationController.index.url(), {
    headers: { Accept: 'application/json' }
  })
  const data = await res.json()
  notifications.value = data.notifications
  loading.value = false
}

async function markRead(id: string) {
  await fetch(NotificationController.markRead.url(id), {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content,
      Accept: 'application/json',
    },
  })
  const n = notifications.value.find(n => n.id === id)

  if (n) {
    n.read_at = 'À l\'instant'
  }

  router.reload({ only: ['auth'] })
}

async function markAllRead() {
  await fetch(NotificationController.markAllRead.url(), {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content,
      Accept: 'application/json',
    },
  })
  notifications.value.forEach(n => n.read_at = 'À l\'instant')
  router.reload({ only: ['auth'] })
}

function toggle() {
  open.value = !open.value

  if (open.value) {
    fetchNotifications()
  }
}

// Fermer au clic extérieur
function onClickOutside(e: MouseEvent) {
  const el = document.getElementById('notif-bell')

  if (el && !el.contains(e.target as Node)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('click', onClickOutside))
onUnmounted(() => document.removeEventListener('click', onClickOutside))

function notifIcon(n: AppNotification): string {
  const icons: Record<string, string> = {
    low_stock: '⚠️',
    ticket_assigned: '🔧',
    invoice_sent: '🧾',
  }

  return icons[n.data.type as string] ?? '🔔'
}

function notifLabel(n: AppNotification): string {
  switch (n.data.type as string) {
    case 'low_stock':
      return `Stock critique — ${n.data.part_name} (${n.data.quantity}/${n.data.min_threshold}) · ${n.data.depot_name}`
    case 'ticket_assigned':
      return `Ticket ${n.data.reference} vous a été assigné par ${n.data.assigned_by} (${n.data.customer})`
    case 'invoice_sent':
      return `Facture ${n.data.number} envoyée`
    default:
      return n.data.reference
        ? `Ticket ${n.data.reference} — ${n.data.status_label ?? ''}`
        : 'Nouvelle notification'
  }
}
</script>

<template>
  <div id="notif-bell" class="relative">

    <!-- Bouton cloche -->
    <button @click.stop="toggle"
      class="relative p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-5-5.917V5a1 1 0 10-2 0v.083A6 6 0 006 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      <span v-if="unreadCount > 0"
        class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold leading-none">
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <Transition name="dropdown">
      <div v-if="open"
        class="absolute right-0 top-10 w-80 bg-white border border-gray-200 rounded-xl shadow-xl z-50 overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b">
          <p class="text-sm font-semibold text-gray-900">Notifications</p>
          <button v-if="unreadCount > 0" @click="markAllRead" class="text-xs text-indigo-600 hover:underline">
            Tout marquer lu
          </button>
        </div>

        <!-- Liste -->
        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100">
          <div v-if="loading" class="flex justify-center py-6">
            <svg class="w-5 h-5 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
            </svg>
          </div>

          <div v-else-if="notifications.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">
            Aucune notification
          </div>

          <button v-else v-for="n in notifications" :key="n.id" @click="markRead(n.id)"
            class="w-full text-left px-4 py-3 hover:bg-gray-50 transition flex gap-3"
            :class="{ 'bg-indigo-50/50': !n.read_at }">
            <span class="text-lg shrink-0">{{ notifIcon(n) }}</span>
            <div class="min-w-0">
              <p class="text-xs text-gray-700 leading-snug">{{ notifLabel(n) }}</p>
              <p class="text-xs text-gray-400 mt-0.5">{{ n.created_at }}</p>
            </div>
            <span v-if="!n.read_at" class="w-2 h-2 bg-indigo-500 rounded-full shrink-0 mt-1" />
          </button>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>