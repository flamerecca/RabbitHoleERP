<?php

namespace App\Actions\MasterData;

use App\Models\TaxRate;
use Illuminate\Support\Facades\DB;

class DeleteTaxRate
{
    /**
     * Tables and columns whose rows keep the record from being deleted.
     *
     * @var list<array{0: string, 1: string}>
     */
    protected const REFERENCES = [
        ['purchase_order_items', 'tax_rate_id'],
        ['sales_order_items', 'tax_rate_id'],
    ];

    /**
     * Delete the record unless other data still refers to it.
     */
    public function handle(TaxRate $taxRate): void
    {
        DB::transaction(function () use ($taxRate) {
            foreach (self::REFERENCES as [$table, $column]) {
                abort_if(DB::table($table)->where($column, $taxRate->id)->exists(), 409, __('Tax rates used by document lines cannot be deleted.'));
            }

            $taxRate->delete();
        });
    }
}
