<?php

namespace App\Actions\MasterData;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class DeleteCustomer
{
    /**
     * Tables and columns whose rows keep the record from being deleted.
     *
     * @var list<array{0: string, 1: string}>
     */
    protected const REFERENCES = [
        ['sales_orders', 'customer_id'],
        ['sales_returns', 'customer_id'],
    ];

    /**
     * Delete the record unless other data still refers to it.
     */
    public function handle(Customer $customer): void
    {
        DB::transaction(function () use ($customer) {
            foreach (self::REFERENCES as [$table, $column]) {
                abort_if(DB::table($table)->where($column, $customer->id)->exists(), 409, __('Customers with sales history cannot be deleted.'));
            }

            $customer->delete();
        });
    }
}
