<!-- resources/js/Components/UI/Input.vue -->
<script setup lang="ts">
defineProps<{
  modelValue?: string | number
  placeholder?: string
  type?: string
  error?: string
}>()

defineEmits<{ 'update:modelValue': [value: string] }>()
</script>

<template>
  <div class="flex flex-col gap-1">
    <input
      :value="modelValue"
      :type="type ?? 'text'"
      :placeholder="placeholder"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      :class="[
        'w-full rounded-lg border px-3 py-2 text-sm outline-none transition',
        'focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
        error ? 'border-red-400' : 'border-gray-300'
      ]"
    />
    <span v-if="error" class="text-xs text-red-500">{{ error }}</span>
  </div>
</template>