export interface CustomerTicket {
  id: number
  reference: string
  status_label: string
  status_color: BadgeVariant
  device_name: string
  created_at: string
}

export interface CustomerDevice {
  id: number
  full_name: string
  type: string
  serial_number?: string
  color?: string
}

export interface Customer {
  id: number
  name: string
  email?: string
  phone?: string
  address?: string
  notes?: string
  tickets_count?: number
  devices_count?: number
  total_spent?: number
  tickets?: CustomerTicket[]
  devices?: CustomerDevice[]
}