<?php

namespace App\Http\Resources;

use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SalesReturn
 */
class SalesReturnResource extends JsonResource
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
            'return_no' => $this->return_no,
            'customer_id' => $this->customer_id,
            'shipment_id' => $this->shipment_id,
            'warehouse_id' => $this->warehouse_id,
            'return_date' => $this->return_date->toDateString(),
            'status' => $this->status->value,
            'reason' => $this->reason,
            'total_amount' => (float) $this->total_amount,
            'items' => $this->items->map(fn (SalesReturnItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => (float) $item->quantity,
                'disposition' => $item->disposition->value,
                'stock_lot_id' => $item->stock_lot_id,
                'is_whole_lot' => $item->is_whole_lot,
            ]),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
