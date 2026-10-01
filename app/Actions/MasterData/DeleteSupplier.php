<?php

namespace App\Actions\MasterData;

use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class DeleteSupplier
{
    /**
     * Tables and columns whose rows keep the record from being deleted.
     *
     * @var list<array{0: string, 1: string}>
     */
    protected const REFERENCES = [
        ['purchase_orders', 'supplier_id'],
        ['purchase_returns', 'supplier_id'],
        ['product_suppliers', 'supplier_id'],
    ];

    /**
     * Delete the record unless other data still refers to it.
     */
    public function handle(Supplier $supplier): void
    {
        DB::transaction(function () use ($supplier) {
            foreach (self::REFERENCES as [$table, $column]) {
                abort_if(DB::table($table)->where($column, $supplier->id)->exists(), 409, __('Suppliers with purchase history or supplier prices cannot be deleted.'));
            }

            $supplier->delete();
        });
    }
}
