<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import { login, register } from '@/routes';


const form = useForm({
    email:"",
    password:'',
    remember:false,
});

function submit() {
  form.post(login.url(), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

      <!-- Logo / titre -->
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-indigo-600">SAV Platform</h1>
        <p class="text-sm text-gray-500 mt-1">Connectez-vous à votre espace</p>
      </div>

      <!-- Erreur globale -->
      <div
        v-if="form.errors.email"
        class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700"
      >
        {{ form.errors.email }}
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <Input
            v-model="form.email"
            type="email"
            placeholder="admin@atelier.fr"
            :error="form.errors.email"
            autofocus
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

        <label class="flex items-center gap-2 cursor-pointer select-none">
          <input
            type="checkbox"
            v-model="form.remember"
            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
          />
          <span class="text-sm text-gray-600">Se souvenir de moi</span>
        </label>

        <Button
          type="submit"
          :loading="form.processing"
          :disabled="form.processing"
          class="w-full justify-center"
        >
          Se connecter
        </Button>

        <p class="text-center text-sm text-gray-500">
          Pas encore inscrit ?
          <a :href="register.url()" class="text-indigo-600 font-medium hover:underline">Créer un atelier</a>
        </p>
      </form>
    </div>
  </div>
</template>