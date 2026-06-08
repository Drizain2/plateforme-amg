import type { RequestPayload } from '@inertiajs/core'
import { router } from '@inertiajs/vue3'
import { useDebounceFn } from '@vueuse/core'

export function useFilters(url: string) {
  const applyFilter = useDebounceFn((filters: RequestPayload) => {
    router.get(
      url,
      filters,
      { preserveState: true, preserveScroll: true, replace: true }
    )
  }, 300)

  return { applyFilter }
}