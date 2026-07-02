<!-- resources/js/Pages/Tickets/Show.vue -->
<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController'
import TicketController from '@/actions/App/Http/Controllers/Ticket/TicketController'
import type { BadgeVariant } from '@/Components/UI/Badge.vue';
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import Select from '@/Components/UI/Select.vue'
import { usePermission } from '@/Composables/usePermission'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { Ticket } from '@/types'

const props = defineProps<{
    ticket: Ticket
    technicians: { id: number; name: string }[]
    depotParts: { id: number; name: string; quantity: number; unit_price: number }[]
}>()

const { can } = usePermission()

const ticketData = computed(() => props.ticket)
const isClosed = computed(() => ['returned', 'cancelled'].includes(ticketData.value.status))

// ── Transition statut ────────────────────────────────────────────────────────
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
            showTransitionModal.value = false
            transitionForm.reset()
        },
    })
}

// ── Note ────────────────────────────────────────────────────────────────────
const noteForm = useForm({ note: '' })
const showNoteModal = ref(false)

function submitNote() {
    noteForm.post(TicketController.addNote.url({ ticket: ticketData.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showNoteModal.value = false
            noteForm.reset()
        },
    })
}

// ── Diagnostic ──────────────────────────────────────────────────────────────
const diagnosisForm = useForm({
    diagnosis: ticketData.value.diagnosis ?? '',
    estimated_price: ticketData.value.estimated_price?.toString() ?? '',
})
const showDiagnosisModal = ref(false)

function openDiagnosis() {
    diagnosisForm.diagnosis = ticketData.value.diagnosis ?? ''
    diagnosisForm.estimated_price = ticketData.value.estimated_price?.toString() ?? ''
    showDiagnosisModal.value = true
}

function submitDiagnosis() {
    diagnosisForm.post(TicketController.setDiagnosis.url({ ticket: ticketData.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showDiagnosisModal.value = false
        },
    })
}

// ── Assigner technicien ──────────────────────────────────────────────────────
const assignForm = useForm({ technician_id: ticketData.value.technicien?.id?.toString() ?? '' })
const showAssignModal = ref(false)

const technicianOptions = computed(() =>
    props.technicians.map(t => ({ value: t.id, label: t.name }))
)

function openAssign() {
    assignForm.technician_id = ticketData.value.technicien?.id?.toString() ?? ''
    showAssignModal.value = true
}

function submitAssign() {
    assignForm.post(TicketController.assignTechnician.url({ ticket: ticketData.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showAssignModal.value = false
        },
    })
}

// ── Consommer pièce ──────────────────────────────────────────────────────────
const partForm = useForm({ part_id: '', quantity: 1 })
const showPartModal = ref(false)

const partOptions = computed(() =>
    props.depotParts.map(p => ({ value: p.id, label: `${p.name} (stock: ${p.quantity})` }))
)

function submitPart() {
    partForm.post(TicketController.consumePart.url({ ticket: ticketData.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showPartModal.value = false
            partForm.reset()
        },
    })
}

// ── Créer facture ────────────────────────────────────────────────────────────
const invoiceForm = useForm({})

function createInvoice() {
    invoiceForm.post(InvoiceController.fromTicket.url({ ticket: ticketData.value.id }))
}

// ── Helpers ──────────────────────────────────────────────────────────────────
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

                <!-- Transitions -->
                <div v-if="can('tickets.transition') && !isClosed" class="flex gap-2">
                    <Button v-for="s in ticketData.next_statuses" :key="s.value" variant="secondary" size="sm"
                        @click="openTransition(s)">
                        → {{ s.label }}
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6">

                <!-- Colonne principale -->
                <div class="col-span-2 space-y-6">

                    <!-- Client + appareil -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Client</p>
                            <p class="font-medium text-gray-900">{{ ticketData.customer?.name }}</p>
                            <p v-if="ticketData.customer?.email" class="text-sm text-gray-500">{{ ticketData.customer.email }}</p>
                            <p v-if="ticketData.customer?.phone" class="text-sm text-gray-500">{{ ticketData.customer.phone }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Appareil</p>
                            <p class="font-medium text-gray-900">{{ ticketData.device?.full_name }}</p>
                            <p class="text-sm text-gray-500 capitalize">{{ ticketData.device?.type }}</p>
                            <p v-if="ticketData.device?.serial_number" class="text-xs text-gray-400 font-mono">{{ ticketData.device.serial_number }}</p>
                            <p v-if="ticketData.device?.condition_in" class="text-xs text-gray-400 mt-1 italic">{{ ticketData.device.condition_in }}</p>
                        </div>
                    </div>

                    <!-- Description + diagnostic -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Description client</p>
                            <p class="text-sm text-gray-700">{{ ticketData.description }}</p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Diagnostic technicien</p>
                                <Button v-if="can('tickets.edit') && !isClosed" variant="ghost" size="sm" @click="openDiagnosis">
                                    {{ ticketData.diagnosis ? 'Modifier' : '+ Saisir' }}
                                </Button>
                            </div>
                            <div v-if="ticketData.diagnosis" class="bg-gray-50 rounded-lg p-3">
                                <p class="text-sm text-gray-700">{{ ticketData.diagnosis }}</p>
                                <p v-if="ticketData.estimated_price" class="text-sm font-semibold text-indigo-600 mt-2">
                                    Devis estimé : {{ fmt(ticketData.estimated_price) }}
                                </p>
                            </div>
                            <p v-else class="text-sm text-gray-400 italic">Aucun diagnostic saisi</p>
                        </div>
                    </div>

                    <!-- Pièces consommées -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Pièces utilisées</p>
                            <Button v-if="can('tickets.edit') && !isClosed" variant="ghost" size="sm" @click="showPartModal = true">+ Ajouter</Button>
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
                                <span class="text-sm font-semibold text-gray-900">Total pièces : {{ fmt(totalParts) }}</span>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 italic">Aucune pièce consommée</p>
                    </div>

                    <!-- Timeline événements -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Historique</p>
                            <Button v-if="can('tickets.edit')" variant="ghost" size="sm" @click="showNoteModal = true">+ Note</Button>
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
                    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Détails</p>

                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Dépôt</p>
                            <p class="text-sm font-medium text-gray-900">{{ ticketData.depot?.name }}</p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-0.5">
                                <p class="text-xs text-gray-400">Technicien</p>
                                <button v-if="can('tickets.assign') && !isClosed"
                                    class="text-xs text-indigo-500 hover:text-indigo-700 font-medium"
                                    @click="openAssign">
                                    {{ ticketData.technicien ? 'Changer' : 'Assigner' }}
                                </button>
                            </div>
                            <p class="text-sm font-medium text-gray-900">{{ ticketData.technicien?.name ?? 'Non assigné' }}</p>
                        </div>

                        <div v-if="ticketData.estimated_return_date">
                            <p class="text-xs text-gray-400 mb-0.5">Retour estimé</p>
                            <p class="text-sm font-medium text-gray-900">{{ ticketData.estimated_return_date }}</p>
                        </div>

                        <div v-if="ticketData.closed_at">
                            <p class="text-xs text-gray-400 mb-0.5">Clôturé le</p>
                            <p class="text-sm font-medium text-gray-900">{{ ticketData.closed_at }}</p>
                        </div>
                    </div>

                    <!-- Facture -->
                    <div v-if="can('invoices.view') || can('invoices.create')"
                        class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Facture</p>
                        <template v-if="ticketData.invoice_id">
                            <p class="text-sm text-gray-600">Facture générée</p>
                            <Link :href="`/invoices/${ticketData.invoice_id}`"
                                class="block text-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                Voir la facture →
                            </Link>
                        </template>
                        <template v-else>
                            <p class="text-sm text-gray-400 italic">Aucune facture</p>
                            <Button v-if="can('invoices.create') && ticketData.status === 'done'"
                                class="w-full" size="sm"
                                :loading="invoiceForm.processing"
                                @click="createInvoice">
                                Créer la facture
                            </Button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal : transition statut -->

            </div>
        </div>

        <!-- Modal : transition statut -->
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

        <!-- Modal : note -->
        <Modal :show="showNoteModal" title="Ajouter une note" max-width="sm" @close="showNoteModal = false">
            <form @submit.prevent="submitNote" class="space-y-4">
                <textarea v-model="noteForm.note" rows="4" placeholder="Votre note..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
                <p v-if="noteForm.errors.note" class="text-xs text-red-500">{{ noteForm.errors.note }}</p>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" @click="showNoteModal = false">Annuler</Button>
                    <Button type="submit" :loading="noteForm.processing">Ajouter</Button>
                </div>
            </form>
        </Modal>

        <!-- Modal : diagnostic -->
        <Modal :show="showDiagnosisModal" title="Diagnostic technicien" max-width="sm" @close="showDiagnosisModal = false">
            <form @submit.prevent="submitDiagnosis" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Diagnostic *</label>
                    <textarea v-model="diagnosisForm.diagnosis" rows="4" placeholder="Décrivez le diagnostic..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
                    <p v-if="diagnosisForm.errors.diagnosis" class="mt-1 text-xs text-red-500">{{ diagnosisForm.errors.diagnosis }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Devis estimé (optionnel)</label>
                    <Input v-model="diagnosisForm.estimated_price" type="number" placeholder="0" :error="diagnosisForm.errors.estimated_price" />
                </div>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" @click="showDiagnosisModal = false">Annuler</Button>
                    <Button type="submit" :loading="diagnosisForm.processing">Enregistrer</Button>
                </div>
            </form>
        </Modal>

        <!-- Modal : assigner technicien -->
        <Modal :show="showAssignModal" title="Assigner un technicien" max-width="sm" @close="showAssignModal = false">
            <form @submit.prevent="submitAssign" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Technicien *</label>
                    <Select v-model="assignForm.technician_id" :options="technicianOptions" :error="assignForm.errors.technician_id" />
                </div>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" @click="showAssignModal = false">Annuler</Button>
                    <Button type="submit" :loading="assignForm.processing">Assigner</Button>
                </div>
            </form>
        </Modal>

        <!-- Modal : consommer pièce -->
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
