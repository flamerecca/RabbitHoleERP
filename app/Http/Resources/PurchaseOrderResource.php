<?php

namespace App\Http\Resources;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PurchaseOrder
 */
class PurchaseOrderResource extends JsonResource
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
            'order_no' => $this->order_no,
            'supplier_id' => $this->supplier_id,
            'warehouse_id' => $this->warehouse_id,
            'currency_id' => $this->currency_id,
            'exchange_rate' => (float) $this->exchange_rate,
            'status' => $this->status->value,
            'order_date' => $this->order_date->toDateString(),
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'total_amount' => (float) $this->total_amount,
            'created_by' => $this->created_by,
            'items' => $this->items->map(fn (PurchaseOrderItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'unit_id' => $item->unit_id,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'tax_rate_id' => $item->tax_rate_id,
                'subtotal' => round((float) $item->quantity * (float) $item->unit_price, 4),
                'received_quantity' => (float) $item->received_quantity,
            ]),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
