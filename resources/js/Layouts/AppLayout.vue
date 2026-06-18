<!-- resources/js/Layouts/AppLayout.vue -->
<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Toaster } from 'vue-sonner'
import LoginController from '@/actions/App/Http/Controllers/Auth/LoginController'
import CustomerController from '@/actions/App/Http/Controllers/Customer/CustomerController'
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController'
import SettingsController from '@/actions/App/Http/Controllers/SettingsController'
import ShopUserController from '@/actions/App/Http/Controllers/ShopUserController'
import CategorieController from '@/actions/App/Http/Controllers/Stock/CategorieController'
import DepotController from '@/actions/App/Http/Controllers/Stock/DepotController'
import PartController from '@/actions/App/Http/Controllers/Stock/PartController'
import StockMovementController from '@/actions/App/Http/Controllers/Stock/StockMovementController'
import SupplierController from '@/actions/App/Http/Controllers/Stock/SupplierController'
import TicketController from '@/actions/App/Http/Controllers/Ticket/TicketController'
import NotificationBell from '@/Components/UI/NotificationBell.vue'

defineProps<{ title?: string }>()

const page = usePage()

const currentUrl = computed(() => page.url)
const auth = computed(() => page.props.auth)
const isAdmin = computed(() => {
  const role = auth.value.user?.roles?.[0]?.name

  return role === 'admin' || role === 'super_admin'
})

function isActive(prefix: string) {
  return currentUrl.value.startsWith(prefix)
}

const navLinkClass = (prefix: string) =>
  isActive(prefix)
    ? 'bg-indigo-50 text-indigo-700'
    : 'text-gray-600 hover:bg-gray-100'

// Depot switcher
const depotDropdownOpen = ref(false)

function switchDepot(depotId: number | null) {
  depotDropdownOpen.value = false
  router.post('/depot/switch', { depot_id: depotId }, { preserveScroll: false })
}

const depotLabel = computed(() => {
  if (auth.value.isGlobalView) {
return 'Vue globale'
}

  return auth.value.depotActive?.name ?? 'Dépôt'
})
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
      <div class="px-6 py-5 border-b">
        <span class="font-bold text-indigo-600 text-lg">SAV Platform</span>
        <p class="text-xs text-gray-400 mt-0.5">{{ page.props.auth.shop?.name }}</p>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-1">
        <Link href="/dashboard" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition"
          :class="navLinkClass('/dashboard')">
          Dashboard
        </Link>
        <Link :href="PartController.index.url()"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition cursor-pointer"
          :class="navLinkClass('/stock/parts')">
          Stock
        </Link>
        <Link :href="TicketController.index.url()"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition"
          :class="navLinkClass('/tickets')">
          Tickets SAV
        </Link>
        <Link :href="DepotController.index.url()"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition cursor-pointer"
          :class="navLinkClass('/stock/depots')">
          Depots
        </Link>
        <Link :href="SupplierController.index.url()"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition cursor-pointer"
          :class="navLinkClass('/stock/suppliers')">
          Fournisseurs
        </Link>
        <Link :href="CustomerController.index.url()"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition cursor-pointer"
          :class="navLinkClass('/customers')">
          Clients
        </Link>
        <Link :href="InvoiceController.index.url()"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition cursor-pointer"
          :class="navLinkClass('/invoices')">
          Factures
        </Link>
        <Link :href="CategorieController.index.url()"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition cursor-pointer"
          :class="navLinkClass('/stock/categories')">
          Catégories
        </Link>
        <Link :href="StockMovementController.index.url()"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition cursor-pointer"
          :class="navLinkClass('/stock/movements')">
          Mouvements
        </Link>
        <Link :href="StockMovementController.alerts.url()"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition cursor-pointer"
          :class="navLinkClass('/stock/alerts')">
          Alertes stock
        </Link>
        <Link :href="ShopUserController.index.url()"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition"
          :class="navLinkClass('/users')">
          Utilisateurs
        </Link>
        <Link :href="SettingsController.edit.url()"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition"
          :class="navLinkClass('/settings')">
          Paramètres
        </Link>
      </nav>

      <!-- User footer -->
      <div class="px-4 py-4 border-t">
        <p class="text-sm font-medium text-gray-900">{{ page.props.auth.user.name }}</p>
        <Link :href="LoginController.logout.url()" method="post" as="button"
          class="text-xs text-gray-400 hover:text-red-500 transition">
          Déconnexion
        </Link>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-h-screen">
      <header class="bg-white border-b border-gray-200 px-8 py-4 flex items-center justify-between">
        <h2 class="text-sm font-medium text-gray-700">{{ title }}</h2>

        <div class="flex items-center gap-3">
          <!-- Depot switcher (admin / super_admin uniquement) -->
          <div v-if="isAdmin" class="relative">
            <button
              type="button"
              class="flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
              @click="depotDropdownOpen = !depotDropdownOpen"
            >
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <span :class="auth.isGlobalView ? 'text-indigo-600 font-semibold' : ''">{{ depotLabel }}</span>
              <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Dropdown -->
            <div
              v-if="depotDropdownOpen"
              class="absolute right-0 mt-1 w-56 bg-white border border-gray-200 rounded-xl shadow-lg z-50 py-1"
            >
              <!-- Vue globale -->
              <button
                type="button"
                class="w-full text-left px-4 py-2.5 text-sm flex items-center gap-2 hover:bg-gray-50 transition"
                :class="auth.isGlobalView ? 'text-indigo-600 font-semibold' : 'text-gray-700'"
                @click="switchDepot(null)"
              >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                </svg>
                Vue globale
                <svg v-if="auth.isGlobalView" class="w-3.5 h-3.5 ml-auto text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <div class="border-t border-gray-100 my-1" />

              <!-- Dépôts disponibles -->
              <button
                v-for="depot in auth.depots"
                :key="depot.id"
                type="button"
                class="w-full text-left px-4 py-2.5 text-sm flex items-center gap-2 hover:bg-gray-50 transition"
                :class="auth.depotActive?.id === depot.id ? 'text-indigo-600 font-semibold' : 'text-gray-700'"
                @click="switchDepot(depot.id)"
              >
                <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
                </svg>
                {{ depot.name }}
                <svg v-if="auth.depotActive?.id === depot.id" class="w-3.5 h-3.5 ml-auto text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <div v-if="auth.depots.length === 0" class="px-4 py-3 text-sm text-gray-400 italic">
                Aucun dépôt disponible
              </div>
            </div>

            <!-- Overlay pour fermer -->
            <div
              v-if="depotDropdownOpen"
              class="fixed inset-0 z-40"
              @click="depotDropdownOpen = false"
            />
          </div>

          <NotificationBell />
        </div>
      </header>

      <main class="flex-1 px-8 py-6">
        <slot />
      </main>
    </div>

    <Toaster position="bottom-right" rich-colors />
  </div>
</template>
