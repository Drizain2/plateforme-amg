<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import EmailVerificationController from '@/actions/App/Http/Controllers/Auth/EmailVerificationController';
import LoginController from '@/actions/App/Http/Controllers/Auth/LoginController';
import Button from '@/Components/UI/Button.vue'

defineProps<{
    status?: string
}>()

const form = useForm({})

function resend() {
    form.post(EmailVerificationController.resend.url())
}
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

            <div class="text-center mb-8">
                <div class="mx-auto mb-4 w-16 h-16 rounded-full bg-indigo-50 flex items-center justify-center">
                    <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Vérifiez votre email</h1>
                <p class="text-sm text-gray-500 mt-2">
                    Un lien de vérification vous a été envoyé à votre adresse email.
                    Cliquez dessus pour activer votre compte.
                </p>
            </div>

            <div
                v-if="status === 'verification-link-sent'"
                class="mb-6 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-700 text-center"
            >
                Un nouvel email de vérification a été envoyé.
            </div>

            <div class="space-y-3">
                <Button
                    type="button"
                    :loading="form.processing"
                    :disabled="form.processing"
                    class="w-full justify-center"
                    @click="resend"
                >
                    Renvoyer l'email de vérification
                </Button>

                <a
                    :href="LoginController.logout.url()"
                    class="block text-center text-sm text-gray-500 hover:text-gray-700"
                    @click.prevent="$inertia.post(LoginController.logout.url())"
                >
                    Se déconnecter
                </a>
            </div>
        </div>
    </div>
</template>
