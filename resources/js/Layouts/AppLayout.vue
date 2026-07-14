<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import { toast, Toaster } from 'vue-sonner'
import ImpersonationController from '@/actions/App/Http/Controllers/Admin/ImpersonationController'
import SubscriptionController from '@/actions/App/Http/Controllers/SubscriptionController'
import Header from '@/Components/UI/Header.vue'
import Sidebar from '@/Components/UI/Sidebar.vue'
import { usePermission } from '@/Composables/usePermission'
import type { Auth } from '@/types/auth'

defineProps<{
  title: string
}>()

const page = usePage()
const flash = () => page.props.flash as Record<string, string>
const { can } = usePermission()
const shop = computed(() => (page.props.auth as Auth).shop)
const impersonating = computed(() => page.props.impersonating as { id: number; name: string } | null)

const isOnTrial = computed(() => (page.props.auth as Auth).is_on_trial === true)

const trialDaysLeft = computed((): number | null => {
  if (!isOnTrial.value || !shop.value?.trial_ends_at) {
    return null
  }

  const endsAt = new Date(shop.value.trial_ends_at)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  endsAt.setHours(0, 0, 0, 0)
  const days = Math.ceil((endsAt.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))

  return days >= 0 ? days : null
})

const trialBannerStyle = computed(() => {
  const d = trialDaysLeft.value

  if (d === null) {
    return null
  }

  if (d <= 1) {
    return { bg: 'bg-red-50 border-b border-red-200', text: 'text-red-800', btn: 'bg-red-600 hover:bg-red-700 text-white' }
  }

  if (d <= 3) {
    return { bg: 'bg-orange-50 border-b border-orange-200', text: 'text-orange-800', btn: 'bg-orange-600 hover:bg-orange-700 text-white' }
  }

  if (d <= 7) {
    return { bg: 'bg-amber-50 border-b border-amber-200', text: 'text-amber-800', btn: 'bg-amber-600 hover:bg-amber-700 text-white' }
  }

  return { bg: 'bg-blue-50 border-b border-blue-200', text: 'text-blue-800', btn: 'bg-blue-600 hover:bg-blue-700 text-white' }
})

const trialLabel = computed(() => {
  const d = trialDaysLeft.value

  if (d === null) {
    return ''
  }

  if (d === 0) {
    return "Votre essai gratuit expire aujourd'hui."
  }

  if (d === 1) {
    return 'Votre essai gratuit expire demain.'
  }

  return `Votre essai gratuit se termine dans ${d} jours.`
})

watch(
  () => [flash()?.success, flash()?.error, flash()?.warning, flash()?.info],
  ([success, error, warning, info], prev = []) => {
    const [prevSuccess, prevError, prevWarning, prevInfo] = prev

    if (success && success !== prevSuccess) {
toast.success(success)
}

    if (error && error !== prevError) {
toast.error(error)
}

    if (warning && warning !== prevWarning) {
toast.warning(warning)
}

    if (info && info !== prevInfo) {
toast.info(info)
}
  },
  { immediate: true }
)
</script>

<template>
  <div class="h-screen bg-gray-100 flex overflow-hidden">
    <Sidebar />

    <div class="flex-1 flex flex-col h-screen overflow-y-auto">
      <!-- Banner impersonation -->
      <div v-if="impersonating" class="bg-purple-600 px-4 py-2 flex items-center justify-between gap-4">
        <span class="text-sm font-medium text-white">
          Simulation active — vous accédez en tant que <strong>{{ shop?.name }}</strong>
          (compte original : {{ impersonating.name }})
        </span>
        <Link :href="ImpersonationController.stop.url()" method="post" as="button"
          class="shrink-0 text-xs font-semibold bg-white text-purple-700 hover:bg-purple-50 px-3 py-1 rounded-md transition-colors">
          Terminer la simulation
        </Link>
      </div>

      <!-- Banner essai gratuit -->
      <div v-if="trialDaysLeft !== null && trialBannerStyle"
        :class="[trialBannerStyle.bg, 'px-4 py-2 flex items-center justify-between gap-4']">
        <span :class="[trialBannerStyle.text, 'text-sm font-medium']">
          {{ trialLabel }}
        </span>
        <Link :href="SubscriptionController.index.url()" v-show="can('settings.manage')"
          :class="[trialBannerStyle.btn, 'shrink-0 text-xs font-semibold px-3 py-1 rounded-md transition-colors']">
          Choisir un plan
        </Link>
      </div>

      <Header :title="title" class="sticky top-0 z-30" />

      <main class="flex-1 p-8">
        <slot />
      </main>
    </div>

    <Toaster position="bottom-right" rich-colors />
  </div>
</template>
