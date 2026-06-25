<!-- resources/js/Components/Stock/PartForm.vue -->
<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import CategorieController from '@/actions/App/Http/Controllers/Stock/CategorieController'
import PartController from '@/actions/App/Http/Controllers/Stock/PartController'
import SupplierController from '@/actions/App/Http/Controllers/Stock/SupplierController'
import Button from '@/Components/UI/Button.vue'
import Combobox from '@/Components/UI/Combobox.vue'
import Input from '@/Components/UI/Input.vue'
import { useToast } from '@/Composables/useToast'
import type { Part, Category, Supplier } from '@/types'

const props = defineProps<{
  part?: Part | null
  categories: Pick<Category, 'id' | 'name'>[]
  suppliers?: Pick<Supplier, 'id' | 'name'>[]
}>()

const emit = defineEmits<{ saved: []; cancel: [] }>()

const { error: toastError } = useToast()

const form = useForm({
  name: props.part?.name ?? '',
  sku: props.part?.sku ?? '',
  category_id: props.part?.category?.id ?? '',
  supplier_id: props.part?.supplier?.id ?? '',
  unit_price: props.part?.unit_price ?? 0,
  sell_price: props.part?.sell_price ?? 0,
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

const newSuppliers = ref<Pick<Supplier, 'id' | 'name'>[]>([])
const creatingSupplier = ref(false)
const creatingCategory = ref(false)

async function createCategory(name: string) {
  if (!name || creatingCategory.value) {
    return
  }

  creatingCategory.value = true

  try {
    const res = await fetch(CategorieController.store.url(), {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content,
      },
      body: JSON.stringify({ name }),
    })

    if (!res.ok) {
      const body = await res.json().catch(() => null)

      toastError(body?.message ?? 'Impossible de créer la catégorie.')

      return
    }

    const category = await res.json()
    newSuppliers.value.push({ id: category.id, name: category.name })
    form.category_id = category.id
    router.reload({ only: ['categories'] })
  } finally {
    creatingCategory.value = false
  }
}

const supplierOptions = computed(() => {
  const known = props.suppliers ?? []
  const extra = newSuppliers.value.filter(s => !known.some(k => k.id === s.id))

  return [...known, ...extra].map(s => ({ value: s.id, label: s.name }))
})

async function createSupplier(name: string) {
  if (!name || creatingSupplier.value) {
    return
  }

  creatingSupplier.value = true

  try {
    const res = await fetch(SupplierController.store.url(), {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content,
      },
      body: JSON.stringify({ name }),
    })

    if (!res.ok) {
      const body = await res.json().catch(() => null)
      toastError(body?.message ?? 'Impossible de créer le fournisseur.')

      return
    }

    const supplier = await res.json()
    newSuppliers.value.push({ id: supplier.id, name: supplier.name })
    form.supplier_id = supplier.id
    router.reload({ only: ['suppliers'] })
  } finally {
    creatingSupplier.value = false
  }
}

function generateSku() {
  const ascii = Array.from(form.name.normalize('NFD')).filter(ch => ch.codePointAt(0)! < 128).join('')
  const words = ascii.toUpperCase().replace(/[^A-Z0-9\s]/g, '').trim().split(/\s+/).filter(Boolean)
  const letters = words.filter(w => /^[A-Z]/.test(w))

  const prefix = letters.length > 1
    ? letters.map(w => w[0]).join('').slice(0, 4)
    : (letters[0] ?? words[0] ?? 'ART').slice(0, 3)

  const digits = String(Math.floor(Math.random() * 10000)).padStart(4, '0')
  const suffix = Array.from({ length: 2 }, () => String.fromCharCode(65 + Math.floor(Math.random() * 26))).join('')

  form.sku = `${prefix}-${digits}-${suffix}`
}
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
        <div class="flex items-start gap-2">
          <Input v-model="form.sku" placeholder="EX-0001-AA" :error="form.errors.sku" class="flex-1 min-w-0" />
          <Button variant="secondary" type="button" :disabled="!form.name.trim()" @click="generateSku">
            Générer
          </Button>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
        <Combobox v-model="form.category_id" :options="categoryOptions" placeholder="Rechercher une catégorie..."
          :error="form.errors.category_id" allow-create :creating="creatingCategory" @create="createCategory" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fournisseur</label>
        <Combobox v-model="form.supplier_id" :options="supplierOptions"
          placeholder="Rechercher ou créer un fournisseur..." :error="form.errors.supplier_id" allow-create
          :creating="creatingSupplier" @create="createSupplier" />
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
      Le stock se gère par dépôt depuis l'écran « Mouvements » une fois l'article créé.
    </p>

    <div class="flex justify-end gap-2 pt-2">
      <Button variant="secondary" @click="emit('cancel')">Annuler</Button>
      <Button type="submit" :loading="form.processing">
        {{ part ? 'Mettre à jour' : 'Ajouter' }}
      </Button>
    </div>
  </form>
</template>
