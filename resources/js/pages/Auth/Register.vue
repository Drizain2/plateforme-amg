<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import RegisterController from '@/actions/App/Http/Controllers/Auth/RegisterController';
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
    plan_id: props.plans[0]?.id ?? null as number | null,
});

function submit() {
    form.post(RegisterController.store.url(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

      <!-- Logo / titre -->
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-indigo-600">SAV Platform</h1>
        <p class="text-sm text-gray-500 mt-1">Créez votre atelier</p>
      </div>

      <!-- Erreur globale -->
      <div
        v-if="form.errors.email"
        class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700"
      >
        {{ form.errors.email }}
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'atelier</label>
            <Input
              v-model="form.shop_name"
              placeholder="Atelier Demo"
              :error="form.errors.shop_name"
              autofocus
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Votre nom</label>
            <Input
              v-model="form.name"
              placeholder="Jean Dupont"
              :error="form.errors.name"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
            <Input
              v-model="form.phone"
              placeholder="06 12 34 56 78"
              :error="form.errors.phone"
            />
          </div>

          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <Input
              v-model="form.email"
              type="email"
              placeholder="admin@atelier.fr"
              :error="form.errors.email"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
            <Input
              v-model="form.password"
              type="password"
              placeholder="••••••••"
              :error="form.errors.password"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
            <Input
              v-model="form.password_confirmation"
              type="password"
              placeholder="••••••••"
              :error="form.errors.password_confirmation"
            />
          </div>
        </div>

        <!-- Offres d'abonnement -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Choisissez une offre</label>
          <p v-if="form.errors.plan_id" class="text-xs text-red-500 mb-2">{{ form.errors.plan_id }}</p>

          <div class="grid grid-cols-3 gap-4">
            <button
              v-for="plan in plans"
              :key="plan.id"
              type="button"
              @click="form.plan_id = plan.id"
              :class="[
                'text-left rounded-xl border p-4 space-y-3 transition',
                form.plan_id === plan.id
                  ? 'border-indigo-400 ring-2 ring-indigo-100'
                  : 'border-gray-200 hover:border-gray-300'
              ]"
            >
              <div>
                <p class="font-semibold text-gray-900">{{ plan.name }}</p>
                <p class="text-lg font-bold text-indigo-600">
                  {{ plan.price > 0 ? `${plan.price} €/mois` : 'Gratuit' }}
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

        <Button
          type="submit"
          :loading="form.processing"
          :disabled="form.processing"
          class="w-full justify-center"
        >
          Créer mon atelier
        </Button>

        <p class="text-center text-sm text-gray-500">
          Déjà inscrit ?
          <a :href="login.url()" class="text-indigo-600 font-medium hover:underline">Se connecter</a>
        </p>
      </form>
    </div>
  </div>
</template>
