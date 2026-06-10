import type { AppNotification, Shop } from './models';

export type User = {
    id: number;
    shop_id:number
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    roles: UserRole[];
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type UserRole = 'admin' | 'technicien'

export interface ShopUser {
  id: number
  name: string
  email: string
  role: UserRole
  is_active: boolean
  depot_ids: number[]
  depots: { id: number; name: string }[]
  tickets_count: number
  created_at: string
}
export type Auth = {
    user: User;
    shop: Shop;
    unread_count: AppNotification
};
