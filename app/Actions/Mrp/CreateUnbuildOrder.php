<?php

namespace App\Actions\Mrp;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Team;
use App\Models\UnbuildOrder;
use App\Models\User;
use App\Services\DocumentSequence;
use Illuminate\Support\Facades\DB;

class CreateUnbuildOrder
{
    public function __construct(
        protected DocumentSequence $documentSequence,
        protected PrepareUnbuildOrder $prepare,
    ) {
    }

    /**
     * Create a draft unbuild order with the components of the active bom of the product.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Team $team, User $user, array $attributes): UnbuildOrder
    {
        $prepared = $this->prepare->handle($team, $attributes['product_id'], $attributes['quantity'], $attributes['warehouse_id']);

        return DB::transaction(function () use ($team, $user, $attributes, $prepared) {
            $order = UnbuildOrder::create([
                'team_id' => $team->id,
                'unbuild_no' => $this->documentSequence->next($team, DocumentType::UnbuildOrder, now()),
                'warehouse_id' => $attributes['warehouse_id'],
                'product_id' => $attributes['product_id'],
                'bom_id' => $prepared['bom']->id,
                'quantity' => $attributes['quantity'],
                'status' => DocumentStatus::Draft,
                'reason' => $attributes['reason'] ?? null,
                'created_by' => $user->id,
            ]);

            $order->items()->createMany($prepared['items']);

            return $order->fresh('items');
        });
    }
}
