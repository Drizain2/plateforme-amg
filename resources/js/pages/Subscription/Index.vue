<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import SubscriptionController from '@/actions/App/Http/Controllers/SubscriptionController'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { Payment, Plan, Subscription } from '@/types'
import type { PaginatedResponse } from '@/types/pagination'

const props = defineProps<{
  shop: { id: number; name: string; plan: Plan }
  subscription: Subscription | null
  payments: PaginatedResponse<Payment>
  plans: Plan[]
  hasPendingPayment: boolean
}>()

const page = usePage()
const flash = computed(() => page.props.flash as { success?: string; error?: string; instructions?: string; reference?: string })

const fmt = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v)

const fmtDate = (d: string) =>
  new Date(d).toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' })

// ── Subscribe form ────────────────────────────────────────────────────────────
const showSubscribePanel = ref(false)

const subscribeForm = useForm({
  plan_id: props.shop.plan?.id ?? 0,
  billing_period: 'monthly' as 'monthly' | 'annual',
})

const selectedPlan = computed(() =>
  props.plans.find((p) => p.id === subscribeForm.plan_id) ?? props.shop.plan,
)

const monthlyPrice = computed(() => selectedPlan.value?.price ?? 0)
const annualPrice = computed(() => monthlyPrice.value * 10)

function submit() {
  subscribeForm.post(SubscriptionController.subscribe.url(), {
    preserveScroll: true,
    onSuccess: () => {
      showSubscribePanel.value = false
    },
  })
}

// ── Status badge mapping ──────────────────────────────────────────────────────
const statusBadge = (status: Payment['status']) =>
  ({
    pending: { variant: 'warning' as const, label: 'En attente' },
    validated: { variant: 'success' as const, label: 'Validé' },
    rejected: { variant: 'danger' as const, label: 'Rejeté' },
    refunded: { variant: 'default' as const, label: 'Remboursé' },
  })[status]

const subStatusBadge = (status: Subscription['status']) =>
  ({
    trial: { variant: 'info' as const, label: 'Essai' },
    active: { variant: 'success' as const, label: 'Actif' },
    expired: { variant: 'danger' as const, label: 'Expiré' },
    cancelled: { variant: 'default' as const, label: 'Annulé' },
    suspended: { variant: 'warning' as const, label: 'Suspendu' },
  })[status]
</script>

<template>
  <AppLayout title="Mon abonnement">
    <div class="max-w-3xl space-y-6">

      <h1 class="text-xl font-semibold text-gray-900">Mon abonnement</h1>

      <!-- Instructions de paiement manuel (flash) -->
      <div v-if="flash.reference" class="bg-amber-50 border border-amber-300 rounded-xl p-5 space-y-3">
        <div class="flex items-center gap-2">
          <span class="text-amber-700 font-semibold text-sm">Demande enregistrée — paiement en attente</span>
        </div>
        <p class="text-sm text-amber-800">{{ flash.instructions }}</p>
        <div class="flex items-center gap-2 bg-white rounded-lg border border-amber-200 px-3 py-2 w-fit">
          <span class="text-xs text-gray-500">Référence :</span>
          <span class="font-mono text-sm font-semibold text-gray-800 select-all">{{ flash.reference }}</span>
        </div>
        <p class="text-xs text-amber-700">Votre abonnement sera activé dès validation par notre équipe.</p>
      </div>

      <!-- Alerte paiement déjà en attente -->
      <div v-else-if="hasPendingPayment" class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4">
        <p class="text-sm text-amber-800">
          Vous avez déjà un paiement en attente de validation par notre équipe.
          Une nouvelle demande annulera automatiquement l'ancienne.
        </p>
      </div>

      <!-- Abonnement actif -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500 mb-1">Plan actuel</p>
            <p class="text-lg font-semibold text-gray-900">{{ shop.plan?.name ?? '—' }}</p>
          </div>
          <Badge v-if="subscription" :variant="subStatusBadge(subscription.status).variant">
            {{ subStatusBadge(subscription.status).label }}
          </Badge>
          <Badge v-else variant="default">Aucun abonnement</Badge>
        </div>

        <template v-if="subscription">
          <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-gray-400">Période</p>
              <p class="text-gray-800 font-medium">
                {{ subscription.billing_period === 'annual' ? 'Annuelle' : 'Mensuelle' }}
              </p>
            </div>
            <div>
              <p class="text-gray-400">Expire le</p>
              <p class="text-gray-800 font-medium">{{ fmtDate(subscription.ends_at) }}</p>
            </div>
          </div>
        </template>

        <div class="mt-4 pt-4 border-t border-gray-100">
          <Button @click="showSubscribePanel = !showSubscribePanel" variant="secondary" size="sm">
            {{ subscription ? 'Renouveler / Changer de plan' : 'Souscrire' }}
          </Button>
        </div>
      </div>

      <!-- Panneau souscription -->
      <div v-if="showSubscribePanel" class="bg-indigo-50 border border-indigo-200 rounded-xl p-6 space-y-5">
        <div>
          <h2 class="font-medium text-indigo-900">Nouvelle demande d'abonnement</h2>
          <p class="text-sm text-indigo-600 mt-1">
            Sélectionnez un plan et une période. Votre demande sera traitée par notre équipe.
          </p>
        </div>

        <!-- Avertissement si abonnement actif -->
        <div v-if="subscription?.status === 'active'" class="bg-white border border-indigo-200 rounded-lg px-4 py-3 text-sm text-gray-600">
          Votre abonnement actuel est valide jusqu'au <strong>{{ fmtDate(subscription.ends_at) }}</strong>.
          Un renouvellement sera ajouté à la suite.
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <!-- Choix du plan -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Plan</label>
            <div class="space-y-2">
              <label
                v-for="plan in plans"
                :key="plan.id"
                class="flex items-center justify-between gap-3 bg-white border rounded-lg px-4 py-3 cursor-pointer transition"
                :class="subscribeForm.plan_id === plan.id
                  ? 'border-indigo-500 ring-1 ring-indigo-500'
                  : 'border-gray-200 hover:border-gray-300'"
              >
                <div class="flex items-center gap-3">
                  <input
                    type="radio"
                    :value="plan.id"
                    v-model="subscribeForm.plan_id"
                    class="text-indigo-600 focus:ring-indigo-500"
                  />
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ plan.name }}</p>
                    <p v-if="plan.description" class="text-xs text-gray-500">{{ plan.description }}</p>
                  </div>
                </div>
                <span class="text-sm font-semibold text-gray-700 whitespace-nowrap">{{ fmt(plan.price) }}/mois</span>
              </label>
            </div>
            <p v-if="subscribeForm.errors.plan_id" class="text-xs text-red-500 mt-1">{{ subscribeForm.errors.plan_id }}</p>
          </div>

          <!-- Période de facturation -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Période</label>
            <div class="flex gap-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="subscribeForm.billing_period" value="monthly"
                  class="text-indigo-600 focus:ring-indigo-500" />
                <span class="text-sm text-gray-700">Mensuel — <strong>{{ fmt(monthlyPrice) }}</strong>/mois</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="subscribeForm.billing_period" value="annual"
                  class="text-indigo-600 focus:ring-indigo-500" />
                <span class="text-sm text-gray-700">
                  Annuel — <strong>{{ fmt(annualPrice) }}</strong>/an
                  <span class="text-green-600 text-xs font-medium ml-1">2 mois offerts</span>
                </span>
              </label>
            </div>
            <p v-if="subscribeForm.errors.billing_period" class="text-xs text-red-500 mt-1">
              {{ subscribeForm.errors.billing_period }}
            </p>
          </div>

          <div class="flex gap-2 pt-1">
            <Button type="submit" :loading="subscribeForm.processing">Envoyer la demande</Button>
            <Button type="button" variant="secondary" @click="showSubscribePanel = false">Annuler</Button>
          </div>
        </form>
      </div>

      <!-- Historique paiements -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="text-sm font-medium text-gray-700">Historique des paiements</h2>
        </div>

        <div v-if="payments.data.length === 0" class="px-6 py-12 text-center text-sm text-gray-400">
          Aucun paiement pour le moment
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Référence</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Plan</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Montant</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Période</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Statut</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ payment.reference }}</td>
                <td class="px-4 py-3 text-gray-700">{{ payment.plan?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ fmt(payment.amount) }}</td>
                <td class="px-4 py-3 text-gray-500 text-xs">
                  {{ payment.billing_period === 'annual' ? 'Annuelle' : 'Mensuelle' }}
                </td>
                <td class="px-4 py-3">
                  <Badge :variant="statusBadge(payment.status).variant">
                    {{ statusBadge(payment.status).label }}
                  </Badge>
                </td>
                <td class="px-4 py-3 text-gray-400 text-xs">{{ fmtDate(payment.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
