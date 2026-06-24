<!-- resources/js/Components/UI/Combobox.vue -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'

type Option = { value: string | number; label: string }

const props = defineProps<{
  modelValue?: string | number | null
  options: Option[]
  placeholder?: string
  error?: string
  allowCreate?: boolean
  creating?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
  create: [name: string]
}>()

const query = ref('')
const open = ref(false)

const selectedLabel = computed(() => props.options.find(o => o.value === props.modelValue)?.label ?? '')

watch(selectedLabel, label => {
  query.value = label
}, { immediate: true })

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase()

  if (!q) {
    return props.options
  }

  return props.options.filter(o => o.label.toLowerCase().includes(q))
})

const exactMatch = computed(() =>
  props.options.some(o => o.label.toLowerCase() === query.value.trim().toLowerCase()),
)

const showCreate = computed(() =>
  props.allowCreate && query.value.trim().length > 0 && !exactMatch.value,
)

function onInput(value: string) {
  query.value = value
  open.value = true

  if (props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== '') {
    emit('update:modelValue', '')
  }
}

function select(opt: Option) {
  query.value = opt.label
  open.value = false
  emit('update:modelValue', opt.value)
}

function requestCreate() {
  if (props.creating) {
    return
  }

  emit('create', query.value.trim())
  open.value = false
}

function onBlur() {
  setTimeout(() => {
    open.value = false
    query.value = selectedLabel.value
  }, 150)
}
</script>

<template>
  <div class="relative flex flex-col gap-1">
    <input
      :value="query"
      type="text"
      :placeholder="placeholder ?? 'Rechercher...'"
      :disabled="creating"
      autocomplete="off"
      @input="onInput(($event.target as HTMLInputElement).value)"
      @focus="open = true"
      @blur="onBlur"
      :class="[
        'w-full rounded-lg border px-3 py-2 text-sm outline-none transition bg-white disabled:bg-gray-50 disabled:text-gray-400',
        'focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
        error ? 'border-red-400' : 'border-gray-300'
      ]"
    />

    <div
      v-if="open && (filtered.length > 0 || showCreate)"
      class="absolute z-10 top-full mt-1 max-h-56 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg text-sm"
    >
      <button
        v-for="opt in filtered"
        :key="opt.value"
        type="button"
        class="block w-full px-3 py-2 text-left hover:bg-indigo-50"
        :class="{ 'bg-indigo-50 font-medium text-indigo-700': opt.value === modelValue }"
        @mousedown.prevent="select(opt)"
      >
        {{ opt.label }}
      </button>

      <button
        v-if="showCreate"
        type="button"
        class="block w-full px-3 py-2 text-left text-indigo-600 hover:bg-indigo-50 border-t border-gray-100"
        @mousedown.prevent="requestCreate"
      >
        + Créer « {{ query.trim() }} »
      </button>
    </div>

    <span v-if="error" class="text-xs text-red-500">{{ error }}</span>
  </div>
</template>
