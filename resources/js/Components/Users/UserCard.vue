<!-- resources/js/Components/Users/UserCard.vue -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import UserPermissionController from '@/actions/App/Http/Controllers/UserPermissionController';
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import { usePermission } from '@/Composables/usePermission';
import type { BadgeVariant, ShopUser } from '@/types'

const props = defineProps<{
    user: ShopUser
    isCurrentUser: boolean
    toggling: boolean
    resetting: boolean
    deleting: boolean
}>()

const emit = defineEmits<{
    edit: [user: ShopUser]
    toggle: [user: ShopUser]
    'reset-password': [user: ShopUser]
    delete: [user: ShopUser]
}>()

function roleLabel(role: string) {
    const map: Record<string, string> = {
        'admin': 'Administrateur',
        'manager': 'Gestionnaire',
        'technicien': 'Technicien',
        'caissiere': 'Caissière',
    }

    return map[role] || role
}

function roleVariant(role: string) {
    const map: Record<string, string> = {
        'admin': 'warning',
        'manager': 'warning',
        'technicien': 'info',
        'caissiere': 'info',
    }

    return map[role] || 'default'
}

const { can } = usePermission()

const initials = (name: string) =>
    name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
</script>

<template>
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4 transition"
        :class="{ 'opacity-60': !user.is_active }">
        <!-- Avatar -->
        <div
            class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm shrink-0">
            {{ initials(user.name) }}
        </div>

        <!-- Infos -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <p class="font-medium text-gray-900 text-sm">{{ user.name }}</p>
                <Badge :variant="roleVariant(user.role) as BadgeVariant">{{ roleLabel(user.role) }}</Badge>
                <Badge v-if="!user.is_active" variant="default">Inactif</Badge>
                <Badge v-if="props.isCurrentUser" variant="default">Vous</Badge>
            </div>
            <p class="text-xs text-gray-400 mt-0.5">{{ user.email }}</p>
            <div v-if="user.depots.length" class="flex flex-wrap gap-1 mt-1.5">
                <span v-for="d in user.depots" :key="d.id"
                    class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-gray-100 text-gray-500">
                    {{ d.name }}
                </span>
            </div>
        </div>

        <!-- Stats -->
        <div class="text-center shrink-0 hidden sm:block">
            <p class="text-lg font-bold text-indigo-600">{{ user.tickets_count }}</p>
            <p class="text-xs text-gray-400">{{ user.tickets_count_label }}</p>
        </div>

        <!-- Actions — masquées si c'est l'utilisateur courant -->
        <div v-if="!isCurrentUser && can('users.manage')" class="flex items-center gap-1 shrink-0">
            <Button variant="ghost" size="sm" title="Modifier l'utilisateur" @click="emit('edit', user)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
            </Button>
            <Button variant="ghost" size="sm" title="Réinitialiser le mot de passe" :loading="resetting"
                @click="emit('reset-password', user)" class="text-gray-500">
                Reset MDP
            </Button>
            <Link :href="UserPermissionController.index.url({ user: user.id })">
                <Button variant="ghost" size="sm" class="text-indigo-600">
                    Permissions
                </Button>
            </Link>
            <Button variant="ghost" size="sm" :loading="toggling" @click="emit('toggle', user)" :class="user.is_active
                ? 'text-yellow-600 hover:bg-yellow-50'
                : 'text-green-600 hover:bg-green-50'">
                {{ user.is_active ? 'Désactiver' : 'Activer' }}
            </Button>
            <Button variant="ghost" size="sm" title="Supprimer l'utilisateur" :loading="deleting"
                @click="emit('delete', user)" class="text-red-500 hover:text-red-700 hover:bg-red-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </Button>
        </div>
        <div v-else class="text-xs text-gray-400 shrink-0 italic">
            Votre compte
        </div>

    </div>
</template>