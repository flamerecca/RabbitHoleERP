<?php

namespace App\Http\Resources;

use App\Models\StockTake;
use App\Models\StockTakeItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockTake
 */
class StockTakeResource extends JsonResource
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
            'take_no' => $this->take_no,
            'warehouse_id' => $this->warehouse_id,
            'status' => $this->status->value,
            'taken_date' => $this->taken_date->toDateString(),
            'created_by' => $this->created_by,
            'items' => $this->items->map(fn (StockTakeItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'stock_lot_id' => $item->stock_lot_id,
                'system_quantity' => (float) $item->system_quantity,
                'counted_quantity' => $item->counted_quantity === null ? null : (float) $item->counted_quantity,
                'difference' => $item->difference === null ? null : (float) $item->difference,
            ]),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
