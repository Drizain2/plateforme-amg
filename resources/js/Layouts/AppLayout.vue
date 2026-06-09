<!-- resources/js/Layouts/AppLayout.vue -->
<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Toaster } from 'vue-sonner'
import LoginController from '@/actions/App/Http/Controllers/Auth/LoginController'
import DepotController from '@/actions/App/Http/Controllers/Stock/DepotController'
import PartController from '@/actions/App/Http/Controllers/Stock/PartController'
import SupplierController from '@/actions/App/Http/Controllers/Stock/SupplierController'

defineProps<{ title?: string }>()

const page = usePage()

const currentUrl = computed(() => page.url)

function isActive(prefix: string) {
  return currentUrl.value.startsWith(prefix)
}

const navLinkClass = (prefix: string) =>
  isActive(prefix)
    ? 'bg-indigo-50 text-indigo-700'
    : 'text-gray-600 hover:bg-gray-100'
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
        <Link href="/tickets" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition"
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
      <header class="bg-white border-b border-gray-200 px-8 py-4">
        <h2 class="text-sm font-medium text-gray-700">{{ title }}</h2>
      </header>

      <main class="flex-1 px-8 py-6">
        <slot />
      </main>
    </div>

    <Toaster position="bottom-right" rich-colors />
  </div>
</template>
