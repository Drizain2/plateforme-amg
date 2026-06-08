import type { Shop } from './models';

export type User = {
    id: number;
    shop_id:number
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    roles: { name: string }[];
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    shop: Shop;
};
