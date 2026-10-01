<?php

namespace App\Http\Resources;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Shipment
 */
class ShipmentResource extends JsonResource
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
            'shipment_no' => $this->shipment_no,
            'sales_order_id' => $this->sales_order_id,
            'warehouse_id' => $this->warehouse_id,
            'shipped_date' => $this->shipped_date->toDateString(),
            'status' => $this->status->value,
            'created_by' => $this->created_by,
            'items' => $this->items->map(fn (ShipmentItem $item) => [
                'id' => $item->id,
                'sales_order_item_id' => $item->sales_order_item_id,
                'product_id' => $item->product_id,
                'quantity' => (float) $item->quantity,
                'stock_lot_id' => $item->stock_lot_id,
                'lots' => $item->stockMovements()->with('stockLot')->whereNotNull('stock_lot_id')->orderBy('id')->get()
                    ->map(fn (StockMovement $movement) => [
                        'stock_lot_id' => $movement->stock_lot_id,
                        'lot_no' => $movement->stockLot?->lot_no,
                        'quantity' => abs((float) $movement->quantity),
                    ])
                    ->values(),
            ]),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
