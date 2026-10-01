<?php

namespace App\Actions\MasterData;

use App\Models\Currency;
use Illuminate\Support\Facades\DB;

class DeleteCurrency
{
    /**
     * Tables and columns whose rows keep the record from being deleted.
     *
     * @var list<array{0: string, 1: string}>
     */
    protected const REFERENCES = [
        ['suppliers', 'currency_id'],
        ['customers', 'currency_id'],
        ['product_suppliers', 'currency_id'],
        ['purchase_orders', 'currency_id'],
        ['sales_orders', 'currency_id'],
    ];

    /**
     * Delete the record unless other data still refers to it.
     */
    public function handle(Currency $currency): void
    {
        DB::transaction(function () use ($currency) {
            abort_if($currency->is_base, 409, __('The base currency cannot be deleted.'));

            foreach (self::REFERENCES as [$table, $column]) {
                abort_if(DB::table($table)->where($column, $currency->id)->exists(), 409, __('Currencies in use cannot be deleted.'));
            }

            $currency->delete();
        });
    }
}
