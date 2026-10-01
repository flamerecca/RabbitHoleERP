<?php

namespace App\Actions\Sales;

use App\Actions\Inventory\LotQuantities;
use App\Actions\Purchasing\CreatePurchaseReturn;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\SalesReturn;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Team;
use App\Services\DocumentSequence;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSalesReturn
{
    public function __construct(protected DocumentSequence $documentSequence) {}

    /**
     * Create a draft sales return for products of a confirmed shipment.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(Team $team, array $attributes): SalesReturn
    {
        return DB::transaction(function () use ($team, $attributes) {
            $shipment = Shipment::query()->with('salesOrder')->whereKey($attributes['shipment_id'])->firstOrFail();

            if ($shipment->status !== DocumentStatus::Confirmed) {
                throw ValidationException::withMessages(['shipment_id' => __('Only confirmed shipments can be returned.')]);
            }

            $document = SalesReturn::create([
                'team_id' => $team->id,
                'customer_id' => $shipment->salesOrder->customer_id,
                'shipment_id' => $shipment->id,
                'warehouse_id' => $attributes['warehouse_id'],
                'return_no' => $this->documentSequence->next($team, DocumentType::SalesReturn, CarbonImmutable::parse($attributes['return_date'])),
                'return_date' => $attributes['return_date'],
                'status' => DocumentStatus::Draft,
                'reason' => $attributes['reason'],
            ]);

            static::replaceLines($document, $attributes['items']);

            return $document->fresh('items');
        });
    }

    /**
     * Replace the lines of the sales return, each for a product of its shipment, and recalculate the refund total.
     *
     * The refund price of a product is the sales order price of its first shipment line, per stock unit. Lines of
     * products tracked by lot name a lot the shipment sent out; a whole-lot line takes back all of that lot.
     *
     * @param  list<array<string, mixed>>  $lines
     *
     * @throws ValidationException
     */
    public static function replaceLines(SalesReturn $document, array $lines): void
    {
        $shipment = $document->shipment;
        $shipmentLines = static::shipmentLinesByProduct($document);
        $document->items()->get()->each->delete();

        foreach ($lines as $index => $line) {
            /** @var ShipmentItem|null $shipmentLine */
            $shipmentLine = $shipmentLines->get((int) $line['product_id']);

            if ($shipmentLine === null) {
                throw ValidationException::withMessages(["items.{$index}.product_id" => __('The product must belong to the source document.')]);
            }

            $stockLotId = isset($line['stock_lot_id']) ? (int) $line['stock_lot_id'] : null;
            $isWholeLot = (bool) ($line['is_whole_lot'] ?? false);

            CreatePurchaseReturn::ensureValidLot($index, $shipmentLine->product, $stockLotId, $isWholeLot, fn (int $lotId) => LotQuantities::shippedBy($shipment, $lotId) > 0);

            $document->items()->create([
                'product_id' => $shipmentLine->product_id,
                'stock_lot_id' => $stockLotId,
                'is_whole_lot' => $isWholeLot,
                'quantity' => $isWholeLot && $stockLotId !== null
                    ? static::wholeLotQuantity($index, $shipment, $stockLotId)
                    : CreatePurchaseReturn::requiredQuantity($index, $line),
                'disposition' => $line['disposition'],
            ]);
        }

        static::recalculateTotal($document, $shipmentLines);
    }

    /**
     * Resize every whole-lot line to what the shipment sent of the lot and has not been returned yet, then recalculate the total.
     *
     * @throws ValidationException
     */
    public static function refreshWholeLots(SalesReturn $document): void
    {
        foreach ($document->items()->orderBy('id')->get()->values() as $index => $item) {
            if ($item->is_whole_lot && $item->stock_lot_id !== null) {
                $item->update(['quantity' => static::wholeLotQuantity($index, $document->shipment, $item->stock_lot_id)]);
            }
        }

        static::recalculateTotal($document, static::shipmentLinesByProduct($document));
    }

    /**
     * Get the first shipment line of each product, which sets its refund price.
     *
     * @return Collection<int, ShipmentItem>
     */
    protected static function shipmentLinesByProduct(SalesReturn $document): Collection
    {
        return $document->shipment->items()->with(['salesOrderItem.unit', 'product.unit'])->orderBy('id')->get()->unique('product_id')->keyBy('product_id');
    }

    /**
     * Get what the shipment sent of the lot minus what confirmed returns took back, which must not be empty.
     *
     * @throws ValidationException
     */
    protected static function wholeLotQuantity(int $index, Shipment $shipment, int $stockLotId): float
    {
        $quantity = round(LotQuantities::shippedBy($shipment, $stockLotId) - LotQuantities::returnedFrom($shipment, $stockLotId), 4);

        if ($quantity <= 0) {
            throw ValidationException::withMessages(["items.{$index}.stock_lot_id" => __('The lot has nothing left to return.')]);
        }

        return $quantity;
    }

    /**
     * Recalculate the refund total of the sales return from its lines.
     *
     * @param  Collection<int, ShipmentItem>  $shipmentLines
     */
    protected static function recalculateTotal(SalesReturn $document, Collection $shipmentLines): void
    {
        $total = 0.0;

        foreach ($document->items()->get() as $item) {
            $shipmentLine = $shipmentLines->get($item->product_id);

            if ($shipmentLine !== null) {
                $orderItem = $shipmentLine->salesOrderItem;
                $total += round((float) $item->quantity * $orderItem->unit->convertPrice($orderItem->unit_price, $shipmentLine->product->unit), 4);
            }
        }

        $document->update(['total_amount' => round($total, 4)]);
    }
}
