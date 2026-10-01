<?php

namespace App\Actions\Inventory;

use App\Enums\DocumentStatus;
use App\Enums\StockMovementType;
use App\Models\MaterialRequisition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConfirmMaterialRequisition
{
    public function __construct(
        protected RecordStockMovement $recordStockMovement,
        protected EnsureStockAvailable $ensureStockAvailable,
        protected AllocateStockLots $allocateStockLots,
    ) {}

    /**
     * Confirm a draft material requisition: consume unreserved stock for internal use, oldest lots first.
     */
    public function handle(MaterialRequisition $document, User $user): MaterialRequisition
    {
        return DB::transaction(function () use ($document, $user) {
            $document = MaterialRequisition::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft material requisitions can be confirmed.'));

            $document->load('items.product');
            $requiredByProduct = $document->items->groupBy('product_id')->map(fn ($lines) => (float) $lines->sum('quantity'))->all();
            $this->ensureStockAvailable->handle($document->warehouse_id, $requiredByProduct, $document->items->pluck('product', 'product_id'));

            foreach ($document->items as $line) {
                foreach ($this->allocateStockLots->handle($document->warehouse_id, $line->product, (float) $line->quantity) as $allocation) {
                    $this->recordStockMovement->handle(
                        reference: $line,
                        teamId: $document->team_id,
                        warehouseId: $document->warehouse_id,
                        product: $line->product,
                        type: StockMovementType::RequisitionOut,
                        quantity: -$allocation['quantity'],
                        user: $user,
                        stockLotId: $allocation['stock_lot_id'],
                    );
                }
            }

            $document->update(['status' => DocumentStatus::Confirmed]);

            return $document->fresh('items');
        });
    }
}
