<script setup lang="ts">
defineProps<{ show: boolean; title?: string; maxWidth?: 'sm' | 'md' | 'lg' }>()
defineEmits<{ close: [] }>()

const widths = { sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg' }
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" @click="$emit('close')" />

        <!-- Panel -->
        <div :class="['relative w-full bg-white rounded-xl shadow-xl', widths[maxWidth ?? 'md']]">
          <div v-if="title" class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-base font-semibold text-gray-900">{{ title }}</h3>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
          <div class="px-6 py-4">
            <slot />
          </div>
          <div v-if="$slots.footer" class="px-6 py-4 border-t flex justify-end gap-2">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>