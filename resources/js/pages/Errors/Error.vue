<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

const props = defineProps<{ status: number }>()

const messages: Record<number, { title: string; description: string }> = {
    403: { title: 'Accès refusé', description: "Vous n'avez pas les droits pour accéder à cette page." },
    404: { title: 'Page introuvable', description: "La page que vous cherchez n'existe pas ou a été déplacée." },
    500: { title: 'Erreur serveur', description: 'Une erreur interne est survenue. Réessayez dans quelques instants.' },
    503: { title: 'Service indisponible', description: "Le service est temporairement indisponible. Réessayez dans quelques instants." },
}

const { title, description } = messages[props.status] ?? { title: 'Erreur', description: 'Une erreur est survenue.' }
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
        <div class="text-center">
            <p class="text-6xl font-bold text-indigo-600 mb-4">{{ status }}</p>
            <h1 class="text-2xl font-semibold text-gray-900 mb-2">{{ title }}</h1>
            <p class="text-gray-500 mb-8 max-w-sm mx-auto">{{ description }}</p>
            <Link
                href="/dashboard"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Retour au tableau de bord
            </Link>
        </div>
    </div>
</template>
