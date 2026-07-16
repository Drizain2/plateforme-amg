<!-- resources/js/Components/UI/Input.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'

const props = defineProps<{
  modelValue?: string | number
  placeholder?: string
  type?: string
  error?: string
}>()

defineEmits<{ 'update:modelValue': [value: string] }>()

const showPassword = ref(false)

const isPassword = computed(() => props.type === 'password')
const inputType = computed(() => {
  if (!isPassword.value) {
return props.type ?? 'text'
}

  return showPassword.value ? 'text' : 'password'
})
</script>

<template>
  <div class="flex flex-col gap-1">
    <div class="relative">
      <input
        :value="modelValue"
        :type="inputType"
        :placeholder="placeholder"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        :class="[
          'w-full rounded-lg border px-3 py-2 text-sm outline-none transition',
          'focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
          isPassword ? 'pr-10' : '',
          error ? 'border-red-400' : 'border-gray-300'
        ]"
      />
      <button
        v-if="isPassword"
        type="button"
        @click="showPassword = !showPassword"
        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
        tabindex="-1"
      >
        <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
        </svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </button>
    </div>
    <span v-if="error" class="text-xs text-red-500">{{ error }}</span>
  </div>
</template>