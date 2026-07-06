<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import TwoFactorController from '@/actions/App/Http/Controllers/Auth/TwoFactorController'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'

const useRecovery = ref(false)

const form = useForm({
  code: '',
  recovery_code: '',
})

function submit() {
  form.post(TwoFactorController.challenge.url(), {
    onFinish: () => form.reset('code', 'recovery_code'),
  })
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

      <div class="text-center mb-6">
        <div class="w-12 h-12 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-3">
          <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.955 11.955 0 012.25 10.5c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.572-.598-3.75h-.152c-3.196 0-6.1-1.25-8.25-3.286z" />
          </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900">Vérification à deux facteurs</h1>
        <p class="text-sm text-gray-500 mt-1">
          <template v-if="!useRecovery">
            Saisissez le code à 6 chiffres généré par votre application d'authentification.
          </template>
          <template v-else>
            Saisissez l'un de vos codes de récupération.
          </template>
        </p>
      </div>

      <form @submit.prevent="submit" class="space-y-4">

        <div v-if="!useRecovery">
          <label class="block text-sm font-medium text-gray-700 mb-1">Code d'authentification</label>
          <Input
            v-model="form.code"
            type="text"
            inputmode="numeric"
            autocomplete="one-time-code"
            placeholder="000000"
            autofocus
          />
          <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</p>
        </div>

        <div v-else>
          <label class="block text-sm font-medium text-gray-700 mb-1">Code de récupération</label>
          <Input
            v-model="form.recovery_code"
            type="text"
            autocomplete="off"
            placeholder="xxxx-xxxx-xxxx"
          />
          <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</p>
        </div>

        <Button type="submit" :loading="form.processing" class="w-full">
          Vérifier
        </Button>

        <button
          type="button"
          class="w-full text-center text-sm text-indigo-600 hover:text-indigo-700 transition"
          @click="useRecovery = !useRecovery; form.reset()"
        >
          {{ useRecovery ? 'Utiliser le code TOTP' : 'Utiliser un code de récupération' }}
        </button>
      </form>
    </div>
  </div>
</template>
