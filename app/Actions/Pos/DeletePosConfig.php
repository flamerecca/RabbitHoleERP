<?php

namespace App\Actions\Pos;

use App\Models\PosConfig;
use App\Models\PosSession;
use Illuminate\Support\Facades\DB;

class DeletePosConfig
{
    /**
     * Delete the config together with the links to its payment methods, unless it has sessions.
     */
    public function handle(PosConfig $config): void
    {
        DB::transaction(function () use ($config) {
            abort_if(
                PosSession::query()->where('pos_config_id', $config->id)->exists(),
                409,
                __('POS stores with sessions cannot be deleted, deactivate them instead.'),
            );

            $config->delete();
        });
    }
}
