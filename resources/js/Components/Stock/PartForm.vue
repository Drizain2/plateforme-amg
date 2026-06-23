<!-- resources/js/Components/Stock/PartForm.vue -->
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import PartController from '@/actions/App/Http/Controllers/Stock/PartController'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Select from '@/Components/UI/Select.vue'
import type { Part, Category, Supplier } from '@/types'

const props = defineProps<{
  part?: Part | null
  categories: Pick<Category, 'id' | 'name'>[]
  suppliers?: Pick<Supplier, 'id' | 'name'>[]
}>()

const emit = defineEmits<{ saved: []; cancel: [] }>()

const form = useForm({
  name:        props.part?.name ?? '',
  sku:         props.part?.sku ?? '',
  category_id: props.part?.category?.id ?? '',
  supplier_id: props.part?.supplier?.id ?? '',
  unit_price:  props.part?.unit_price ?? 0,
  sell_price:  props.part?.sell_price ?? 0,
})

function submit() {
  if (props.part) {
    form.put(PartController.update.url(props.part.id), {
      onSuccess: () => emit('saved'),
    })
  } else {
    form.post(PartController.store.url(), {
      onSuccess: () => emit('saved'),
    })
  }
}

const categoryOptions = computed(() => props.categories.map(c => ({ value: c.id, label: c.name })))
const supplierOptions = computed(() => (props.suppliers ?? []).map(s => ({ value: s.id, label: s.name })))
</script>

<template>
  <form @submit.prevent="submit" class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
      <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
        <Input v-model="form.name" placeholder="Ex: Écran iPhone 14" :error="form.errors.name" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
        <Input v-model="form.sku" placeholder="EX-0001-AA" :error="form.errors.sku" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
        <Select v-model="form.category_id" :options="categoryOptions" placeholder="Aucune" :error="form.errors.category_id" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fournisseur</label>
        <Select v-model="form.supplier_id" :options="supplierOptions" placeholder="Aucun" :error="form.errors.supplier_id" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Prix d'achat (FCFA)</label>
        <Input v-model="form.unit_price" type="number" step="0.01" :error="form.errors.unit_price" />
      </div>

      <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Prix de vente (FCFA)</label>
        <Input v-model="form.sell_price" type="number" step="0.01" :error="form.errors.sell_price" />
      </div>
    </div>

    <p class="text-xs text-gray-500">
      Le stock se gère par dépôt depuis l'écran « Mouvements » une fois la pièce créée.
    </p>

    <div class="flex justify-end gap-2 pt-2">
      <Button variant="secondary" @click="emit('cancel')">Annuler</Button>
      <Button type="submit" :loading="form.processing">
        {{ part ? 'Mettre à jour' : 'Ajouter' }}
      </Button>
    </div>
  </form>
</template>
