<?php

namespace App\Filament\Support;

use App\Enums\DocumentStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SalesOrderStatus;
use App\Models\ConsignmentHold;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Shipment;
use App\Models\StockLot;
use App\Models\StockLotBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Models\Team;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Select options limited to the current admin panel tenant.
 */
class TeamOptions
{
    /**
     * Get the current tenant team.
     */
    public static function team(): Team
    {
        /** @var Team $team */
        $team = Filament::getTenant();

        return $team;
    }

    /**
     * @return array<int, string>
     */
    public static function warehouses(): array
    {
        return Warehouse::query()->where('team_id', static::team()->id)->orderBy('code')->get()
            ->mapWithKeys(fn (Warehouse $warehouse) => [$warehouse->id => "{$warehouse->code} {$warehouse->name}"])->all();
    }

    /**
     * @return array<int, string>
     */
    public static function suppliers(): array
    {
        return Supplier::query()->where('team_id', static::team()->id)->orderBy('code')->get()
            ->mapWithKeys(fn (Supplier $supplier) => [$supplier->id => "{$supplier->code} {$supplier->name}"])->all();
    }

    /**
     * @return array<int, string>
     */
    public static function customers(): array
    {
        return Customer::query()->where('team_id', static::team()->id)->orderBy('code')->get()
            ->mapWithKeys(fn (Customer $customer) => [$customer->id => "{$customer->code} {$customer->name}"])->all();
    }

    /**
     * @return array<int, string>
     */
    public static function currencies(): array
    {
        return Currency::query()->where('team_id', static::team()->id)->orderBy('code')->pluck('code', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    public static function taxRates(): array
    {
        return TaxRate::query()->where('team_id', static::team()->id)->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    public static function products(): array
    {
        return Product::query()->where('team_id', static::team()->id)->orderBy('sku')->get()
            ->mapWithKeys(fn (Product $product) => [$product->id => "{$product->sku} {$product->name}"])->all();
    }

    /**
     * Get the units of the unit category of the product's stock unit.
     *
     * @return array<int, string>
     */
    public static function unitsFor(mixed $productId): array
    {
        $product = filled($productId) ? Product::query()->with('unit')->where('team_id', static::team()->id)->whereKey($productId)->first() : null;

        if ($product === null) {
            return [];
        }

        return Unit::query()->where('category_id', $product->unit->category_id)->orderBy('ratio')->pluck('code', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    public static function members(): array
    {
        return static::team()->members()->orderBy('name')->pluck('users.name', 'users.id')->all();
    }

    /**
     * Get the signed in admin user.
     */
    public static function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    /**
     * Get the purchase orders that can still receive goods.
     *
     * @return array<int, string>
     */
    public static function receivablePurchaseOrders(): array
    {
        return PurchaseOrder::query()->with('supplier')->where('team_id', static::team()->id)
            ->whereIn('status', [PurchaseOrderStatus::Confirmed, PurchaseOrderStatus::PartiallyReceived])->orderByDesc('id')->get()
            ->mapWithKeys(fn (PurchaseOrder $order) => [$order->id => "{$order->order_no} {$order->supplier->name}"])->all();
    }

    /**
     * Get the lines of the purchase order with the quantity still to receive.
     *
     * @return array<int, string>
     */
    public static function purchaseOrderLines(mixed $orderId): array
    {
        return PurchaseOrderItem::query()->with(['product', 'unit'])
            ->whereHas('purchaseOrder', fn ($query) => $query->where('team_id', static::team()->id))
            ->where('purchase_order_id', $orderId)->orderBy('id')->get()
            ->mapWithKeys(fn (PurchaseOrderItem $item) => [$item->id => sprintf('%s %s, %s %s', $item->product->sku, $item->product->name, __('Remaining'), round((float) $item->quantity - (float) $item->received_quantity, 4).' '.$item->unit->code)])->all();
    }

    /**
     * Get the sales orders that can still ship goods.
     *
     * @return array<int, string>
     */
    public static function shippableSalesOrders(): array
    {
        return SalesOrder::query()->with('customer')->where('team_id', static::team()->id)
            ->whereIn('status', [SalesOrderStatus::Confirmed, SalesOrderStatus::PartiallyShipped])->orderByDesc('id')->get()
            ->mapWithKeys(fn (SalesOrder $order) => [$order->id => "{$order->order_no} {$order->customer->name}"])->all();
    }

    /**
     * Get the lines of the sales order with the quantity still to ship.
     *
     * @return array<int, string>
     */
    public static function salesOrderLines(mixed $orderId): array
    {
        return SalesOrderItem::query()->with(['product', 'unit'])
            ->whereHas('salesOrder', fn ($query) => $query->where('team_id', static::team()->id))
            ->where('sales_order_id', $orderId)->orderBy('id')->get()
            ->mapWithKeys(fn (SalesOrderItem $item) => [$item->id => sprintf('%s %s, %s %s', $item->product->sku, $item->product->name, __('Remaining'), round((float) $item->quantity - (float) $item->shipped_quantity, 4).' '.$item->unit->code)])->all();
    }

    /**
     * @return array<int, string>
     */
    public static function confirmedGoodsReceipts(): array
    {
        return GoodsReceipt::query()->where('team_id', static::team()->id)->where('status', DocumentStatus::Confirmed)->orderByDesc('id')->pluck('receipt_no', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    public static function confirmedShipments(): array
    {
        return Shipment::query()->where('team_id', static::team()->id)->where('status', DocumentStatus::Confirmed)->orderByDesc('id')->pluck('shipment_no', 'id')->all();
    }

    /**
     * Get the confirmed shipments without a consignment hold.
     *
     * @return array<int, string>
     */
    public static function holdableShipments(): array
    {
        return Shipment::query()->where('team_id', static::team()->id)->where('status', DocumentStatus::Confirmed)
            ->whereNotIn('id', ConsignmentHold::query()->select('shipment_id'))->orderByDesc('id')->pluck('shipment_no', 'id')->all();
    }

    /**
     * Get the products received by the goods receipt.
     *
     * @return array<int, string>
     */
    public static function goodsReceiptProducts(mixed $receiptId): array
    {
        return static::productsIn(Product::query()->whereIn('id', GoodsReceipt::query()->where('team_id', static::team()->id)->whereKey($receiptId)->join('goods_receipt_items', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')->select('goods_receipt_items.product_id')));
    }

    /**
     * Get the products shipped by the shipment.
     *
     * @return array<int, string>
     */
    public static function shipmentProducts(mixed $shipmentId): array
    {
        return static::productsIn(Product::query()->whereIn('id', Shipment::query()->where('team_id', static::team()->id)->whereKey($shipmentId)->join('shipment_items', 'shipment_items.shipment_id', '=', 'shipments.id')->select('shipment_items.product_id')));
    }

    /**
     * @param  Builder<Product>  $query
     * @return array<int, string>
     */
    protected static function productsIn(Builder $query): array
    {
        return $query->orderBy('sku')->get()->mapWithKeys(fn (Product $product) => [$product->id => "{$product->sku} {$product->name}"])->all();
    }

    /**
     * Get the translated options of a status enum.
     *
     * @param  class-string<\BackedEnum>  $enum
     * @return array<string, string>
     */
    public static function statuses(string $enum): array
    {
        return collect($enum::cases())->mapWithKeys(fn (\BackedEnum $status) => [(string) $status->value => __("status.{$status->value}")])->all();
    }

    /**
     * Determine if the product of a purchase order line is tracked by lot.
     */
    public static function purchaseOrderLineIsLotTracked(mixed $orderItemId): bool
    {
        return filled($orderItemId) && (bool) PurchaseOrderItem::query()->whereKey($orderItemId)->first()?->product?->isLotTracked();
    }

    /**
     * Determine if the product is tracked by lot.
     */
    public static function isLotTracked(mixed $productId): bool
    {
        return filled($productId) && (bool) Product::query()->where('team_id', static::team()->id)->whereKey($productId)->first()?->isLotTracked();
    }

    /**
     * Get the lots of the product of a sales order line that have stock in the warehouse.
     *
     * @return array<int, string>
     */
    public static function lotsInStockForSalesOrderLine(mixed $orderItemId, mixed $warehouseId): array
    {
        $productId = filled($orderItemId) ? SalesOrderItem::query()->whereKey($orderItemId)->value('product_id') : null;

        return static::lotsWithStock($productId, $warehouseId);
    }

    /**
     * Get the lots of the product with stock in the warehouse, labelled with their quantity.
     *
     * @return array<int, string>
     */
    public static function lotsWithStock(mixed $productId, mixed $warehouseId): array
    {
        if (blank($productId) || blank($warehouseId)) {
            return [];
        }

        return StockLotBalance::query()->with('stockLot')
            ->where('team_id', static::team()->id)->where('product_id', $productId)->where('warehouse_id', $warehouseId)
            ->where('quantity_on_hand', '>', 0)->orderBy('stock_lot_id')->get()
            ->mapWithKeys(fn (StockLotBalance $balance) => [$balance->stock_lot_id => $balance->stockLot->lot_no.', '.__('On hand').' '.(float) $balance->quantity_on_hand])->all();
    }

    /**
     * Get all lots of the product.
     *
     * @return array<int, string>
     */
    public static function lotsOf(mixed $productId): array
    {
        return blank($productId) ? [] : StockLot::query()->where('team_id', static::team()->id)->where('product_id', $productId)->orderBy('id')->pluck('lot_no', 'id')->all();
    }

    /**
     * Get the lots of the product that the movements of the given document lines touched.
     *
     * @param  class-string<Model>  $lineClass
     * @return array<int, string>
     */
    public static function lotsMovedBy(string $lineClass, string $foreignKey, mixed $documentId, mixed $productId): array
    {
        if (blank($documentId) || blank($productId)) {
            return [];
        }

        return StockLot::query()->where('team_id', static::team()->id)->where('product_id', $productId)
            ->whereIn('id', StockMovement::query()->select('stock_lot_id')
                ->where('reference_type', (new $lineClass)->getMorphClass())
                ->whereIn('reference_id', $lineClass::query()->select('id')->where($foreignKey, $documentId)))
            ->orderBy('id')->pluck('lot_no', 'id')->all();
    }
}
