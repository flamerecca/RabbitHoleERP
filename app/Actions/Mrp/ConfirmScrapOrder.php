<?php

namespace App\Actions\Mrp;

use App\Actions\Inventory\AllocateStockLots;
use App\Actions\Inventory\EnsureStockAvailable;
use App\Actions\Inventory\RecordStockMovement;
use App\Enums\DocumentStatus;
use App\Enums\StockMovementType;
use App\Models\ScrapOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConfirmScrapOrder
{
    public function __construct(
        protected RecordStockMovement $recordStockMovement,
        protected EnsureStockAvailable $ensureStockAvailable,
        protected AllocateStockLots $allocateStockLots,
    ) {
    }

    /**
     * Confirm a draft scrap order: take the unreserved stock out as scrap, oldest lots first.
     */
    public function handle(ScrapOrder $order, User $user): ScrapOrder
    {
        return DB::transaction(function () use ($order, $user) {
            $order = ScrapOrder::query()->lockForUpdate()->findOrFail($order->id);

            abort_if($order->status !== DocumentStatus::Draft, 409, __('Only draft scrap orders can be confirmed.'));

            $order->load('items.product');
            $requiredByProduct = $order->items->groupBy('product_id')->map(fn ($lines) => (float) $lines->sum('quantity'))->all();
            $this->ensureStockAvailable->handle($order->warehouse_id, $requiredByProduct, $order->items->pluck('product', 'product_id'));

            foreach ($order->items as $line) {
                foreach ($this->allocateStockLots->handle($order->warehouse_id, $line->product, (float) $line->quantity) as $allocation) {
                    $this->recordStockMovement->handle(
                        reference: $line,
                        teamId: $order->team_id,
                        warehouseId: $order->warehouse_id,
                        product: $line->product,
                        type: StockMovementType::ScrapOut,
                        quantity: -$allocation['quantity'],
                        user: $user,
                        stockLotId: $allocation['stock_lot_id'],
                    );
                }
            }

            $order->update(['status' => DocumentStatus::Confirmed, 'scrapped_at' => now()]);

            return $order->fresh('items');
        });
    }
}
