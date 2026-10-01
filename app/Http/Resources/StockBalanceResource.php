<?php

namespace App\Http\Resources;

use App\Models\StockBalance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockBalance
 */
class StockBalanceResource extends JsonResource
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
            'quantity_on_hand' => (float) $this->quantity_on_hand,
            'quantity_reserved' => (float) $this->quantity_reserved,
            'quantity_available' => round((float) $this->quantity_on_hand - (float) $this->quantity_reserved, 4),
            'average_cost' => (float) $this->average_cost,
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
