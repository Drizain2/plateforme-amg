<!-- resources/js/Pages/Tickets/Show.vue -->
<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import TicketController from '@/actions/App/Http/Controllers/Ticket/TicketController'
import type { BadgeVariant } from '@/Components/UI/Badge.vue';
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import Select from '@/Components/UI/Select.vue'
// import { usePermission } from '@/Composables/usePermission'
import { useToast } from '@/Composables/useToast'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { Ticket } from '@/types'

const props = defineProps<{
    ticket: Ticket
    technicians: { id: number; name: string }[]
    depotParts: { id: number; name: string; quantity: number; unit_price: number }[]
}>()

const { success, error } = useToast()
// const { isAdmin } = usePermission()
const page = usePage()

const ticketData = computed(() => props.ticket)

watch(() => page.props.flash, (flash) => {
    if (flash.success) {
        success(flash.success)
    }

    if (flash.error) {
        error(flash.error)
    }
}, { immediate: true })

// Transition statut
const transitionForm = useForm({ status: '', note: '' })
const showTransitionModal = ref(false)
const selectedStatus = ref<{ value: string; label: string } | null>(null)

function openTransition(s: { value: string; label: string }) {
    selectedStatus.value = s
    transitionForm.status = s.value
    transitionForm.note = ''
    showTransitionModal.value = true
}

function submitTransition() {
    transitionForm.post(TicketController.transition.url({ ticket: ticketData.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showTransitionModal.value = false; transitionForm.reset()
        },
    })
}

// Note
const noteForm = useForm({ note: '' })
const showNoteModal = ref(false)

function submitNote() {
    noteForm.post(TicketController.addNote.url({ ticket: ticketData.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showNoteModal.value = false; noteForm.reset()
        },
    })
}

// Consommer pièce
const partForm = useForm({ part_id: '', quantity: 1 })
const showPartModal = ref(false)

const partOptions = computed(() =>
    props.depotParts.map(p => ({ value: p.id, label: `${p.name} (stock: ${p.quantity})` }))
)

function submitPart() {
    partForm.post(TicketController.consumePart.url({ ticket: ticketData.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showPartModal.value = false; partForm.reset()
        },
    })
}

// Icône événement
function eventIcon(type: string): string {
    return ({
        status_changed: '🔄',
        note_added: '📝',
        part_consumed: '🔧',
        diagnosis_set: '🔍',
        tech_assigned: '👤',
    } as Record<string, string>)[type] ?? '•'
}

const totalParts = computed(() =>
    (ticketData.value.parts ?? []).reduce((sum, p) => sum + p.total, 0)
)

const fmt = (v: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(v)
</script>

<template>
    <AppLayout :title="`Ticket ${ticketData.reference}`">
        <div class="space-y-6 max-w-5xl">

            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl font-semibold text-gray-900 font-mono">{{ ticketData.reference }}</h1>
                        <Badge :variant="ticketData.status_color as BadgeVariant">{{ ticketData.status_label }}</Badge>
                        <Badge :variant="ticketData.priority_color as BadgeVariant">{{ ticketData.priority_label }}</Badge>
                    </div>
                    <p class="text-sm text-gray-400 mt-1">Créé le {{ ticketData.created_at }}</p>
                </div>

                <!-- Actions statut -->
                <div class="flex gap-2">
                    <Button v-for="s in ticketData.next_statuses" :key="s.value" variant="secondary" size="sm"
                        @click="openTransition(s)">
                        → {{ s.label }}
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6">

                <!-- Colonne principale -->
                <div class="col-span-2 space-y-6">

                    <!-- Infos client + appareil -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Client</p>
                            <p class="font-medium text-gray-900">{{ ticketData.customer?.name }}</p>
                            <p v-if="ticketData.customer?.email" class="text-sm text-gray-500">{{ ticketData.customer.email }}
                            </p>
                            <p v-if="ticketData.customer?.phone" class="text-sm text-gray-500">{{ ticketData.customer.phone }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Appareil</p>
                            <p class="font-medium text-gray-900">{{ ticketData.device?.full_name }}</p>
                            <p class="text-sm text-gray-500 capitalize">{{ ticketData.device?.type }}</p>
                            <p v-if="ticketData.device?.serial_number" class="text-xs text-gray-400 font-mono">{{
                                ticketData.device.serial_number }}</p>
                            <p v-if="ticketData.device?.condition_in" class="text-xs text-gray-400 mt-1 italic">{{
                                ticketData.device.condition_in }}</p>
                        </div>
                    </div>

                    <!-- Description + diagnostic -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Description</p>
                            <p class="text-sm text-gray-700">{{ ticketData.description }}</p>
                        </div>
                        <div v-if="ticketData.diagnosis">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Diagnostic</p>
                            <p class="text-sm text-gray-700">{{ ticketData.diagnosis }}</p>
                            <p v-if="ticketData.estimated_price" class="text-sm font-medium text-indigo-600 mt-1">
                                Devis estimé : {{ fmt(ticketData.estimated_price) }}
                            </p>
                        </div>
                    </div>

                    <!-- Pièces consommées -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Pièces utilisées</p>
                            <Button variant="ghost" size="sm" @click="showPartModal = true">+ Ajouter</Button>
                        </div>
                        <div v-if="ticketData.parts?.length" class="divide-y divide-gray-100">
                            <div v-for="p in ticketData.parts" :key="p.id" class="flex items-center justify-between py-2">
                                <span class="text-sm text-gray-700">{{ p.part.name }}</span>
                                <span class="text-sm text-gray-500">
                                    {{ p.quantity }} × {{ fmt(p.unit_price) }} =
                                    <span class="font-medium text-gray-900">{{ fmt(p.total) }}</span>
                                </span>
                            </div>
                            <div class="flex justify-end pt-2">
                                <span class="text-sm font-semibold text-gray-900">
                                    Total pièces : {{ fmt(totalParts) }}
                                </span>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 italic">Aucune pièce consommée</p>
                    </div>

                    <!-- Timeline événements -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Historique</p>
                            <Button variant="ghost" size="sm" @click="showNoteModal = true">+ Note</Button>
                        </div>
                        <div class="space-y-3">
                            <div v-for="event in ticketData.events" :key="event.id" class="flex gap-3">
                                <span class="text-lg leading-none mt-0.5">{{ eventIcon(event.type) }}</span>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-700">{{ event.note }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ event.user?.name ?? 'Système' }} · {{ event.created_at }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Colonne latérale -->
                <div class="space-y-4">
                    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Détails</p>

                        <div>
                            <p class="text-xs text-gray-400">Dépôt</p>
                            <p class="text-sm font-medium text-gray-900">{{ ticketData.depot?.name }}</p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-400">Technicien</p>
                            <p class="text-sm font-medium text-gray-900">{{ ticketData.technicien?.name ?? 'Non assigné' }}
                            </p>
                        </div>

                        <div v-if="ticketData.estimated_return_date">
                            <p class="text-xs text-gray-400">Retour estimé</p>
                            <p class="text-sm font-medium text-gray-900">{{ ticketData.estimated_return_date }}</p>
                        </div>

                        <div v-if="ticketData.closed_at">
                            <p class="text-xs text-gray-400">Clôturé le</p>
                            <p class="text-sm font-medium text-gray-900">{{ ticketData.closed_at }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal transition -->
        <Modal :show="showTransitionModal" :title="`Passer à : ${selectedStatus?.label}`" max-width="sm"
            @close="showTransitionModal = false">
            <form @submit.prevent="submitTransition" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note (optionnel)</label>
                    <textarea v-model="transitionForm.note" rows="3" placeholder="Ajouter un commentaire..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
                </div>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" @click="showTransitionModal = false">Annuler</Button>
                    <Button type="submit" :loading="transitionForm.processing">Confirmer</Button>
                </div>
            </form>
        </Modal>

        <!-- Modal note -->
        <Modal :show="showNoteModal" title="Ajouter une note" max-width="sm" @close="showNoteModal = false">
            <form @submit.prevent="submitNote" class="space-y-4">
                <textarea v-model="noteForm.note" rows="4" placeholder="Votre note..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" @click="showNoteModal = false">Annuler</Button>
                    <Button type="submit" :loading="noteForm.processing">Ajouter</Button>
                </div>
            </form>
        </Modal>

        <!-- Modal pièce -->
        <Modal :show="showPartModal" title="Consommer une pièce" max-width="sm" @close="showPartModal = false">
            <form @submit.prevent="submitPart" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pièce *</label>
                    <Select v-model="partForm.part_id" :options="partOptions" :error="partForm.errors.part_id" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantité *</label>
                    <Input v-model="partForm.quantity" type="number" :error="partForm.errors.quantity" />
                </div>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" @click="showPartModal = false">Annuler</Button>
                    <Button type="submit" :loading="partForm.processing">Consommer</Button>
                </div>
            </form>
        </Modal>

    </AppLayout>
</template>