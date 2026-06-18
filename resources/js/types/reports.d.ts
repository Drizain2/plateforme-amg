export interface CashReportSummary {
    revenue_paid: number
    revenue_pending: number
    invoices_count: number
    invoices_paid_count: number
    avg_payment_days: number
    uninvoiced_count: number
}

export interface CashReportPeriod {
    label: string
    total: number
    count: number
}

export interface CashReportTechnician {
    technician: string
    total: number
    count: number
}

export interface CashReportClient {
    customer: string
    total: number
    count: number
}

export interface CashReportUninvoiced {
    id: number
    reference: string
    customer: string
    status: string
    closed_at: string
}
