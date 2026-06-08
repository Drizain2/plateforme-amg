<!-- resources/js/Components/UI/Select.vue -->
<script setup lang="ts">
defineProps<{
  modelValue?: string | number | null
  options: { value: string | number; label: string }[]
  placeholder?: string
  error?: string
}>()

defineEmits<{ 'update:modelValue': [value: string] }>()
</script>

<template>
  <div class="flex flex-col gap-1">
    <select
      :value="modelValue ?? ''"
      @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
      :class="[
        'w-full rounded-lg border px-3 py-2 text-sm outline-none transition bg-white',
        'focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
        error ? 'border-red-400' : 'border-gray-300'
      ]"
    >
      <option value="">{{ placeholder ?? 'Sélectionner...' }}</option>
      <option v-for="opt in options" :key="opt.value" :value="opt.value">
        {{ opt.label }}
      </option>
    </select>
    <span v-if="error" class="text-xs text-red-500">{{ error }}</span>
  </div>
</template>