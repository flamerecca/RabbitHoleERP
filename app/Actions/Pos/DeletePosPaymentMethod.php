<?php

namespace App\Actions\Pos;

use App\Models\PosPaymentMethod;
use Illuminate\Support\Facades\DB;

class DeletePosPaymentMethod
{
    /**
     * Delete the payment method unless a config still accepts it.
     */
    public function handle(PosPaymentMethod $method): void
    {
        DB::transaction(function () use ($method) {
            abort_if(
                DB::table('pos_config_payment_method')->where('pos_payment_method_id', $method->id)->exists(),
                409,
                __('Payment methods used by a POS cannot be deleted, deactivate them instead.'),
            );

            $method->delete();
        });
    }
}
