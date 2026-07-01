<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import RegisterController from '@/actions/App/Http/Controllers/Auth/RegisterController';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import { login } from '@/routes';
import type { Plan } from '@/types';

const props = defineProps<{
    plans: Plan[]
}>()

const form = useForm({
    shop_name: '',
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    plan_id: null as number | null,
    logo: null as File | null,
});

const isStockOnly = (plan: Plan) => (plan.disabled_modules ?? []).includes('tickets')
const track = ref<'full' | 'stock'>('full')
const filteredPlans = computed(() =>
    props.plans.filter(plan => isStockOnly(plan) === (track.value === 'stock'))
)

watch(filteredPlans, (plans) => {
    if (!plans.some(p => p.id === form.plan_id)) {
        form.plan_id = plans[0]?.id ?? null
    }
}, { immediate: true })

const logoPreview = ref<string | null>(null)
const logoInput = ref<HTMLInputElement | null>(null)

function handleLogoChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]

    if (!file) {
        return
    }

    form.logo = file
    logoPreview.value = URL.createObjectURL(file)
}

function handleLogoDrop(e: DragEvent) {
    const file = e.dataTransfer?.files?.[0]

    if (!file || !file.type.startsWith('image/')) {
        return
    }

    form.logo = file
    logoPreview.value = URL.createObjectURL(file)
}

function removeLogo() {
    form.logo = null
    logoPreview.value = null

    if (logoInput.value) {
        logoInput.value.value = ''
    }
}

function submit() {
    form.post(RegisterController.store.url(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}

const fmt = (v: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(v)
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">

      <!-- Header -->
      <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-7 text-white">
        <div class="flex items-center gap-3 mb-1">
          <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
          <h1 class="text-2xl font-bold">SAV Platform</h1>
        </div>
        <p class="text-indigo-100 text-sm">Créez votre espace professionnel en quelques minutes</p>
      </div>

      <div class="p-8">
        <!-- Erreur globale -->
        <div v-if="form.errors.email" class="mb-6 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
          {{ form.errors.email }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
          <div class="grid grid-cols-2 gap-4">

            <!-- Nom de l'entreprise -->
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'entreprise</label>
              <Input v-model="form.shop_name" placeholder="Mon Entreprise" :error="form.errors.shop_name" autofocus />
            </div>

            <!-- Logo upload -->
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Logo de l'entreprise
                <span class="text-gray-400 font-normal">(optionnel)</span>
              </label>

              <div v-if="!logoPreview"
                class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/50 transition-colors"
                @click="logoInput?.click()"
                @dragover.prevent
                @drop.prevent="handleLogoDrop"
              >
                <svg class="w-8 h-8 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm text-gray-500">Cliquez ou déposez votre logo ici</p>
                <p class="text-xs text-gray-400 mt-1">PNG, JPG, WebP — max 2 Mo</p>
              </div>

              <div v-else class="flex items-center gap-4 p-4 border border-gray-200 rounded-xl">
                <img :src="logoPreview" alt="Aperçu logo" class="h-16 w-auto object-contain rounded-lg bg-gray-50 p-1" />
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-700 truncate">{{ form.logo?.name }}</p>
                  <p class="text-xs text-gray-400">{{ form.logo ? Math.round(form.logo.size / 1024) : 0 }} Ko</p>
                </div>
                <button type="button" @click="removeLogo"
                  class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition shrink-0">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <input ref="logoInput" type="file" class="hidden" accept="image/png,image/jpeg,image/webp" @change="handleLogoChange" />
              <p v-if="form.errors.logo" class="mt-1 text-xs text-red-500">{{ form.errors.logo }}</p>
            </div>

            <!-- Nom -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Votre nom</label>
              <Input v-model="form.name" placeholder="Jean Dupont" :error="form.errors.name" />
            </div>

            <!-- Téléphone -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
              <Input v-model="form.phone" placeholder="06 12 34 56 78" :error="form.errors.phone" />
            </div>

            <!-- Email -->
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <Input v-model="form.email" type="email" placeholder="admin@atelier.fr" :error="form.errors.email" />
            </div>

            <!-- Mot de passe -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
              <Input v-model="form.password" type="password" placeholder="••••••••" :error="form.errors.password" />
            </div>

            <!-- Confirmation -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
              <Input v-model="form.password_confirmation" type="password" placeholder="••••••••" :error="form.errors.password_confirmation" />
            </div>
          </div>

          <!-- Offres d'abonnement -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Quelle est votre activité ?</label>
            <div class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit mb-4">
              <button type="button" @click="track = 'full'"
                :class="['px-4 py-1.5 rounded-lg text-sm font-medium transition', track === 'full' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                Atelier (SAV + Stock)
              </button>
              <button type="button" @click="track = 'stock'"
                :class="['px-4 py-1.5 rounded-lg text-sm font-medium transition', track === 'stock' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                Stock seul
              </button>
            </div>

            <label class="block text-sm font-medium text-gray-700 mb-2">Choisissez une offre</label>
            <p v-if="form.errors.plan_id" class="text-xs text-red-500 mb-2">{{ form.errors.plan_id }}</p>

            <div :class="['grid gap-4', filteredPlans.length >= 3 ? 'grid-cols-3' : 'grid-cols-2']">
              <button v-for="plan in filteredPlans" :key="plan.id" type="button" @click="form.plan_id = plan.id"
                :class="[
                  'text-left rounded-xl border p-4 space-y-3 transition-all',
                  form.plan_id === plan.id
                    ? 'border-indigo-500 ring-2 ring-indigo-100 bg-indigo-50/40'
                    : 'border-gray-200 hover:border-gray-300 hover:shadow-sm'
                ]">
                <div>
                  <div class="flex items-center justify-between mb-1">
                    <p class="font-semibold text-gray-900">{{ plan.name }}</p>
                    <div v-if="form.plan_id === plan.id"
                      class="w-4 h-4 rounded-full bg-indigo-500 flex items-center justify-center">
                      <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                      </svg>
                    </div>
                  </div>
                  <p class="text-lg font-bold text-indigo-600">
                    {{ plan.price > 0 ? `${fmt(plan.price)}/mois` : 'Gratuit' }}
                  </p>
                </div>
                <ul class="space-y-1">
                  <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-1.5 text-xs text-gray-500">
                    <svg class="w-3.5 h-3.5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ feature }}
                  </li>
                </ul>
              </button>
            </div>
          </div>

          <Button type="submit" :loading="form.processing" :disabled="form.processing" class="w-full justify-center">
            Créer mon espace
          </Button>

          <p class="text-center text-sm text-gray-500">
            Déjà inscrit ?
            <a :href="login.url()" class="text-indigo-600 font-medium hover:underline">Se connecter</a>
          </p>
        </form>
      </div>
    </div>
  </div>
</template>
