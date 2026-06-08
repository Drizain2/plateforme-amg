<script lang="ts" setup>
import { computed } from 'vue';

const props = defineProps<{
    variant?: 'default' | 'success' | 'warning' | 'danger' | 'info'
}>()
const classes = computed(() => ({
    'default': 'bg-gray-100 text-gray-700',
    'success': 'bg-green-100 text-green-700',
    'warning': 'bg-yellow-100 text-yellow-800',
    'danger': 'bg-red-100 text-red-700',
    'info': 'bg-blue-100 text-blue-700',
}[props.variant ?? 'default']))
</script>

<template>
    <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium', classes]">
        <slot />
    </span>
</template>