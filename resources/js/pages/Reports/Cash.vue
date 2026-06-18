<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import ReportController from '@/actions/App/Http/Controllers/ReportController'
import TicketController from '@/actions/App/Http/Controllers/Ticket/TicketController'
import LineChart from '@/Components/Dashboard/LineChart.vue'
import StatCard from '@/Components/Dashboard/StatCard.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import type {
    CashReportClient,
    CashReportPeriod,
    CashReportSummary,
    CashReportTechnician,
    CashReportUninvoiced,
} from '@/types'

const props = defineProps<{
    summary: CashReportSummary
    by_period: CashReportPeriod[]
    by_technician: CashReportTechnician[]
    top_clients: CashReportClient[]
    uninvoiced_tickets: CashReportUninvoiced[]
    filters: { from: string; to: string }
}>()

const page = usePage()

const from = ref(props.filters.from)
const to = ref(props.filters.to)

function applyPeriod() {
    router.get(ReportController.cash.url(), { from: from.value, to: to.value }, {
        preserveState: true,
        replace: true,
    })
}

function quickPeriod(preset: 'week' | 'month' | 'quarter' | 'year') {
    const now = new Date()
    const pad = (n: number) => String(n).padStart(2, '0')
    const fmt = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`

    const presets: Record<typeof preset, [Date, Date]> = {
        week: (() => {
            const d = new Date(now)
            d.setDate(d.getDate() - d.getDay() + 1)
            const e = new Date(d); e.setDate(d.getDate() + 6)
            return [d, e]
        })(),
        month: [new Date(now.getFullYear(), now.getMonth(), 1), new Date(now.getFullYear(), now.getMonth() + 1, 0)],
        quarter: (() => {
            const q = Math.floor(now.getMonth() / 3)
            return [new Date(now.getFullYear(), q * 3, 1), new Date(now.getFullYear(), q * 3 + 3, 0)]
        })(),
        year: [new Date(now.getFullYear(), 0, 1), new Date(now.getFullYear(), 11, 31)],
    }

    const [s, e] = presets[preset]
    from.value = fmt(s)
    to.value = fmt(e)
    applyPeriod()
}

const fmt = (v: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v)

const chartLabels = computed(() => props.by_period.map(p => p.label))
const chartValues = computed(() => props.by_period.map(p => p.total))

const maxTech = computed(() =>
    props.by_technician.reduce((max, t) => Math.max(max, t.total), 0)
)
</script>

<template>
    <AppLayout title="Rapport de caisse">
        <div class="space-y-6">

            <!-- Header + filtres période -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Rapport de caisse</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Chiffre d'affaires encaissé sur la période</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Raccourcis période -->
                    <div class="flex gap-1">
                        <button
                            v-for="(label, key) in { week: 'Semaine', month: 'Mois', quarter: 'Trimestre', year: 'Année' }"
                            :key="key"
                            type="button"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-medium border border-gray-200 text-gray-600 hover:bg-gray-50 transition"
                            @click="quickPeriod(key as 'week' | 'month' | 'quarter' | 'year')"
                        >
                            {{ label }}
                        </button>
                    </div>

                    <!-- Picker personnalisé -->
                    <div class="flex items-center gap-1.5">
                        <input
                            v-model="from"
                            type="date"
                            class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                        />
                        <span class="text-xs text-gray-400">→</span>
                        <input
                            v-model="to"
                            type="date"
                            class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                        />
                        <button
                            type="button"
                            class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700 transition"
                            @click="applyPeriod"
                        >
                            Filtrer
                        </button>
                    </div>
                </div>
            </div>

            <!-- KPI cards -->
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                <StatCard
                    label="CA encaissé"
                    :value="fmt(summary.revenue_paid)"
                    sub="sur la période"
                    variant="success"
                />
                <StatCard
                    label="CA en attente"
                    :value="fmt(summary.revenue_pending)"
                    sub="factures envoyées"
                    variant="warning"
                />
                <StatCard
                    label="Factures émises"
                    :value="summary.invoices_count"
                    sub="sur la période"
                />
                <StatCard
                    label="Factures payées"
                    :value="summary.invoices_paid_count"
                    :sub="`sur ${summary.invoices_count} émises`"
                    variant="success"
                />
                <StatCard
                    label="Délai règlement"
                    :value="`${summary.avg_payment_days}j`"
                    sub="moyen d'encaissement"
                />
                <StatCard
                    label="Non-facturés"
                    :value="summary.uninvoiced_count"
                    sub="tickets terminés"
                    :variant="summary.uninvoiced_count > 0 ? 'danger' : 'default'"
                />
            </div>

            <!-- Graphique CA par période -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <p class="text-sm font-medium text-gray-700 mb-4">Chiffre d'affaires encaissé</p>
                <div v-if="by_period.some(p => p.total > 0)">
                    <LineChart :labels="chartLabels" :values="chartValues" label="CA encaissé (XOF)" />
                </div>
                <div v-else class="h-48 flex items-center justify-center text-sm text-gray-400">
                    Aucun encaissement sur cette période
                </div>
            </div>

            <!-- Techniciens + Clients -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Par technicien -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <p class="text-sm font-medium text-gray-700">CA par technicien</p>
                    </div>
                    <div v-if="by_technician.length" class="divide-y divide-gray-100">
                        <div
                            v-for="row in by_technician"
                            :key="row.technician"
                            class="px-5 py-3"
                        >
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-800">{{ row.technician }}</span>
                                <span class="text-sm font-semibold text-indigo-600">{{ fmt(row.total) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                                    <div
                                        class="h-full rounded-full bg-indigo-400"
                                        :style="{ width: `${maxTech ? (row.total / maxTech) * 100 : 0}%` }"
                                    />
                                </div>
                                <span class="text-xs text-gray-400 shrink-0">{{ row.count }} facture{{ row.count > 1 ? 's' : '' }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-5 py-12 text-center text-sm text-gray-400">
                        Aucune donnée
                    </div>
                </div>

                <!-- Top clients -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <p class="text-sm font-medium text-gray-700">Top clients</p>
                    </div>
                    <div v-if="top_clients.length" class="divide-y divide-gray-100">
                        <div
                            v-for="(row, i) in top_clients"
                            :key="row.customer"
                            class="px-5 py-3 flex items-center gap-3"
                        >
                            <span class="text-xs font-bold text-gray-300 w-5 shrink-0 text-center">{{ i + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ row.customer }}</p>
                                <p class="text-xs text-gray-400">{{ row.count }} facture{{ row.count > 1 ? 's' : '' }}</p>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 shrink-0">{{ fmt(row.total) }}</span>
                        </div>
                    </div>
                    <div v-else class="px-5 py-12 text-center text-sm text-gray-400">
                        Aucune donnée
                    </div>
                </div>
            </div>

            <!-- Tickets terminés non-facturés -->
            <div v-if="uninvoiced_tickets.length" class="bg-white rounded-xl border border-orange-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-orange-100 bg-orange-50 flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-sm font-medium text-orange-700">
                        {{ uninvoiced_tickets.length }} ticket{{ uninvoiced_tickets.length > 1 ? 's' : '' }} terminé{{ uninvoiced_tickets.length > 1 ? 's' : '' }} sans facture
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-400 uppercase tracking-wide">Référence</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-400 uppercase tracking-wide">Client</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-400 uppercase tracking-wide">Statut</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-400 uppercase tracking-wide">Clôturé le</th>
                                <th class="px-4 py-2.5" />
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="ticket in uninvoiced_tickets"
                                :key="ticket.id"
                                class="hover:bg-gray-50 transition"
                            >
                                <td class="px-4 py-2.5 font-mono text-xs font-medium text-gray-900">
                                    {{ ticket.reference }}
                                </td>
                                <td class="px-4 py-2.5 text-gray-700">{{ ticket.customer }}</td>
                                <td class="px-4 py-2.5 text-gray-500 text-xs">{{ ticket.status }}</td>
                                <td class="px-4 py-2.5 text-gray-400 text-xs">{{ ticket.closed_at }}</td>
                                <td class="px-4 py-2.5 text-right">
                                    <a
                                        :href="TicketController.show.url(ticket.id)"
                                        class="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                                    >
                                        Ouvrir →
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
