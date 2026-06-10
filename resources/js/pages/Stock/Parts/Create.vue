<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import PartController from '@/actions/App/Http/Controllers/Stock/PartController'
import PartForm from '@/Components/Stock/PartForm.vue'
import Button from '@/Components/UI/Button.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { Category, Supplier } from '@/types'

defineProps<{
  categories: Pick<Category, 'id' | 'name'>[]
  suppliers: Pick<Supplier, 'id' | 'name'>[]
}>()
</script>

<template>
  <AppLayout title="Ajouter une pièce">
    <div class="max-w-2xl space-y-6">

      <!-- Header -->
      <div class="flex items-center gap-3">
        <Button variant="ghost" size="sm" @click="router.visit(PartController.index.url())">
          ← Retour
        </Button>
        <h1 class="text-xl font-semibold text-gray-900">Ajouter une pièce</h1>
      </div>

      <!-- Formulaire -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <PartForm
          :categories="categories"
          :suppliers="suppliers"
          @cancel="router.visit(PartController.index.url())"
        />
      </div>

    </div>
  </AppLayout>
</template>
