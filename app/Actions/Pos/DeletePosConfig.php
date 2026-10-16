<?php

namespace App\Actions\Pos;

use App\Models\PosConfig;
use Illuminate\Support\Facades\DB;

class DeletePosConfig
{
    /**
     * Delete the config together with the links to its payment methods.
     */
    public function handle(PosConfig $config): void
    {
        DB::transaction(function () use ($config) {
            $config->delete();
        });
    }
}
