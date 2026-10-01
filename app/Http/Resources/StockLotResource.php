<?php

namespace App\Http\Resources;

use App\Models\StockLot;
use App\Models\StockLotBalance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockLot
 */
class StockLotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $balances = $this->balances->filter(fn (StockLotBalance $balance) => (float) $balance->quantity_on_hand != 0)->sortBy('warehouse_id');

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'lot_no' => $this->lot_no,
            'quantity_on_hand' => round((float) $balances->sum('quantity_on_hand'), 4),
            'warehouses' => $balances->map(fn (StockLotBalance $balance) => [
                'warehouse_id' => $balance->warehouse_id,
                'quantity_on_hand' => (float) $balance->quantity_on_hand,
            ])->values(),
        ];
    }
}
