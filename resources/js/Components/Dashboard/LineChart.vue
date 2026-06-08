<!-- resources/js/Components/Dashboard/LineChart.vue -->
<script setup lang="ts">
import {
  Chart as ChartJS, LineElement, PointElement,
  LinearScale, CategoryScale, Filler, Tooltip
} from 'chart.js'
import { computed } from 'vue'
import { Line } from 'vue-chartjs'

ChartJS.register(LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip)

const props = defineProps<{
  labels: string[]
  values: number[]
  label?: string
}>()

const data = computed(() => ({
  labels: props.labels,
  datasets: [{
    label:           props.label ?? '',
    data:            props.values,
    borderColor:     '#6366f1',
    backgroundColor: 'rgba(99,102,241,0.08)',
    borderWidth:     2,
    pointRadius:     3,
    pointHoverRadius:5,
    tension:         0.4,
    fill:            true,
  }],
}))

const options = {
  responsive:          true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { mode: 'index' as const } },
  scales: {
    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
    y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { precision: 0 } },
  },
}
</script>

<template>
  <div class="h-48">
    <Line :data="data" :options="options" />
  </div>
</template>