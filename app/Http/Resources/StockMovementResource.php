<?php

namespace App\Http\Resources;

use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockMovement
 */
class StockMovementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'product_id' => $this->product_id,
            'stock_lot_id' => $this->stock_lot_id,
            'type' => $this->type->value,
            'quantity' => (float) $this->quantity,
            'balance_after' => (float) $this->balance_after,
            'total_cost' => (float) $this->total_cost,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'note' => $this->note,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toJSON(),
        ];
    }
}
