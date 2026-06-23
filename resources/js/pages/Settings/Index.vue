<!-- resources/js/Pages/Settings/Index.vue -->
<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import SettingsController from '@/actions/App/Http/Controllers/SettingsController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import { usePermission } from '@/Composables/usePermission'
import { useToast } from '@/Composables/useToast'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { ShopSettings, ProfileSettings, Plan } from '@/types'

const props = defineProps<{
    shop: ShopSettings
    plans: Plan[]
    profile: ProfileSettings
}>()

const { success, error } = useToast()
const { can } = usePermission()
const page = usePage()

watch(() => page.props.flash, (flash) => {
    if (flash.success) {
        success(flash.success)
    }

    if (flash.error) {
        error(flash.error)
    }
}, { immediate: true })

// -----------------------------------------------
// Onglets — l'atelier et l'abonnement ne concernent que les comptes
// disposant de settings.manage ; le profil et le mot de passe restent
// accessibles à tout utilisateur authentifié.
// -----------------------------------------------
const canManageShop = computed(() => can('settings.manage'))

const allTabs = [
    { id: 'shop', label: 'Atelier', adminOnly: true },
    { id: 'profile', label: 'Mon profil', adminOnly: false },
    { id: 'password', label: 'Mot de passe', adminOnly: false },
    { id: 'plan', label: 'Abonnement', adminOnly: true },
]
const tabs = computed(() => allTabs.filter(t => !t.adminOnly || canManageShop.value))
const activeTab = ref(canManageShop.value ? 'shop' : 'profile')

// -----------------------------------------------
// Formulaire atelier
// -----------------------------------------------
const logoPreview = ref<string | null>(props.shop.logo_url ?? null)

const shopForm = useForm({
    name: props.shop.name,
    email: props.shop.email,
    phone: props.shop.phone ?? '',
    address: props.shop.address ?? '',
    tax_rate: props.shop.tax_rate,
    logo: null as File | null,
})

function onLogoChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]

    if (!file) {
        return
    }

    shopForm.logo = file
    logoPreview.value = URL.createObjectURL(file)
}

function submitShop() {
    shopForm.post(SettingsController.updateShop.url(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => shopForm.reset('logo'),
    })
}

// -----------------------------------------------
// Formulaire profil
// -----------------------------------------------
const profileForm = useForm({
    name: props.profile.name,
    email: props.profile.email,
})

function submitProfile() {
    profileForm.put(SettingsController.updateProfile.url(), { preserveScroll: true })
}

// -----------------------------------------------
// Formulaire mot de passe
// -----------------------------------------------
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

function submitPassword() {
    passwordForm.put(SettingsController.updatePassword.url(), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    })
}

// -----------------------------------------------
// Plan
// -----------------------------------------------
const planColors: Record<string, 'default' | 'info' | 'success'> = {
    starter: 'default',
    pro: 'info',
    enterprise: 'success',
}
</script>

<template>
    <AppLayout title="Paramètres">
        <div class="max-w-3xl space-y-6">

            <h1 class="text-xl font-semibold text-gray-900">Paramètres</h1>

            <!-- Tabs -->
            <div class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit">
                <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                    'px-4 py-1.5 rounded-lg text-sm font-medium transition',
                    activeTab === tab.id
                        ? 'bg-white text-gray-900 shadow-sm'
                        : 'text-gray-500 hover:text-gray-700'
                ]">
                    {{ tab.label }}
                </button>
            </div>

            <!-- -----------------------------------------------
           Onglet Atelier
      ----------------------------------------------- -->
            <div v-if="activeTab === 'shop'">
                <form @submit.prevent="submitShop" class="bg-white rounded-xl border border-gray-200 divide-y">

                    <!-- Logo -->
                    <div class="p-6 flex items-center gap-6">
                        <div
                            class="w-20 h-20 rounded-xl border-2 border-dashed border-gray-300 overflow-hidden flex items-center justify-center bg-gray-50 shrink-0">
                            <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-cover" />
                            <span v-else class="text-2xl">🏪</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-700 mb-1">Logo de l'atelier</p>
                            <label
                                class="cursor-pointer inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Changer le logo
                                <input type="file" accept="image/*" class="hidden" @change="onLogoChange" />
                            </label>
                            <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP — max 2Mo</p>
                            <p v-if="shopForm.errors.logo" class="text-xs text-red-500 mt-1">{{ shopForm.errors.logo }}
                            </p>
                        </div>
                    </div>

                    <!-- Champs -->
                    <div class="p-6 grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'atelier *</label>
                            <Input v-model="shopForm.name" :error="shopForm.errors.name" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <Input v-model="shopForm.email" type="email" :error="shopForm.errors.email" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                            <Input v-model="shopForm.phone" :error="shopForm.errors.phone" />
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                            <Input v-model="shopForm.address" :error="shopForm.errors.address" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">TVA par défaut (%)</label>
                            <Input v-model="shopForm.tax_rate" type="number" step="0.01"
                                :error="shopForm.errors.tax_rate" />
                            <p class="text-xs text-gray-400 mt-1">Appliqué automatiquement à toutes les nouvelles
                                factures</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 flex justify-end">
                        <Button type="submit" :loading="shopForm.processing">Enregistrer</Button>
                    </div>
                </form>
            </div>

            <!-- -----------------------------------------------
           Onglet Profil
      ----------------------------------------------- -->
            <div v-if="activeTab === 'profile'">
                <form @submit.prevent="submitProfile" class="bg-white rounded-xl border border-gray-200 divide-y">
                    <div class="p-6 grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                            <Input v-model="profileForm.name" :error="profileForm.errors.name" />
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <Input v-model="profileForm.email" type="email" :error="profileForm.errors.email" />
                        </div>
                    </div>
                    <div class="px-6 py-4 flex justify-end">
                        <Button type="submit" :loading="profileForm.processing">Mettre à jour</Button>
                    </div>
                </form>
            </div>

            <!-- -----------------------------------------------
           Onglet Mot de passe
      ----------------------------------------------- -->
            <div v-if="activeTab === 'password'">
                <form @submit.prevent="submitPassword" class="bg-white rounded-xl border border-gray-200 divide-y">
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel *</label>
                            <Input v-model="passwordForm.current_password" type="password"
                                :error="passwordForm.errors.current_password" autocomplete="current-password" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe *</label>
                            <Input v-model="passwordForm.password" type="password" :error="passwordForm.errors.password"
                                autocomplete="new-password" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe
                                *</label>
                            <Input v-model="passwordForm.password_confirmation" type="password"
                                :error="passwordForm.errors.password_confirmation" autocomplete="new-password" />
                        </div>
                    </div>
                    <div class="px-6 py-4 flex justify-end">
                        <Button type="submit" :loading="passwordForm.processing">Changer le mot de passe</Button>
                    </div>
                </form>
            </div>

            <!-- -----------------------------------------------
           Onglet Abonnement
      ----------------------------------------------- -->
            <div v-if="activeTab === 'plan'">
                <div class="space-y-4">

                    <!-- Plan actuel -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm font-medium text-gray-700">Plan actuel</p>
                            <Badge :variant="planColors[shop.plan.slug]">
                                {{ shop.plan.name }}
                            </Badge>
                        </div>
                        <ul class="space-y-2">
                            <li v-for="feature in shop.plan.features" :key="feature"
                                class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                {{ feature }}
                            </li>
                        </ul>
                    </div>

                    <!-- Comparaison plans -->
                    <div class="grid grid-cols-3 gap-4">
                        <div v-for="plan in plans" :key="plan.id" :class="[
                            'bg-white rounded-xl border p-5 space-y-4 transition',
                            shop.plan.id === plan.id
                                ? 'border-indigo-400 ring-2 ring-indigo-100'
                                : 'border-gray-200'
                        ]">
                            <div class="flex items-center justify-between">
                                <p class="font-semibold text-gray-900">{{ plan.name }}</p>
                                <Badge v-if="shop.plan.id === plan.id" variant="info">Actuel</Badge>
                            </div>
                            <ul class="space-y-1.5">
                                <li v-for="feature in plan.features" :key="feature"
                                    class="flex items-start gap-1.5 text-xs text-gray-500">
                                    <svg class="w-3.5 h-3.5 text-green-500 shrink-0 mt-0.5" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ feature }}
                                </li>
                            </ul>
                            <Button v-if="shop.plan.id !== plan.id" variant="secondary" size="sm" class="w-full justify-center"
                                @click="() => { }">
                                Passer à {{ plan.name }}
                            </Button>
                            <p v-else class="text-xs text-center text-indigo-600 font-medium">Plan actif</p>
                        </div>
                    </div>

                    <p class="text-xs text-center text-gray-400">
                        Pour modifier votre abonnement, contactez le support.
                    </p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>