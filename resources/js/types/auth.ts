import type { AppNotification, Depot, Shop } from './models';

export type User = {
    id: number;
    shop_id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    roles: Role[];
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type UserRoleName = 'super_admin' | 'admin' | 'gestionnaire' | 'technicien' | 'caissiere';

export type Role = {
    id: string | number;
    name: string;
};

export type Permission =
    | 'stock.view'
    | 'stock.create'
    | 'stock.edit'
    | 'stock.delete'
    | 'stock.restock'
    | 'stock.transfer'
    | 'stock.adjust'
    | 'tickets.view'
    | 'tickets.create'
    | 'tickets.edit'
    | 'tickets.delete'
    | 'tickets.transition'
    | 'tickets.assign'
    | 'customers.view'
    | 'customers.create'
    | 'customers.edit'
    | 'customers.delete'
    | 'invoices.view'
    | 'invoices.create'
    | 'invoices.edit'
    | 'invoices.delete'
    | 'invoices.mark_paid'
    | 'depots.view'
    | 'depots.manage'
    | 'users.view'
    | 'users.manage'
    | 'settings.manage'
    | 'dashboard.view'
    | 'dashboard.analytics';

export interface PermissionOverride {
    granted: boolean;
}

export interface ShopUser {
    id: number;
    name: string;
    email: string;
    role: UserRoleName;
    is_active: boolean;
    depot_ids: number[];
    depots: { id: number; name: string }[];
    tickets_count: number;
    tickets_count_label: string;
    created_at: string;
}
export type Auth = {
    user: User;
    shop: Shop;
    depotActive: Depot | null;
    depots: Pick<Depot, 'id' | 'name' | 'is_active'>[];
    isGlobalView: boolean;
    unread_count: AppNotification;
    permissions: Permission[];
};
