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

watch(
  () => page.props.flash as Record<string, string>,
  (flash) => {
    if (flash?.success) {
      toast.success(flash.success)
    }

    if (flash?.error) {
      toast.error(flash.error)
    }

    if (flash?.warning) {
      toast.warning(flash.warning)
    }

    if (flash?.info) {
      toast.info(flash.info)
    }
  },
  { deep: true },
)
</script>

<template>
  <div class="h-screen bg-gray-100 flex overflow-hidden">
    <Sidebar />

    <div class="flex-1 flex flex-col h-screen overflow-y-auto">
      <Header :title="title" class="sticky top-0 z-30" />

      <main class="flex-1 px-8 py-6">
        <slot />
      </main>
    </div>

    <Toaster position="bottom-right" rich-colors />
  </div>
</template>