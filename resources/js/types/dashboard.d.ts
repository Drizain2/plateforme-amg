// resources/js/Types/dashboard.d.ts
export interface DashboardStats {
  tickets_today: number
  low_stock_count: number
}

export interface DashboardCharts {
  tickets_by_day: { date: string; label: string; total: number }[]
  tickets_by_status: { status: string; total: number }[]
  tickets_by_depot: { depot: string; total: number }[]
}

export interface DashboardRecent {
  low_stock: {
    id: number
    part_name: string
    depot_name: string | null
    quantity: number
    alert_quantity: number
  }[]
}

export interface DashboardAlerts {
  overdue: {
    id: number
    reference: string
    customer_name: string
    overdue_days: number
  }[]
}
