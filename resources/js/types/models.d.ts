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
