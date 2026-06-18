<!-- resources/js/Pages/Stock/Depots/Index.vue -->
<script setup lang="ts">
import { router, usePage, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import DepotController from '@/actions/App/Http/Controllers/Stock/DepotController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import { usePermission } from '@/Composables/usePermission'
import { useToast } from '@/Composables/useToast'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { Depot, PaginatedResource } from '@/types'

const props = defineProps<{
  depots:PaginatedResource<Depot>
  shopUsers: { id: number; name: string }[]
}>()
const { success, error } = useToast()
const { can } = usePermission()
const page = usePage()

watch(() => page.props.flash, (flash) => {
  if (flash.success){
    success(flash.success)
  }

  if (flash.error){
    error(flash.error)
  }
}, { immediate: true })

// -----------------------------------------------
// Modal création / édition dépôt
// -----------------------------------------------
const showDepotModal = ref(false)
const editingDepot   = ref<typeof props.depots.data[0] | null>(null)

const depotForm = useForm({
  name:    '',
  address: '',
  phone:   '',
})

function openCreate() {
  editingDepot.value = null
  depotForm.reset()
  showDepotModal.value = true
}

function openEdit(depot: typeof props.depots.data[0]) {
  editingDepot.value   = depot
  depotForm.name       = depot.name
  depotForm.address    = depot.address ?? ''
  depotForm.phone      = depot.phone ?? ''
  showDepotModal.value = true
}

function submitDepot() {
  if (editingDepot.value) {
    depotForm.put(DepotController.update.url(editingDepot.value.id), {
      preserveScroll: true,
      onSuccess: () => { 
        showDepotModal.value = false 
      },
    })
  } else {
    depotForm.post(DepotController.store.url(), {
      preserveScroll: true,
      onSuccess: () => { 
        showDepotModal.value = false 
      },
    })
  }
}

// -----------------------------------------------
// Suppression / désactivation
// -----------------------------------------------
const deletingId = ref<number | null>(null)

function confirmDelete(depot: typeof props.depots.data[0]) {
  const msg = (depot.stocks_count ?? 0) > 0
    ? `Ce dépôt contient ${depot.stocks_count ?? 0} pièce(s). Il sera désactivé. Continuer ?`
    : `Supprimer le dépôt "${depot.name}" ?`

  if (!confirm(msg)){
    return
  }

  deletingId.value = depot.id
  router.delete(DepotController.destroy.url(depot.id), {
    preserveScroll: true,
    onFinish: () => deletingId.value = null,
  })
}

// -----------------------------------------------
// Modal gestion utilisateurs du dépôt
// -----------------------------------------------
const showUsersModal  = ref(false)
const selectedDepot   = ref<typeof props.depots.data[0] | null>(null)
const attachingUserId = ref<number | null>(null)
const detachingUserId = ref<number | null>(null)

function openUsers(depot: typeof props.depots.data[0]) {
  selectedDepot.value  = depot
  showUsersModal.value = true
}

const userToAttach = ref<string>('')

function attachUser() {
  if (!selectedDepot.value || !userToAttach.value) {
    return
  }

  attachingUserId.value = Number(userToAttach.value)

  router.post(
    DepotController.attachUser.url(selectedDepot.value.id),
    { user_id: userToAttach.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        userToAttach.value    = ''
        attachingUserId.value = null
        // Sync local pour éviter reload complet
        selectedDepot.value = props.depots.data.find(d => d.id === selectedDepot.value!.id) ?? null
      },
      onError: () => attachingUserId.value = null,
    }
  )
}

function detachUser(userId: number) {
  if (!selectedDepot.value) {
    return
}

  detachingUserId.value = userId

  router.delete(
    DepotController.detachUser.url({ depot: selectedDepot.value.id, user: userId }),
    {
      preserveScroll: true,
      onSuccess: () => {
        detachingUserId.value = null
        selectedDepot.value   = props.depots.data.find(d => d.id === selectedDepot.value!.id) ?? null
      },
      onError: () => detachingUserId.value = null,
    }
  )
}

// Utilisateurs non encore assignés au dépôt sélectionné
const availableUsers = computed(() => {
  if (!selectedDepot.value){
     return []
    }

  const assignedIds = selectedDepot.value.users?.map(u => u.id) || []

  return props.shopUsers.filter(u => !assignedIds.includes(u.id))
})

// const userOptions = computed(() =>
//   availableUsers.value.map(u => ({ value: u.id, label: u.name }))
// )
</script>

<template>
  <AppLayout title="Dépôts">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Dépôts</h1>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ depots.data.length }} dépôt{{ depots.data.length > 1 ? 's' : '' }}
          </p>
        </div>
        <Button v-show="can('depots.manage')" @click="openCreate">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Nouveau dépôt
        </Button>
      </div>

      <!-- Cards dépôts -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="depot in depots.data"
          :key="depot.id"
          class="bg-white rounded-xl border border-gray-200 p-5 space-y-4 transition"
          :class="{ 'opacity-60': !depot.is_active }"
        >
          <!-- Titre + statut -->
          <div class="flex items-start justify-between">
            <div>
              <h3 class="font-semibold text-gray-900">{{ depot.name }}</h3>
              <p v-if="depot.address" class="text-xs text-gray-400 mt-0.5">{{ depot.address }}</p>
              <p v-if="depot.phone" class="text-xs text-gray-400">{{ depot.phone }}</p>
            </div>
            <Badge :variant="depot.is_active ? 'success' : 'default'">
              {{ depot.is_active ? 'Actif' : 'Inactif' }}
            </Badge>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-gray-50 rounded-lg px-3 py-2 text-center">
              <p class="text-lg font-bold text-indigo-600">{{ depot.stocks_count ?? 0 }}</p>
              <p class="text-xs text-gray-500">Pièces</p>
            </div>
            <div class="bg-gray-50 rounded-lg px-3 py-2 text-center">
              <p class="text-lg font-bold text-indigo-600">{{ depot.users?.length }}</p>
              <p class="text-xs text-gray-500">Techniciens</p>
            </div>
          </div>

          <!-- Techniciens assignés -->
          <div v-if="depot.users?.length" class="flex flex-wrap gap-1">
            <span
              v-for="user in depot.users"
              :key="user.id"
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-indigo-50 text-indigo-700"
            >
              {{ user.name }}
            </span>
          </div>
          <p v-else class="text-xs text-gray-400 italic">Aucun technicien assigné</p>

          <!-- Actions -->
          <div v-show="can('depots.manage')" class="flex items-center gap-2 pt-1 border-t border-gray-100">
            <Button variant="ghost" size="sm" @click="openUsers(depot)">
              Gérer techniciens
            </Button>
            <Button variant="ghost" size="sm" @click="openEdit(depot)">
              Modifier
            </Button>
            <Button
              variant="ghost"
              size="sm"
              :loading="deletingId === depot.id"
              @click="confirmDelete(depot)"
              class="text-red-500 hover:text-red-700 hover:bg-red-50 ml-auto"
            >
              {{ (depot.stocks_count ?? 0) > 0 ? 'Désactiver' : 'Supprimer' }}
            </Button>
          </div>
        </div>

        <!-- Card vide -->
        <div
          v-if="depots.data.length === 0"
          class="col-span-full bg-white rounded-xl border border-dashed border-gray-300 p-12 text-center"
        >
          <p class="text-gray-400 text-sm">Aucun dépôt configuré.</p>
          <Button v-show="can('depots.manage')" variant="secondary" size="sm" class="mt-3" @click="openCreate">
            Créer le premier dépôt
          </Button>
        </div>
      </div>

    </div>

    <!-- -----------------------------------------------
         Modal création / édition
    ----------------------------------------------- -->
    <Modal
      :show="showDepotModal"
      :title="editingDepot ? 'Modifier le dépôt' : 'Nouveau dépôt'"
      @close="showDepotModal = false"
    >
      <form @submit.prevent="submitDepot" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
          <Input
            v-model="depotForm.name"
            placeholder="Ex: Dépôt principal"
            :error="depotForm.errors.name"
            autofocus
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
          <Input
            v-model="depotForm.address"
            placeholder="12 rue de la Paix, Paris"
            :error="depotForm.errors.address"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
          <Input
            v-model="depotForm.phone"
            placeholder="01 23 45 67 89"
            :error="depotForm.errors.phone"
          />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <Button variant="secondary" @click="showDepotModal = false">Annuler</Button>
          <Button type="submit" :loading="depotForm.processing">
            {{ editingDepot ? 'Mettre à jour' : 'Créer' }}
          </Button>
        </div>
      </form>
    </Modal>

    <!-- -----------------------------------------------
         Modal gestion techniciens
    ----------------------------------------------- -->
    <Modal
      :show="showUsersModal"
      :title="`Techniciens — ${selectedDepot?.name}`"
      max-width="sm"
      @close="showUsersModal = false"
    >
      <div class="space-y-4">

        <!-- Assignés -->
        <div>
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Assignés</p>
          <div v-if="selectedDepot?.users?.length" class="space-y-2">
            <div
              v-for="user in selectedDepot.users"
              :key="user.id"
              class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50"
            >
              <span class="text-sm text-gray-800">{{ user.name }}</span>
              <Button
                variant="ghost"
                size="sm"
                :loading="detachingUserId === user.id"
                @click="detachUser(user.id)"
                class="text-red-500 hover:text-red-700 hover:bg-red-50"
              >
                Retirer
              </Button>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400 italic">Aucun technicien assigné</p>
        </div>

        <!-- Ajouter -->
        <div v-if="availableUsers.length">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Ajouter</p>
          <div class="flex gap-2">
            <select
              v-model="userToAttach"
              class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
            >
              <option value="">Choisir un technicien...</option>
              <option v-for="u in availableUsers" :key="u.id" :value="u.id">
                {{ u.name }}
              </option>
            </select>
            <Button
              :loading="!!attachingUserId"
              :disabled="!userToAttach"
              @click="attachUser"
            >
              Assigner
            </Button>
          </div>
        </div>

        <p v-else-if="!selectedDepot?.users?.length" class="text-xs text-gray-400">
          Tous les techniciens sont déjà assignés.
        </p>

      </div>
    </Modal>

  </AppLayout>
</template>