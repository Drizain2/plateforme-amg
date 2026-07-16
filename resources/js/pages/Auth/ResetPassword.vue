<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';

const props = defineProps<{
  token: string;
  email: string;
}>();

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

function submit() {
  form.post('/reset-password', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

      <!-- Logo / titre -->
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-indigo-600">Stockora</h1>
        <p class="text-sm text-gray-500 mt-1">Définissez votre nouveau mot de passe</p>
      </div>

      <!-- Erreur globale -->
      <div v-if="form.errors.email"
        class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
        {{ form.errors.email }}
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <Input v-model="form.email" type="email" :error="form.errors.email" readonly />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
          <Input v-model="form.password" type="password" placeholder="••••••••" :error="form.errors.password"
            autofocus />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
          <Input v-model="form.password_confirmation" type="password" placeholder="••••••••"
            :error="form.errors.password_confirmation" />
        </div>

        <Button type="submit" :loading="form.processing" :disabled="form.processing" class="w-full justify-center">
          Réinitialiser le mot de passe
        </Button>
      </form>
    </div>
  </div>
</template>
