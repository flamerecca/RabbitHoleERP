<?php

namespace App\Actions\Purchasing;

use App\Actions\Inventory\LotQuantities;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockLot;
use App\Models\Team;
use App\Services\DocumentSequence;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePurchaseReturn
{
    public function __construct(protected DocumentSequence $documentSequence) {}

    /**
     * Create a draft purchase return for products of a confirmed goods receipt.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(Team $team, array $attributes): PurchaseReturn
    {
        return DB::transaction(function () use ($team, $attributes) {
            $goodsReceipt = GoodsReceipt::query()->with('purchaseOrder')->whereKey($attributes['goods_receipt_id'])->firstOrFail();

            if ($goodsReceipt->status !== DocumentStatus::Confirmed) {
                throw ValidationException::withMessages(['goods_receipt_id' => __('Only confirmed goods receipts can be returned.')]);
            }

            $document = PurchaseReturn::create([
                'team_id' => $team->id,
                'supplier_id' => $goodsReceipt->purchaseOrder->supplier_id,
                'goods_receipt_id' => $goodsReceipt->id,
                'warehouse_id' => $attributes['warehouse_id'],
                'return_no' => $this->documentSequence->next($team, DocumentType::PurchaseReturn, CarbonImmutable::parse($attributes['return_date'])),
                'return_date' => $attributes['return_date'],
                'status' => DocumentStatus::Draft,
                'reason' => $attributes['reason'],
            ]);

            static::replaceLines($document, $attributes['items']);

            return $document->fresh('items');
        });
    }

    /**
     * Replace the lines of the purchase return, each for a product of its goods receipt, and recalculate the total.
     *
     * Lines of products tracked by lot name a lot the goods receipt put into stock; a whole-lot line returns
     * all of that lot's stock in the return warehouse.
     *
     * @param  list<array<string, mixed>>  $lines
     *
     * @throws ValidationException
     */
    public static function replaceLines(PurchaseReturn $document, array $lines): void
    {
        $products = Product::query()->whereIn('id', $document->goodsReceipt->items()->select('product_id'))->get()->keyBy('id');
        $document->items()->get()->each->delete();

        foreach ($lines as $index => $line) {
            $product = $products->get((int) $line['product_id']);

            if ($product === null) {
                throw ValidationException::withMessages(["items.{$index}.product_id" => __('The product must belong to the source document.')]);
            }

            $stockLotId = isset($line['stock_lot_id']) ? (int) $line['stock_lot_id'] : null;
            $isWholeLot = (bool) ($line['is_whole_lot'] ?? false);

            static::ensureValidLot($index, $product, $stockLotId, $isWholeLot, fn (int $lotId) => LotQuantities::wasReceivedBy($document->goodsReceipt, $lotId));

            $quantity = $isWholeLot && $stockLotId !== null
                ? static::wholeLotQuantity($index, $document->warehouse_id, $stockLotId)
                : static::requiredQuantity($index, $line);

            $document->items()->create([
                'product_id' => $product->id,
                'stock_lot_id' => $stockLotId,
                'is_whole_lot' => $isWholeLot,
                'quantity' => $quantity,
                'unit_cost' => $line['unit_cost'],
            ]);
        }

        static::recalculateTotal($document);
    }

    /**
     * Resize every whole-lot line to the lot's current stock in the return warehouse and recalculate the total.
     *
     * @throws ValidationException
     */
    public static function refreshWholeLots(PurchaseReturn $document): void
    {
        foreach ($document->items()->orderBy('id')->get()->values() as $index => $item) {
            if ($item->is_whole_lot && $item->stock_lot_id !== null) {
                $item->update(['quantity' => static::wholeLotQuantity($index, $document->warehouse_id, $item->stock_lot_id)]);
            }
        }

        static::recalculateTotal($document);
    }

    /**
     * Fail validation unless a lot of the line's product is given exactly for products tracked by lot, the source
     * document handled it, and only lot lines return a whole lot.
     *
     * @param  Closure(int): bool  $belongsToSource
     *
     * @throws ValidationException
     */
    public static function ensureValidLot(int $index, Product $product, ?int $stockLotId, bool $isWholeLot, Closure $belongsToSource): void
    {
        if ($product->isLotTracked() && $stockLotId === null) {
            throw ValidationException::withMessages(["items.{$index}.stock_lot_id" => __('A lot is required for products tracked by lot.')]);
        }

        if (! $product->isLotTracked() && ($stockLotId !== null || $isWholeLot)) {
            throw ValidationException::withMessages(["items.{$index}.stock_lot_id" => __('Only products tracked by lot take a lot.')]);
        }

        if ($stockLotId !== null && ! StockLot::query()->whereKey($stockLotId)->where('product_id', $product->id)->exists()) {
            throw ValidationException::withMessages(["items.{$index}.stock_lot_id" => __('The lot must belong to the product of the line.')]);
        }

        if ($stockLotId !== null && ! $belongsToSource($stockLotId)) {
            throw ValidationException::withMessages(["items.{$index}.stock_lot_id" => __('The lot must come from the source document.')]);
        }
    }

    /**
     * Get the quantity of a line that does not return a whole lot.
     *
     * @param  array<string, mixed>  $line
     *
     * @throws ValidationException
     */
    public static function requiredQuantity(int $index, array $line): float
    {
        if (! isset($line['quantity']) || (float) $line['quantity'] <= 0) {
            throw ValidationException::withMessages(["items.{$index}.quantity" => __('The quantity is required unless the whole lot is returned.')]);
        }

        return (float) $line['quantity'];
    }

    /**
     * Get the stock of the lot in the warehouse, which must not be empty.
     *
     * @throws ValidationException
     */
    protected static function wholeLotQuantity(int $index, int $warehouseId, int $stockLotId): float
    {
        $quantity = LotQuantities::onHand($warehouseId, $stockLotId);

        if ($quantity <= 0) {
            throw ValidationException::withMessages(["items.{$index}.stock_lot_id" => __('The lot has nothing left to return.')]);
        }

        return $quantity;
    }

    /**
     * Recalculate the total of the purchase return from its lines.
     */
    protected static function recalculateTotal(PurchaseReturn $document): void
    {
        $total = $document->items()->get()->sum(fn (PurchaseReturnItem $item) => round((float) $item->quantity * (float) $item->unit_cost, 4));

        $document->update(['total_amount' => round((float) $total, 4)]);
    }
}
