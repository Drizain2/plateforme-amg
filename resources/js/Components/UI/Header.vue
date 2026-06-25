<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { useSidebar } from '@/Composables/useSidebar'
import NotificationBell from './NotificationBell.vue';

defineProps<{
    title: string
}>()

const page = usePage()
const { toggleMobile } = useSidebar()
const auth = computed(() => page.props.auth)
const isAdmin = computed(() => {
    const role = auth.value.user?.roles?.[0]?.name

    return role === 'admin' || role === 'super_admin'
})
const depotDropdownOpen = ref(false)

const depotLabel = computed(() => {
    if (auth.value.isGlobalView) {
        return 'Vue globale'
    }

    return auth.value.depotActive?.name ?? 'Aucun dépôt'
})

function switchDepot(depotId: number | null) {
    router.post('/depot/switch', { depot_id: depotId }, {
        onSuccess: () => {
            depotDropdownOpen.value = false
        },
        preserveScroll: false
    })
}
</script>

<template>
    <header class="bg-white border-b border-gray-200 px-4 lg:px-8 py-4 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <button type="button"
                class="lg:hidden flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 hover:bg-gray-100 transition shrink-0"
                title="Ouvrir le menu" @click="toggleMobile">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <h2 class="text-sm font-medium text-gray-700 truncate">{{ title }}</h2>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <div v-if="!isAdmin && auth.depotActive"
                class="flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-700">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span class="font-medium">{{ auth.depotActive.name }}</span>
            </div>

            <div v-if="isAdmin" class="relative">
                <button type="button"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                    @click="depotDropdownOpen = !depotDropdownOpen">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span :class="auth.isGlobalView ? 'text-indigo-600 font-semibold' : ''">{{ depotLabel }}</span>
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div v-if="depotDropdownOpen"
                    class="absolute right-0 mt-1 w-56 bg-white border border-gray-200 rounded-xl shadow-lg z-50 py-1">
                    <button type="button"
                        class="w-full text-left px-4 py-2.5 text-sm flex items-center gap-2 hover:bg-gray-50 transition"
                        :class="auth.isGlobalView ? 'text-indigo-600 font-semibold' : 'text-gray-700'"
                        @click="switchDepot(null)">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                        </svg>
                        Vue globale
                        <svg v-if="auth.isGlobalView" class="w-3.5 h-3.5 ml-auto text-indigo-600" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div class="border-t border-gray-100 my-1" />

                    <button v-for="depot in auth.depots" :key="depot.id" type="button"
                        class="w-full text-left px-4 py-2.5 text-sm flex items-center gap-2 hover:bg-gray-50 transition"
                        :class="auth.depotActive?.id === depot.id ? 'text-indigo-600 font-semibold' : 'text-gray-700'"
                        @click="switchDepot(depot.id)">
                        <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
                        </svg>
                        {{ depot.name }}
                        <svg v-if="auth.depotActive?.id === depot.id" class="w-3.5 h-3.5 ml-auto text-indigo-600"
                            fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div v-if="auth.depots.length === 0" class="px-4 py-3 text-sm text-gray-400 italic">
                        Aucun dépôt disponible
                    </div>
                </div>

                <div v-if="depotDropdownOpen" class="fixed inset-0 z-40" @click="depotDropdownOpen = false" />
            </div>

            <NotificationBell />
        </div>
    </header>
</template>