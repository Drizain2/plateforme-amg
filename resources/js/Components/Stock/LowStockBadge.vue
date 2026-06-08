<script setup lang="ts">
import Badge from '@/Components/UI/Badge.vue'

defineProps<{ quantity: number; alertQuantity: number }>()
</script>

<template>
  <div class="flex items-center gap-2">
    <span class="font-medium" :class="quantity <= alertQuantity ? 'text-red-600' : 'text-gray-900'">
      {{ quantity }}
    </span>
    <Badge v-if="quantity === 0" variant="danger">Rupture</Badge>
    <Badge v-else-if="quantity <= alertQuantity" variant="warning">Critique</Badge>
  </div>
</template>
