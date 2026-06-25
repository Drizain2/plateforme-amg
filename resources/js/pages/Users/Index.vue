<!-- resources/js/Pages/Users/Index.vue -->
<script setup lang="ts">
import { router, usePage, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import ShopUserController from '@/actions/App/Http/Controllers/ShopUserController'
// import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import Select from '@/Components/UI/Select.vue'
import UserCard from '@/Components/Users/UserCard.vue'
import { usePermission } from '@/Composables/usePermission'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { ShopUser, UserRole } from '@/types'

const props = defineProps<{
    users: ShopUser[]
    depots: { id: number; name: string }[]
    userLimit: number | null
    canAddUser: boolean
}>()

const { can } = usePermission()
const page = usePage()

const currentUserId = computed(() => page.props.auth.user.id)

// -----------------------------------------------
// Modal création
// -----------------------------------------------
const showCreateModal = ref(false)

const createForm = useForm({
    name: '',
    email: '',
    role: 'technicien' as UserRole,
    depot_ids: [] as number[],
})

function submitCreate() {
    createForm.post(ShopUserController.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false; createForm.reset()
        },
    })
}

// -----------------------------------------------
// Modal édition
// -----------------------------------------------
const showEditModal = ref(false)
const editingUser = ref<ShopUser | null>(null)

const editForm = useForm({
    name: '',
    email: '',
    role: 'technicien' as UserRole,
    is_active: true,
    depot_ids: [] as number[],
})

function openEdit(user: ShopUser) {
    editingUser.value = user
    editForm.name = user.name
    editForm.email = user.email
    editForm.role = user.role
    editForm.is_active = user.is_active
    editForm.depot_ids = [...user.depot_ids]
    showEditModal.value = true
}

function submitEdit() {
    if (!editingUser.value) {
        return
    }

    editForm.put(ShopUserController.update.url({ id: editingUser.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false
        },
    })
}

// -----------------------------------------------
// Actions rapides
// -----------------------------------------------
const togglingId = ref<number | null>(null)
const resettingId = ref<number | null>(null)
const deletingId = ref<number | null>(null)

function toggleActive(user: ShopUser) {
    const action = user.is_active ? 'désactiver' : 'activer'

    if (!confirm(`Voulez-vous ${action} ${user.name} ?`)) {
        return
    }

    togglingId.value = user.id
    router.post(ShopUserController.toggleActive.url({ id: user.id }), {}, {
        preserveScroll: true,
        onFinish: () => togglingId.value = null,
    })
}

function resetPassword(user: ShopUser) {
    if (!confirm(`Envoyer un email de réinitialisation à ${user.email} ?`)) {
        return
    }

    resettingId.value = user.id
    router.post(ShopUserController.resetPassword.url({ id: user.id }), {}, {
        preserveScroll: true,
        onFinish: () => resettingId.value = null,
    })
}

function confirmDelete(user: ShopUser) {
    const msg = user.tickets_count > 0
        ? `${user.name} a ${user.tickets_count} ticket(s). Le compte sera désactivé. Continuer ?`
        : `Supprimer ${user.name} ?`

    if (!confirm(msg)) {
        return
    }

    deletingId.value = user.id
    router.delete(ShopUserController.destroy.url({ id: user.id }), {
        preserveScroll: true,
        onFinish: () => deletingId.value = null,
    })
}

// -----------------------------------------------
// Helpers
// -----------------------------------------------
const roleOptions = [
    { value: 'admin', label: 'Administrateur' },
    { value: 'gestionnaire', label: 'Gestionnaire' },
    { value: 'technicien', label: 'Technicien' },
    { value: 'caissiere', label: 'Caissière' },
]

// const depotOptions = computed(() =>
//     props.depots.map(d => ({ value: d.id, label: d.name }))
// )

const admins = computed(() => props?.users.filter(u => u.role === 'admin'))
const techniciens = computed(() => props?.users.filter(u => u.role === 'technicien'))

// function roleVariant(role: string) {
//     return role === 'admin' ? 'warning' : 'info'
// }

// function roleLabel(role: string) {
//     return role === 'admin' ? 'Admin' : 'Technicien'
// }

// Gestion checkbox dépôts dans les formulaires
function toggleDepot(form: typeof createForm | typeof editForm, depotId: number) {
    const idx = form.depot_ids.indexOf(depotId)

    if (idx === -1) {
        form.depot_ids.push(depotId)
    } else {
        form.depot_ids.splice(idx, 1)
    }
}
</script>

<template>
    <AppLayout title="Utilisateurs">
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Utilisateurs</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ users.length }} membre{{ users.length > 1 ? 's' : '' }}
                        <span v-if="userLimit !== null"> / {{ userLimit }} max</span>
                    </p>
                </div>
                <Button v-show="can('users.manage')" :disabled="!canAddUser" @click="showCreateModal = true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Inviter un utilisateur
                </Button>
            </div>

            <div v-if="can('users.manage') && !canAddUser" class="bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-xl px-4 py-3">
                Limite d'utilisateurs atteinte pour votre offre actuelle. Passez à une offre supérieure pour en ajouter.
            </div>

            <!-- Admins -->
            <div class="space-y-3">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide">
                    Administrateurs ({{ admins.length }})
                </h2>
                <UserCard v-for="user in admins" :key="user.id" :user="user"
                    :is-current-user="user.id === currentUserId" :toggling="togglingId === user.id"
                    :resetting="resettingId === user.id" :deleting="deletingId === user.id" @edit="openEdit"
                    @toggle="toggleActive" @reset-password="resetPassword" @delete="confirmDelete" />
            </div>

            <!-- Techniciens -->
            <div class="space-y-3">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide">
                    Techniciens ({{ techniciens.length }})
                </h2>
                <div v-if="techniciens.length === 0" class="text-sm text-gray-400 italic">
                    Aucun technicien
                </div>
                <UserCard v-for="user in techniciens" :key="user.id" :user="user"
                    :is-current-user="user.id === currentUserId" :toggling="togglingId === user.id"
                    :resetting="resettingId === user.id" :deleting="deletingId === user.id" @edit="openEdit"
                    @toggle="toggleActive" @reset-password="resetPassword" @delete="confirmDelete" />
            </div>

        </div>

        <!-- -----------------------------------------------
         Modal création
    ----------------------------------------------- -->
        <Modal :show="showCreateModal" title="Inviter un utilisateur" @close="showCreateModal = false">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                    <Input v-model="createForm.name" placeholder="Jean Dupont" :error="createForm.errors.name"
                        autofocus />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <Input v-model="createForm.email" type="email" :error="createForm.errors.email" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rôle *</label>
                    <Select v-model="createForm.role" :options="roleOptions" :error="createForm.errors.role" />
                </div>
                <div v-if="depots.length">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dépôts assignés</label>
                    <div class="space-y-2">
                        <label v-for="depot in depots" :key="depot.id" class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" :value="depot.id" :checked="createForm.depot_ids.includes(depot.id)"
                                @change="toggleDepot(createForm, depot.id)"
                                class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-sm text-gray-700">{{ depot.name }}</span>
                        </label>
                    </div>
                </div>
                <p class="text-xs text-gray-400">
                    Un email d'invitation sera envoyé pour définir le mot de passe.
                </p>
                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="secondary" @click="showCreateModal = false">Annuler</Button>
                    <Button type="submit" :loading="createForm.processing">Inviter</Button>
                </div>
            </form>
        </Modal>

        <!-- -----------------------------------------------
         Modal édition
    ----------------------------------------------- -->
        <Modal :show="showEditModal" :title="`Modifier — ${editingUser?.name}`" @close="showEditModal = false">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                    <Input v-model="editForm.name" :error="editForm.errors.name" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <Input v-model="editForm.email" type="email" :error="editForm.errors.email" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rôle *</label>
                    <Select v-model="editForm.role" :options="roleOptions" :error="editForm.errors.role" />
                </div>
                <div v-if="depots.length">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dépôts assignés</label>
                    <div class="space-y-2">
                        <label v-for="depot in depots" :key="depot.id" class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" :value="depot.id" :checked="editForm.depot_ids.includes(depot.id)"
                                @change="toggleDepot(editForm, depot.id)"
                                class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-sm text-gray-700">{{ depot.name }}</span>
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="secondary" @click="showEditModal = false">Annuler</Button>
                    <Button type="submit" :loading="editForm.processing">Mettre à jour</Button>
                </div>
            </form>
        </Modal>

    </AppLayout>
</template>