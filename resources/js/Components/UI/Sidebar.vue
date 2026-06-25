<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import LoginController from '@/actions/App/Http/Controllers/Auth/LoginController'
import CustomerController from '@/actions/App/Http/Controllers/Customer/CustomerController'
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController'
import PurchaseController from '@/actions/App/Http/Controllers/PurchaseController'
import ReportController from '@/actions/App/Http/Controllers/ReportController'
import SettingsController from '@/actions/App/Http/Controllers/SettingsController'
import ShopUserController from '@/actions/App/Http/Controllers/ShopUserController'
import CategorieController from '@/actions/App/Http/Controllers/Stock/CategorieController'
import DepotController from '@/actions/App/Http/Controllers/Stock/DepotController'
import PartController from '@/actions/App/Http/Controllers/Stock/PartController'
import StockCountController from '@/actions/App/Http/Controllers/Stock/StockCountController'
import StockMovementController from '@/actions/App/Http/Controllers/Stock/StockMovementController'
import SupplierController from '@/actions/App/Http/Controllers/Stock/SupplierController'
import TicketController from '@/actions/App/Http/Controllers/Ticket/TicketController'
import { usePermission } from '@/Composables/usePermission'
import { useSidebar } from '@/Composables/useSidebar'
import type { SidebarGroup, SidebarItem } from '@/types'

const page = usePage()
const { can } = usePermission()
const { collapsed, mobileOpen, toggleCollapsed, closeMobile } = useSidebar()

// Icônes SVG (heroicons outline, viewBox 0 0 24 24, stroke-width 2)
const ICONS = {
  dashboard: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
  stock: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
  ticket: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
  depot: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
  supplier: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
  customer: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
  invoice: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  category: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
  movement: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
  alert: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
  users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
  settings: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
  report: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
  purchase: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m-10 4a1 1 0 102 0 1 1 0 10-2 0zm10 0a1 1 0 102 0 1 1 0 10-2 0z',
  count: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
}

const groups: SidebarGroup[] = [
  {
    items: [
      {
        label: 'Dashboard',
        href: '/dashboard',
        path: '/dashboard',
        permission: 'dashboard.view',
        icon: ICONS.dashboard
      },
      {
        label: 'Mon profil',
        href: SettingsController.edit.url(),
        path: '/settings',
        icon: ICONS.settings
      },
    ],
  },
  {
    label: 'SAV',
    items: [
      {
        label: 'Tickets SAV',
        href: TicketController.index.url(),
        path: '/tickets',
        permission: 'tickets.view',
        icon: ICONS.ticket
      },
      {
        label: 'Clients',
        href: CustomerController.index.url(),
        path: '/customers',
        permission: 'customers.view',
        icon: ICONS.customer
      },
      {
        label: 'Factures',
        href: InvoiceController.index.url(),
        path: '/invoices',
        permission: 'invoices.view',
        icon: ICONS.invoice
      },
    ],
  },
  {
    label: 'Stock',
    items: [
      {
        label: 'Pièces',
        href: PartController.index.url(),
        path: '/stock/parts',
        permission: 'stock.view',
        icon: ICONS.stock
      },
      {
        label: 'Mouvements',
        href: StockMovementController.index.url(),
        path: '/stock/movements',
        permission: 'stock.view',
        icon: ICONS.movement
      },
      {
        label: 'Alertes',
        href: StockMovementController.alerts.url(),
        path: '/stock/alerts',
        permission: 'stock.view',
        icon: ICONS.alert
      },
      {
        label: 'Inventaires',
        href: StockCountController.index.url(),
        path: '/stock/counts',
        permission: 'stock.count',
        icon: ICONS.count
      },
      {
        label: 'Dépôts',
        href: DepotController.index.url(),
        path: '/stock/depots',
        permission: 'depots.view',
        icon: ICONS.depot
      },
      {
        label: 'Fournisseurs',
        href: SupplierController.index.url(),
        path: '/stock/suppliers',
        permission: 'stock.view',
        icon: ICONS.supplier
      },
      {
        label: 'Achats',
        href: PurchaseController.index.url(),
        path: '/purchases',
        permission: 'purchases.view',
        icon: ICONS.purchase
      },
      {
        label: 'Catégories',
        href: CategorieController.index.url(),
        path: '/stock/categories',
        permission: 'stock.view',
        icon: ICONS.category
      },
    ],
  },
  {
    label: 'Rapports',
    items: [
      { label: 'Rapport de caisse', href: ReportController.cash.url(), path: '/reports/cash', permission: 'dashboard.analytics', icon: ICONS.report },
    ],
  },
  {
    label: 'Administration',
    items: [
      { label: 'Utilisateurs', href: ShopUserController.index.url(), path: '/users', permission: 'users.view', icon: ICONS.users },
    ],
  },
]

function isVisible(item: SidebarItem): boolean {
  return !item.permission || can(item.permission)
}

const visibleGroups = computed(() =>
  groups
    .map(g => ({ ...g, items: g.items.filter(isVisible) }))
    .filter(g => g.items.length > 0)
)

function isActive(path: string): boolean {
  return page.url.startsWith(path)
}

const ROLE_LABELS: Record<string, string> = {
  super_admin: 'Super admin',
  admin: 'Administrateur',
  manager: 'Gestionnaire',
  gestionnaire: 'Gestionnaire',
  technicien: 'Technicien',
  caissiere: 'Caissière',
}

const userInitials = computed(() => {
  const name = page.props.auth.user.name ?? ''

  return name.split(' ').filter(Boolean).map(w => w[0]).join('').toUpperCase().slice(0, 2)
})

const userRoleLabel = computed(() => {
  const role = page.props.auth.user.roles?.[0]?.name

  return role ? (ROLE_LABELS[role] ?? role) : null
})
</script>

<template>
  <div class="contents">
  <!-- Backdrop mobile -->
  <div v-if="mobileOpen" class="fixed inset-0 bg-black/40 z-40 lg:hidden" @click="closeMobile" />

  <aside
    class="bg-white border-r border-gray-200 flex flex-col h-screen overflow-y-auto shrink-0 z-50
      fixed inset-y-0 left-0 w-64 transition-transform duration-200
      lg:sticky lg:top-0 lg:translate-x-0 lg:transition-[width]"
    :class="[
      mobileOpen ? 'translate-x-0' : '-translate-x-full',
      collapsed ? 'lg:w-16' : 'lg:w-64',
    ]"
  >

    <!-- Logo -->
    <div class="px-4 py-5 border-b shrink-0 flex items-center gap-2" :class="collapsed ? 'lg:justify-center lg:px-2' : 'justify-between'">
      <div class="min-w-0" :class="{ 'lg:hidden': collapsed }">
        <span class="font-bold text-indigo-600 text-lg block truncate">SAV Platform</span>
        <p class="text-xs text-gray-400 mt-0.5 truncate">{{ page.props.auth.shop?.name }}</p>
      </div>

      <button
        type="button"
        class="hidden lg:flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition shrink-0"
        :title="collapsed ? 'Développer le menu' : 'Réduire le menu'"
        @click="toggleCollapsed"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <path v-if="collapsed" d="M9 5l7 7-7 7" />
          <path v-else d="M15 19l-7-7 7-7" />
        </svg>
      </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-5 overflow-y-auto">
      <div v-for="(group, gi) in visibleGroups" :key="gi">

        <!-- Séparateur de groupe -->
        <p v-if="group.label" class="px-3 mb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider truncate"
          :class="{ 'lg:hidden': collapsed }">
          {{ group.label }}
        </p>

        <div class="space-y-0.5">
          <Link v-for="item in group.items" :key="item.href" :href="item.href" :title="item.label"
            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="[
              isActive(item.path) ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
              collapsed ? 'lg:justify-center lg:px-2' : '',
            ]"
            @click="closeMobile">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <path :d="item.icon" />
            </svg>
            <span class="truncate" :class="{ 'lg:hidden': collapsed }">{{ item.label }}</span>
          </Link>
        </div>

      </div>
    </nav>

    <!-- User footer -->
    <div class="px-3 py-4 border-t shrink-0 space-y-3">
      <div class="flex items-center gap-2.5" :class="{ 'lg:justify-center': collapsed }">
        <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs shrink-0"
          :title="page.props.auth.user.name">
          {{ userInitials }}
        </div>
        <div class="min-w-0" :class="{ 'lg:hidden': collapsed }">
          <p class="text-sm font-medium text-gray-900 truncate">{{ page.props.auth.user.name }}</p>
          <p v-if="userRoleLabel" class="text-xs text-gray-400 truncate">{{ userRoleLabel }}</p>
        </div>
      </div>

      <Link :href="LoginController.logout.url()" method="post" as="button" title="Déconnexion"
        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 transition-colors cursor-pointer"
        :class="collapsed ? 'lg:justify-center lg:px-0' : ''">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
        </svg>
        <span :class="{ 'lg:hidden': collapsed }">Déconnexion</span>
      </Link>
    </div>

  </aside>
  </div>
</template>
