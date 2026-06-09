export interface Shop {
    id: number;
    name: string;
    slug: string;
    email: string;
    plan: 'starter' | 'pro' | 'entreprise';
    is_active: boolean;
}

export interface Depot {
    id: number;
    shop_id: number;
    name: string;
    address?: string;
    phone?: string;
    is_active: boolean;
    parts_count?: number;
    users?: {
        id: number;
        name: string;
    }[];
}
export interface Supplier {
    id: number;
    name: string;
    email?: string;
    phone?: string;
    address?: string;
    is_active: boolean;
    parts_count?: number;
}
export interface Category {
    id: number;
    shop_id: number;
    name: string;
    is_active: boolean;
}

export interface StockDepot {
    id: number;
    depot_id: number;
    depot_name: string | null;
    quantity: number;
    alert_quantity: number;
    is_critical: boolean;
}
export interface Part {
    id: number;
    supplier?: Pick<Supplier, 'id' | 'name'>;
    name: string;
    sku?: string;
    category?: Pick<Category, 'id' | 'name'>;
    stock_depots?: StockDepot[];
    brand_compat: string[];
    unit_price: number;
    sell_price: number;
    is_active: boolean;
}

export interface StockMovement {
    id: number;
    type: 'in' | 'out' | 'adjustment' | 'transfer_in' | 'transfer_out';
    type_label: string;
    quantity: number;
    ticket_id?: number;
    note?: string;
    stock?: { id: number; part: Pick<Part, 'id' | 'name' | 'sku'> | null };
    depot?: Pick<Depot, 'id' | 'name'>;
    transfer_depot?: Pick<Depot, 'id' | 'name'>;
    user?: { id: number; name: string };
    created_at: string;
}
export interface Customer {
    id: number;
    name: string;
    email?: string;
    phone?: string;
    address?: string;
    notes?: string;
}

export interface Device {
    id: number;
    customer_id: number;
    type: string;
    brand: string;
    model: string;
    full_name: string;
    serial_number?: string;
    color?: string;
    condition_in?: string;
}

export type TicketStatus =
    | 'received'
    | 'diagnosing'
    | 'waiting_parts'
    | 'repairing'
    | 'done'
    | 'returned'
    | 'cancelled';

export type TicketPriority = 'low' | 'normal' | 'high' | 'urgent';

export type BadgeVariant =
    | 'default'
    | 'info'
    | 'success'
    | 'warning'
    | 'danger';

export interface TicketEvent {
    id: number;
    type: string;
    note?: string;
    metadata?: Record<string, unknown>;
    created_at: string;
    user?: { id: number; name: string };
}

export interface TicketPart {
    id: number;
    quantity: number;
    unit_price: number;
    total: number;
    part: { id: number; name: string };
}

export interface Ticket {
    id: number;
    reference: string;
    status: TicketStatus;
    status_label: string;
    status_color: BadgeVariant;
    priority: TicketPriority;
    priority_label: string;
    priority_color: BadgeVariant;
    description: string;
    diagnosis?: string;
    estimated_price?: number;
    estimated_return_date?: string;
    closed_at?: string;
    created_at: string;
    next_statuses: { value: TicketStatus; label: string }[];
    customer?: Customer;
    device?: Device;
    technician?: { id: number; name: string } | null;
    depot?: { id: number; name: string };
    events?: TicketEvent[];
    parts?: TicketPart[];
}
