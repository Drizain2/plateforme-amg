<!-- resources/js/Components/Dashboard/DonutChart.vue -->
<script setup lang="ts">
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps<{
  labels: string[]
  values: number[]
}>()

const COLORS = [
  '#6366f1','#22c55e','#f59e0b','#ef4444',
  '#3b82f6','#8b5cf6','#14b8a6','#f97316',
]

const data = computed(() => ({
  labels: props.labels,
  datasets: [{
    data:            props.values,
    backgroundColor: COLORS.slice(0, props.values.length),
    borderWidth:     0,
    hoverOffset:     4,
  }],
}))

const options = {
  responsive:          true,
  maintainAspectRatio: false,
  cutout:              '70%',
  plugins: {
    legend: {
      position:  'right' as const,
      labels:    { font: { size: 12 }, padding: 12, boxWidth: 12 },
    },
  },
}
</script>

<template>
  <div class="h-48">
    <Doughnut :data="data" :options="options" />
  </div>
</template>