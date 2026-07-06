<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { watch } from 'vue'
import { toast, Toaster } from 'vue-sonner'
import AdminDashboardController from '@/actions/App/Http/Controllers/Admin/DashboardController'
import AdminPaymentController from '@/actions/App/Http/Controllers/Admin/PaymentController'
import PlanController from '@/actions/App/Http/Controllers/Admin/PlanController'
import LoginController from '@/actions/App/Http/Controllers/Auth/LoginController'

defineProps<{
  title: string
}>()

const page = usePage()

const flash = () => page.props.flash as Record<string, string>

watch(
  () => [flash()?.success, flash()?.error],
  ([success, error], [prevSuccess, prevError]) => {
    if (success && success !== prevSuccess) {
      toast.success(success)
    }

    if (error && error !== prevError) {
      toast.error(error)
    }
  },
)
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <header class="bg-white border-b border-gray-200 px-8 py-4 flex items-center justify-between">
      <div class="flex items-center gap-8">
        <span class="font-bold text-indigo-600 text-lg">SAV Platform — Administration</span>
        <nav class="flex items-center gap-4 text-sm font-medium">
          <Link :href="AdminDashboardController.index.url()" class="text-gray-600 hover:text-indigo-600 transition">
            Tableau de bord
          </Link>
          <Link :href="PlanController.index.url()" class="text-gray-600 hover:text-indigo-600 transition">
            Offres
          </Link>
          <Link :href="AdminPaymentController.index.url()" class="text-gray-600 hover:text-indigo-600 transition">
            Paiements
          </Link>
        </nav>
      </div>

      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-500">{{ page.props.auth.user.name }}</span>
        <Link :href="LoginController.logout.url()" method="post" as="button"
          class="text-sm text-gray-400 hover:text-red-500 transition-colors cursor-pointer">
          Déconnexion
        </Link>
      </div>
    </header>

    <main class="px-8 py-6">
      <h1 class="text-xl font-semibold text-gray-900 mb-6">{{ title }}</h1>
      <slot />
    </main>

    <Toaster position="bottom-right" rich-colors />
  </div>
</template>
