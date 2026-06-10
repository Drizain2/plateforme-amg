export interface DashboardStats {
    tickets_open: number;
    tickets_today: number;
    tickets_done_month: number;
    low_stock_count: number;
    revenue_month: number;
    avg_repair_days: number;
}

export interface DashboardCharts {
    tickets_by_day: { date: string; label: string; total: number }[];
    tickets_by_status: { status: string; total: number }[];
    tickets_by_depot: { depot: string; total: number }[];
}

export interface DashboardRecent {
    tickets: {
        id: number;
        reference: string;
        status_label: string;
        status_color: string;
        priority_color: string;
        customer_name: string;
        device_name: string;
        technicien?: string;
        created_at: string;
    }[];
    low_stock: {
        id: number;
        part_name: string;
        depot_name: string | null;
        quantity: number;
        alert_quantity: number;
    }[];
}

export interface DashboardAlerts {
    overdue: {
        id: number;
        reference: string;
        customer_name: string;
        overdue_days: number;
    }[];
}
