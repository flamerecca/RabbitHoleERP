<?php

namespace App\Http\Resources;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GoodsReceipt
 */
class GoodsReceiptResource extends JsonResource
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
            'receipt_no' => $this->receipt_no,
            'purchase_order_id' => $this->purchase_order_id,
            'supplier_id' => $this->purchaseOrder->supplier_id,
            'warehouse_id' => $this->warehouse_id,
            'received_date' => $this->received_date->toDateString(),
            'status' => $this->status->value,
            'created_by' => $this->created_by,
            'items' => $this->items->map(fn (GoodsReceiptItem $item) => [
                'id' => $item->id,
                'purchase_order_item_id' => $item->purchase_order_item_id,
                'product_id' => $item->product_id,
                'quantity' => (float) $item->quantity,
                'unit_cost' => (float) $item->unit_cost,
                'lot_no' => $item->lot_no,
            ]),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
