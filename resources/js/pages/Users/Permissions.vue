<!-- resources/js/Pages/Users/Permissions.vue -->
<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import UserPermissionController from '@/actions/App/Http/Controllers/UserPermissionController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
// import type { Permission } from '@/Types/models'

const props = defineProps<{
    targetUser: { id: number; name: string; role: string }
    allPermissions: string[]
    effectivePerms: string[]
    rolePerms: string[]
    overrides: Record<string, { granted: boolean }>
}>()

// Grouper les permissions par domaine
const grouped = computed(() => {
    const groups: Record<string, string[]> = {}
    props.allPermissions.forEach(perm => {
        const [domain] = perm.split('.')

        if (!groups[domain]) {
            groups[domain] = []
        }

        groups[domain].push(perm)
    })

    return groups
})

// État d'une permission pour cet user
function permState(perm: string): 'granted-override' | 'revoked-override' | 'role' | 'none' {
    if (props.overrides[perm] !== undefined) {
        return props.overrides[perm].granted ? 'granted-override' : 'revoked-override'
    }

    return props.rolePerms.includes(perm) ? 'role' : 'none'
}

function isEffective(perm: string): boolean {
    return props.effectivePerms.includes(perm)
}

const actionLoading = ref<string | null>(null)

function setPermission(perm: string, action: 'grant' | 'revoke' | 'reset') {
    actionLoading.value = `${perm}-${action}`

    router.post(
        UserPermissionController.update.url({ user: props.targetUser.id }),
        { permission: perm, action },
        {
            preserveScroll: true,
            onFinish: () => actionLoading.value = null,
        }
    )
}

function resetAll() {
    if (!confirm(`Réinitialiser toutes les permissions de ${props.targetUser.name} ?`)) {
        return
    }

    router.delete(UserPermissionController.resetAll.url({ user: props.targetUser.id }), {
        preserveScroll: true,
    })
}

const domainLabels: Record<string, string> = {
    stock: '📦 Stock',
    tickets: '🎫 Tickets',
    customers: '👥 Clients',
    invoices: '🧾 Facturation',
    depots: '🏪 Dépôts',
    users: '👤 Utilisateurs',
    settings: '⚙️ Paramètres',
    dashboard: '📊 Dashboard',
}

function permLabel(perm: string): string {
    const labels: Record<string, string> = {
        'stock.view': 'Voir le stock',
        'stock.create': 'Créer des articles',
        'stock.edit': 'Modifier les articles',
        'stock.delete': 'Supprimer les articles',
        'stock.restock': 'Ravitailler le stock',
        'stock.transfer': 'Transférer entre dépôts',
        'stock.adjust': 'Ajuster l\'inventaire',
        'stock.count': 'Faire un inventaire physique',
        'tickets.view': 'Voir les tickets',
        'tickets.create': 'Créer des tickets',
        'tickets.edit': 'Modifier les tickets',
        'tickets.delete': 'Supprimer les tickets',
        'tickets.transition': 'Changer le statut',
        'tickets.assign': 'Assigner un technicien',
        'customers.view': 'Voir les clients',
        'customers.create': 'Créer des clients',
        'customers.edit': 'Modifier les clients',
        'customers.delete': 'Supprimer les clients',
        'invoices.view': 'Voir les factures',
        'invoices.create': 'Créer des factures',
        'invoices.edit': 'Modifier les factures',
        'invoices.delete': 'Supprimer les factures',
        'invoices.mark_paid': 'Marquer payée',
        'depots.view': 'Voir les dépôts',
        'depots.manage': 'Gérer les dépôts',
        'users.view': 'Voir les utilisateurs',
        'users.manage': 'Gérer les utilisateurs',
        'settings.manage': 'Gérer les paramètres',
        'dashboard.view': 'Voir le dashboard',
        'dashboard.analytics': 'Stats avancées',
        'purchases.view': 'Voir les commandes d\'achat',
        'purchases.create': 'Créer des commandes d\'achat',
        'purchases.edit': 'Modifier des commandes d\'achat',
        'purchases.delete': 'Supprimer des commandes d\'achat',
        'purchases.mark_paid': 'Valider des commandes d\'achat',
    }

    return labels[perm] ?? perm
}
</script>

<template>
    <AppLayout :title="`Permissions — ${targetUser.name}`">
        <div class="max-w-7xl m-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">
                        Permissions — {{ targetUser.name }}
                    </h1>
                    <div class="flex items-center gap-2 mt-1">
                        <Badge variant="info">{{ targetUser.role }}</Badge>
                        <span class="text-xs text-gray-400">
                            {{ effectivePerms.length }} permission{{ effectivePerms.length > 1 ? 's' : '' }} actives
                        </span>
                    </div>
                </div>
                <Button variant="secondary" size="sm" @click="resetAll" :disabled="Object.keys(overrides).length === 0">
                    Réinitialiser au rôle
                </Button>
            </div>

            <!-- Légende -->
            <div class="flex flex-wrap gap-3 text-xs">
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-green-500 inline-block" />
                    Permission du rôle
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-indigo-500 inline-block" />
                    Accordée manuellement
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-red-400 inline-block" />
                    Révoquée manuellement
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-gray-200 inline-block" />
                    Non accordée
                </span>
            </div>

            <!-- Groupes de permissions -->
            <div v-for="(perms, domain) in grouped" :key="domain"
                class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 bg-gray-50 border-b">
                    <p class="text-sm font-semibold text-gray-700">{{ domainLabels[domain] ?? domain }}</p>
                </div>

                <div class="divide-y divide-gray-100">
                    <div v-for="perm in perms" :key="perm" class="px-5 py-3 flex items-center justify-between gap-4">
                        <!-- Indicateur + label -->
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="{
                                'bg-green-500': permState(perm) === 'role',
                                'bg-indigo-500': permState(perm) === 'granted-override',
                                'bg-red-400': permState(perm) === 'revoked-override',
                                'bg-gray-200': permState(perm) === 'none',
                            }" />
                            <div>
                                <p class="text-sm text-gray-800">{{ permLabel(perm) }}</p>
                                <p class="text-xs text-gray-400 font-mono">{{ perm }}</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1 shrink-0">
                            <!-- Bouton Grant -->
                            <button v-if="!isEffective(perm) || permState(perm) === 'revoked-override'"
                                :disabled="actionLoading === `${perm}-grant`" @click="setPermission(perm, 'grant')"
                                class="px-2.5 py-1 rounded-lg text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition disabled:opacity-50">
                                {{ actionLoading === `${perm}-grant` ? '...' : 'Accorder' }}
                            </button>

                            <!-- Bouton Revoke -->
                            <button v-if="isEffective(perm) && permState(perm) !== 'revoked-override'"
                                :disabled="actionLoading === `${perm}-revoke`" @click="setPermission(perm, 'revoke')"
                                class="px-2.5 py-1 rounded-lg text-xs font-medium bg-red-50 text-red-600 hover:bg-red-100 transition disabled:opacity-50">
                                {{ actionLoading === `${perm}-revoke` ? '...' : 'Révoquer' }}
                            </button>

                            <!-- Bouton Reset (si override actif) -->
                            <button v-if="overrides[perm] !== undefined" :disabled="actionLoading === `${perm}-reset`"
                                @click="setPermission(perm, 'reset')"
                                class="px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-500 hover:bg-gray-200 transition disabled:opacity-50">
                                {{ actionLoading === `${perm}-reset` ? '...' : 'Réinitialiser' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>