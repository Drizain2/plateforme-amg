<script setup lang="ts">
import { Toaster } from 'vue-sonner'
import Header from '@/Components/UI/Header.vue'
import Sidebar from '@/Components/UI/Sidebar.vue'


defineProps<{
  title: string
}>()
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