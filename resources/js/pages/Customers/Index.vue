<!-- resources/js/pages/Customers/Index.vue -->
<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import CustomerController from '@/actions/App/Http/Controllers/Customer/CustomerController'
import TicketController from '@/actions/App/Http/Controllers/Ticket/TicketController'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import { useFilters } from '@/Composables/useFilters'
import { useToast } from '@/Composables/useToast'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { Customer, PaginatedResource } from '@/types'

const props = defineProps<{
  customers: PaginatedResource<Customer & { tickets_count: number }>
  filters: { search?: string }
}>()

const { success, error } = useToast()
const { applyFilter } = useFilters(CustomerController.index.url())
const page = usePage()

watch(() => page.props.flash, (flash) => {
  if (flash.success) success(flash.success)
  if (flash.error) error(flash.error)
}, { immediate: true })

const search = ref(props.filters?.search ?? '')
watch(search, (val) => applyFilter({ search: val || undefined }))

function goToPage(url: string | null) {
  if (!url) return
  router.visit(url, { preserveScroll: true, preserveState: true })
}
</script>

<template>
  <AppLayout title="Clients">
    <div class="space-y-6">

      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Clients</h1>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ customers.meta.total }} client{{ customers.meta.total > 1 ? 's' : '' }}
          </p>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <Input
          v-model="search"
          placeholder="Rechercher par nom, email, téléphone..."
          class="max-w-sm"
        />
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Nom</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Email</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Téléphone</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Tickets</th>
                <th class="px-4 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="customers.data.length === 0">
                <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                  Aucun client trouvé
                </td>
              </tr>
              <tr
                v-for="customer in customers.data"
                :key="customer.id"
                class="hover:bg-gray-50 transition"
              >
                <td class="px-4 py-3 font-medium text-gray-900">{{ customer.name }}</td>

                <td class="px-4 py-3 text-gray-500">
                  <a
                    v-if="customer.email"
                    :href="`mailto:${customer.email}`"
                    class="hover:text-indigo-600 transition"
                  >
                    {{ customer.email }}
                  </a>
                  <span v-else class="text-gray-300">—</span>
                </td>

                <td class="px-4 py-3 text-gray-500">
                  <a
                    v-if="customer.phone"
                    :href="`tel:${customer.phone}`"
                    class="hover:text-indigo-600 transition"
                  >
                    {{ customer.phone }}
                  </a>
                  <span v-else class="text-gray-300">—</span>
                </td>

                <td class="px-4 py-3">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                    {{ customer.tickets_count }}
                  </span>
                </td>

                <td class="px-4 py-3 text-right">
                  <Button
                    variant="ghost"
                    size="sm"
                    @click="router.visit(TicketController.index.url({ search: customer.name }))"
                  >
                    Voir tickets
                  </Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-if="customers.meta.last_page > 1"
          class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600"
        >
          <span>{{ customers.meta.from }}–{{ customers.meta.to }} sur {{ customers.meta.total }}</span>
          <div class="flex gap-1">
            <Button variant="secondary" size="sm" :disabled="!customers.links.prev" @click="goToPage(customers.links.prev)">
              ← Précédent
            </Button>
            <Button variant="secondary" size="sm" :disabled="!customers.links.next" @click="goToPage(customers.links.next)">
              Suivant →
            </Button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
