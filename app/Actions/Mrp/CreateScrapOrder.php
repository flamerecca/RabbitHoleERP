<?php

namespace App\Actions\Mrp;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\ScrapOrder;
use App\Models\Team;
use App\Models\User;
use App\Services\DocumentSequence;
use Illuminate\Support\Facades\DB;

class CreateScrapOrder
{
    public function __construct(
        protected DocumentSequence $documentSequence,
        protected ValidateScrapOrderInput $validateInput,
    ) {
    }

    /**
     * Create a draft scrap order.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Team $team, User $user, array $attributes): ScrapOrder
    {
        $this->validateInput->handle($team, $attributes);

        return DB::transaction(function () use ($team, $user, $attributes) {
            $order = ScrapOrder::create([
                'team_id' => $team->id,
                'warehouse_id' => $attributes['warehouse_id'],
                'scrap_no' => $this->documentSequence->next($team, DocumentType::ScrapOrder, now()),
                'status' => DocumentStatus::Draft,
                'reason' => $attributes['reason'] ?? null,
                'created_by' => $user->id,
            ]);

            $order->items()->createMany($attributes['items']);

            return $order->fresh('items');
        });
    }
}
