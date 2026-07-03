<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import type { Plan } from '@/types'

defineProps<{
  plans: Plan[]
}>()

const fmt = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v)
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
      <span class="text-xl font-bold text-indigo-600">AMG Plateforme</span>
      <div class="flex items-center gap-3">
        <Link href="/login" class="text-sm text-gray-600 hover:text-gray-900 transition">Se connecter</Link>
        <Link href="/register"
          class="text-sm bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
          Commencer
        </Link>
      </div>
    </header>

    <!-- Hero -->
    <div class="py-16 text-center px-4">
      <h1 class="text-4xl font-bold text-gray-900 mb-4">Choisissez votre offre</h1>
      <p class="text-lg text-gray-500 max-w-xl mx-auto">
        Gérez votre atelier de réparation simplement. Passez à une offre supérieure à tout moment.
      </p>
    </div>

    <!-- Plans -->
    <div class="max-w-5xl mx-auto px-4 pb-20">
      <div class="grid gap-6" :class="plans.length === 3 ? 'grid-cols-3' : plans.length === 2 ? 'grid-cols-2' : 'grid-cols-1 max-w-sm mx-auto'">
        <div
          v-for="plan in plans"
          :key="plan.id"
          :class="[
            'bg-white rounded-2xl border p-8 flex flex-col gap-6',
            plan.slug === 'pro' ? 'border-indigo-400 ring-2 ring-indigo-100 shadow-lg' : 'border-gray-200 shadow-sm'
          ]"
        >
          <!-- Badge populaire -->
          <div v-if="plan.slug === 'pro'" class="self-start">
            <span class="text-xs font-semibold bg-indigo-600 text-white px-3 py-1 rounded-full">Populaire</span>
          </div>

          <div>
            <h2 class="text-xl font-bold text-gray-900">{{ plan.name }}</h2>
            <p v-if="plan.description" class="text-sm text-gray-500 mt-1">{{ plan.description }}</p>
          </div>

          <div>
            <span v-if="plan.price === 0" class="text-4xl font-bold text-gray-900">Gratuit</span>
            <template v-else>
              <span class="text-4xl font-bold text-gray-900">{{ fmt(plan.price) }}</span>
              <span class="text-gray-400 text-sm">/mois</span>
              <p class="text-xs text-green-600 mt-1">ou {{ fmt(plan.price * 10) }}/an (2 mois offerts)</p>
            </template>
          </div>

          <ul class="space-y-2.5 flex-1">
            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2 text-sm text-gray-600">
              <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              {{ feature }}
            </li>
          </ul>

          <Link
            href="/register"
            :class="[
              'text-center py-3 rounded-xl text-sm font-semibold transition',
              plan.slug === 'pro'
                ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            Commencer avec {{ plan.name }}
          </Link>
        </div>
      </div>

      <!-- Note paiement manuel -->
      <p class="text-center text-sm text-gray-400 mt-10">
        Paiement par virement ou Mobile Money. Votre abonnement est activé après vérification de votre paiement.
      </p>
    </div>
  </div>
</template>
