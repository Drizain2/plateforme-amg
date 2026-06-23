// resources/js/Types/settings.d.ts
import type { Plan } from './models'

export interface ShopSettings {
  name:     string
  email:    string
  phone?:   string
  address?: string
  logo_url?: string
  plan:     Plan
  tax_rate: number
}

export interface ProfileSettings {
  name:  string
  email: string
}