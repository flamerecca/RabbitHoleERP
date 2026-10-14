<?php

namespace App\Actions\MasterData;

use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class DeleteWarehouse
{
    /**
     * Tables and columns whose rows keep the record from being deleted.
     *
     * @var list<array{0: string, 1: string}>
     */
    protected const REFERENCES = [
        ['stock_balances', 'warehouse_id'],
        ['purchase_orders', 'warehouse_id'],
        ['goods_receipts', 'warehouse_id'],
        ['purchase_returns', 'warehouse_id'],
        ['sales_orders', 'warehouse_id'],
        ['shipments', 'warehouse_id'],
        ['sales_returns', 'warehouse_id'],
        ['consignment_holds', 'warehouse_id'],
        ['warehouse_transfers', 'from_warehouse_id'],
        ['warehouse_transfers', 'to_warehouse_id'],
        ['stock_takes', 'warehouse_id'],
        ['material_requisitions', 'warehouse_id'],
        ['reordering_rules', 'warehouse_id'],
    ];

    /**
     * Delete the record unless other data still refers to it.
     */
    public function handle(Warehouse $warehouse): void
    {
        DB::transaction(function () use ($warehouse) {
            foreach (self::REFERENCES as [$table, $column]) {
                abort_if(DB::table($table)->where($column, $warehouse->id)->exists(), 409, __('Warehouses with stock or documents cannot be deleted.'));
            }

            $warehouse->delete();
        });
    }
}
