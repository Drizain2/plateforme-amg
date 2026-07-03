<!-- resources/js/Pages/Tickets/Index.vue -->
<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import TicketController from '@/actions/App/Http/Controllers/Ticket/TicketController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Select from '@/Components/UI/Select.vue'
import { useFilters } from '@/Composables/useFilters'
import { usePermission } from '@/Composables/usePermission'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { BadgeVariant, PaginatedResource, Ticket } from '@/types'


const props = defineProps<{
    tickets: PaginatedResource<Ticket>
    filters: Record<string, string>
    technicians: { id: number; name: string }[]
    statuses: { value: string; label: string }[]
    priorities: { value: string; label: string }[]
}>()

const { applyFilter } = useFilters('tickets.index')
const { can } = usePermission()

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')
const priority = ref(props.filters.priority ?? '')
const technicianId = ref(props.filters.technician_id ?? '')

watch([search, status, priority, technicianId], () => {
    applyFilter({
        search: search.value || undefined,
        status: status.value || undefined,
        priority: priority.value || undefined,
        technician_id: technicianId.value || undefined,
    })
})

function goToPage(url: string | null) {
    if (!url) {
        return
    }

    router.visit(url, { preserveScroll: true, preserveState: true })
}

const technicianOptions = computed(() => props.technicians.map(t => ({ value: t.id, label: t.name })))
const statusOptions = computed(() => props.statuses.map(s => ({ value: s.value, label: s.label })))
const priorityOptions = computed(() => props.priorities.map(p => ({ value: p.value, label: p.label })))
</script>

<template>
    <AppLayout title="Tickets SAV">
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Tickets SAV</h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ tickets.meta.total }} ticket{{ tickets.meta.total > 1 ? 's' : '' }}
                    </p>
                </div>
                <Link v-show="can('tickets.create')" :href="TicketController.create.url()">
                    <Button>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nouveau ticket
                    </Button>
                </Link>
            </div>

            <!-- Filtres -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" :class="technicians.length ? 'lg:grid-cols-4' : 'lg:grid-cols-3'">
                    <Input v-model="search" placeholder="Réf, client, appareil..." />
                    <Select v-model="status" :options="statusOptions" placeholder="Tous statuts" />
                    <Select v-model="priority" :options="priorityOptions" placeholder="Toutes priorités" />
                    <Select v-if="technicians.length" v-model="technicianId" :options="technicianOptions" placeholder="Tous techniciens" />
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                    Référence</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                    Client</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                    Appareil</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                    Dépôt</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                    Statut</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                    Priorité</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                    Technicien</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                    Créé le</th>
                                <th class="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="tickets.data.length === 0">
                                <td colspan="9" class="px-4 py-12 text-center text-gray-400">
                                    Aucun ticket trouvé
                                </td>
                            </tr>
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 font-mono text-xs font-medium text-gray-900">
                                    {{ ticket.reference }}
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ ticket.customer?.name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ ticket.device?.full_name }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ ticket.depot?.name }}</td>
                                <td class="px-4 py-3">
                                    <Badge :variant="ticket.status_color as BadgeVariant">
                                        {{ ticket.status_label }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :variant="ticket.priority_color as BadgeVariant">
                                        {{ ticket.priority_label }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    {{ ticket.technicien?.name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-400 text-xs">{{ ticket.created_at }}</td>
                                <td class="px-4 py-3">
                                    <Link :href="TicketController.show.url(ticket.id)">
                                        <Button variant="ghost" size="sm">Ouvrir</Button>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tickets.meta.last_page > 1"
                    class="px-4 py-3 border-t flex items-center justify-between text-sm text-gray-600">
                    <span>{{ tickets.meta.from }}–{{ tickets.meta.to }} sur {{ tickets.meta.total }}</span>
                    <div class="flex gap-1">
                        <Button variant="secondary" size="sm" :disabled="!tickets.links.prev"
                            @click="goToPage(tickets.links.prev)">← Précédent</Button>
                        <Button variant="secondary" size="sm" :disabled="!tickets.links.next"
                            @click="goToPage(tickets.links.next)">Suivant →</Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>