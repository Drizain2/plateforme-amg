// resources/js/Types/models.d.ts — ajout
export type StockMovementType =
    | 'in'
    | 'out'
    | 'adjustment'
    | 'transfer_in'
    | 'transfer_out';

export interface StockMovement {
    id: number;
    type: StockMovementType;
    type_label: string;
    is_debit: boolean;
    quantity: number;
    note?: string;
    created_at: string;
    stock?: { id: number; part: Pick<Part, 'id' | 'name' | 'sku'> | null };
    depot?: Pick<Depot, 'id' | 'name'>;
    transfer_depot?: Pick<Depot, 'id' | 'name'>;
    user?: { id: number; name: string } | null;
    ticket?: { id: number; reference: string } | null;
}
