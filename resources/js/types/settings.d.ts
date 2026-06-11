// resources/js/Types/settings.d.ts
export interface ShopSettings {
  name:     string
  email:    string
  phone?:   string
  address?: string
  logo_url?: string
  plan:     'starter' | 'pro' | 'enterprise'
  tax_rate: number
}

export interface ProfileSettings {
  name:  string
  email: string
}