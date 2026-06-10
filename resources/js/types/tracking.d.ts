// resources/js/Types/tracking.d.ts
export interface TrackingStep {
  label:  string
  status: string
  state:  'done' | 'current' | 'pending' | 'cancelled'
}

export interface TrackingEvent {
  type:       string
  note?:      string
  metadata?:  Record<string, unknown>
  created_at: string
}

export interface TrackingTicket {
  reference:              string
  tracking_token:         string
  status:                 string
  status_label:           string
  status_color:           string
  priority_label:         string
  description:            string
  diagnosis?:             string
  estimated_price?:       number
  estimated_return_date?: string
  closed_at?:             string
  created_at:             string
  device: {
    full_name:      string
    type:           string
    color?:         string
    serial_number?: string
  }
  shop: {
    name:   string
    email?: string
    phone?: string
  }
  events: TrackingEvent[]
  steps:  TrackingStep[]
}
