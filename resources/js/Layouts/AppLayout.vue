<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { watch } from 'vue'
import { toast, Toaster } from 'vue-sonner'
import Header from '@/Components/UI/Header.vue'
import Sidebar from '@/Components/UI/Sidebar.vue'

defineProps<{
  title: string
}>()

const page = usePage()

const flash = () => page.props.flash as Record<string, string>

watch(
  () => [flash()?.success, flash()?.error, flash()?.warning, flash()?.info],
  ([success, error, warning, info], [prevSuccess, prevError, prevWarning, prevInfo]) => {
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
)
</script>

<template>
  <div class="h-screen bg-gray-100 flex overflow-hidden">
    <Sidebar />

    <div class="flex-1 flex flex-col h-screen overflow-y-auto">
      <Header :title="title" class="sticky top-0 z-30" />

      <main class="flex-1 p-8">
        <slot />
      </main>
    </div>

    <Toaster position="bottom-right" rich-colors />
  </div>
</template>