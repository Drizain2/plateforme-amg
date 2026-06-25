<!-- resources/js/Components/Dashboard/StatCard.vue -->
<script setup lang="ts">
defineProps<{
  label: string
  value: string | number
  sub?: string
  variant?: 'default' | 'danger' | 'success' | 'warning'
}>()

const colors = {
  default: 'text-indigo-600 bg-indigo-50',
  danger:  'text-red-600 bg-red-50',
  success: 'text-green-600 bg-green-50',
  warning: 'text-yellow-600 bg-yellow-50',
}
</script>

<template>
  <div class="bg-white rounded-xl border border-gray-200 p-5 min-w-0 overflow-hidden">
    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide truncate">{{ label }}</p>
    <p
      class="text-3xl font-bold mt-2 truncate"
      :class="colors[variant ?? 'default'].split(' ')[0]"
    >
      {{ value }}
    </p>
    <p v-if="sub" class="text-xs text-gray-400 mt-1 truncate">{{ sub }}</p>
  </div>
</template>