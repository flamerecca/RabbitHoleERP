<?php

namespace App\Actions\Mrp;

use App\Enums\DocumentType;
use App\Enums\ManufacturingOrderStatus;
use App\Models\ManufacturingOrder;
use App\Models\Team;
use App\Models\User;
use App\Services\DocumentSequence;
use Illuminate\Support\Facades\DB;

class CreateManufacturingOrder
{
    public function __construct(
        protected DocumentSequence $documentSequence,
        protected ExpandActiveBom $expandBom,
    ) {
    }

    /**
     * Create a draft manufacturing order with the components of the active bom of the product.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Team $team, User $user, array $attributes): ManufacturingOrder
    {
        $expanded = $this->expandBom->handle($team, $attributes['product_id'], $attributes['quantity'], $attributes['warehouse_id']);

        return DB::transaction(function () use ($team, $user, $attributes, $expanded) {
            $order = ManufacturingOrder::create([
                'team_id' => $team->id,
                'mo_no' => $this->documentSequence->next($team, DocumentType::ManufacturingOrder, now()),
                'product_id' => $attributes['product_id'],
                'bom_id' => $expanded['bom']->id,
                'warehouse_id' => $attributes['warehouse_id'],
                'quantity' => $attributes['quantity'],
                'status' => ManufacturingOrderStatus::Draft,
                'planned_date' => $attributes['planned_date'] ?? null,
                'created_by' => $user->id,
            ]);

            $order->items()->createMany($expanded['items']);

            return $order->fresh('items');
        });
    }
}
