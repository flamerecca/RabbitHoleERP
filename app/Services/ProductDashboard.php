<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Enums\SalesOrderStatus;
use App\Models\GoodsReceiptItem;
use App\Models\MaterialRequisitionItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturnItem;
use App\Models\SalesOrder;
use App\Models\SalesReturnItem;
use App\Models\ShipmentItem;
use App\Models\StockMovement;
use App\Models\StockTakeItem;
use App\Models\Team;
use App\Models\WarehouseTransferItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Read-only figures shown on both the team dashboard and the admin panel dashboard.
 */
class ProductDashboard
{
    /**
     * Get the number of active products, the stock value and the products below their reorder point.
     *
     * @return array{active_products: int, stock_value: float, below_reorder_point: int, currency: string|null}
     */
    public function overview(Team $team): array
    {
        $availableByProduct = DB::table('stock_balances')
            ->where('team_id', $team->id)
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity_on_hand - quantity_reserved) as available')
            ->pluck('available', 'product_id');

        $belowReorderPoint = Product::query()
            ->where('team_id', $team->id)
            ->where('is_active', true)
            ->whereNotNull('reorder_point')
            ->get(['id', 'reorder_point'])
            ->filter(fn (Product $product) => (float) ($availableByProduct[$product->id] ?? 0) < (float) $product->reorder_point)
            ->count();

        $currency = DB::table('currencies')->where('team_id', $team->id)->where('is_base', true)->value('code');

        return [
            'active_products' => Product::query()->where('team_id', $team->id)->where('is_active', true)->count(),
            'stock_value' => round((float) DB::table('stock_balances')->where('team_id', $team->id)->sum(DB::raw('quantity_on_hand * average_cost')), 2),
            'below_reorder_point' => $belowReorderPoint,
            'currency' => is_string($currency) ? $currency : null,
        ];
    }

    /**
     * Get every active reordering rule whose forecasted quantity is below its minimum.
     *
     * The calculation is copied from ProductController::listReplenishment, including truncating
     * converted quantities to four decimals, see docs/architecture.md section 4.7.
     *
     * @return list<array{id: int, product_id: int, sku: string, product: string, warehouse: string, forecasted: float|int, suggested: float, supplier: string|null}>
     */
    public function replenishmentSuggestions(Team $team): array
    {
        $rules = DB::table('reordering_rules')
            ->join('products', 'products.id', '=', 'reordering_rules.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'reordering_rules.warehouse_id')
            ->join('units', 'units.id', '=', 'products.unit_id')
            ->where('reordering_rules.team_id', $team->id)
            ->where('reordering_rules.is_active', true)
            ->where('products.is_active', true)
            ->orderBy('warehouses.code')
            ->orderBy('products.sku')
            ->get(['reordering_rules.id', 'reordering_rules.warehouse_id', 'reordering_rules.product_id', 'reordering_rules.min_quantity', 'reordering_rules.max_quantity', 'reordering_rules.multiple_quantity', 'warehouses.code as warehouse_code', 'products.sku', 'products.name', 'units.ratio as stock_ratio']);

        $productIds = $rules->pluck('product_id')->unique()->values();
        $stockRatios = $rules->pluck('stock_ratio', 'product_id');

        $onHand = [];
        foreach (DB::table('stock_balances')->where('team_id', $team->id)->whereIn('product_id', $productIds)->get() as $balance) {
            $onHand[$balance->warehouse_id.'-'.$balance->product_id] = (float) $balance->quantity_on_hand;
        }

        $incoming = $this->openQuantities('purchase_order_items', 'purchase_orders', 'purchase_order_id', 'received_quantity', [PurchaseOrderStatus::Confirmed->value, PurchaseOrderStatus::PartiallyReceived->value], $team, $productIds->all(), $stockRatios->all());
        $outgoing = $this->openQuantities('sales_order_items', 'sales_orders', 'sales_order_id', 'shipped_quantity', [SalesOrderStatus::Confirmed->value, SalesOrderStatus::PartiallyShipped->value], $team, $productIds->all(), $stockRatios->all());

        $today = now()->toDateString();
        $suppliers = DB::table('product_suppliers')
            ->join('suppliers', 'suppliers.id', '=', 'product_suppliers.supplier_id')
            ->where('product_suppliers.team_id', $team->id)
            ->whereIn('product_suppliers.product_id', $productIds)
            ->where(fn (Builder $q) => $q->whereNull('product_suppliers.valid_from')->orWhereDate('product_suppliers.valid_from', '<=', $today))
            ->where(fn (Builder $q) => $q->whereNull('product_suppliers.valid_until')->orWhereDate('product_suppliers.valid_until', '>=', $today))
            ->orderBy('product_suppliers.sequence')
            ->orderBy('product_suppliers.id')
            ->get(['product_suppliers.product_id', 'suppliers.name'])
            ->unique('product_id')
            ->pluck('name', 'product_id');

        $items = [];
        foreach ($rules as $rule) {
            $key = $rule->warehouse_id.'-'.$rule->product_id;
            $forecasted = ($onHand[$key] ?? 0) + ($incoming[$key] ?? 0) - ($outgoing[$key] ?? 0);

            if ($forecasted >= (float) $rule->min_quantity) {
                continue;
            }

            $items[] = [
                'id' => (int) $rule->id,
                'product_id' => (int) $rule->product_id,
                'sku' => (string) $rule->sku,
                'product' => (string) $rule->name,
                'warehouse' => (string) $rule->warehouse_code,
                'forecasted' => $forecasted,
                'suggested' => ceil(((float) $rule->max_quantity - $forecasted) / (float) $rule->multiple_quantity) * (float) $rule->multiple_quantity,
                'supplier' => isset($suppliers[$rule->product_id]) ? (string) $suppliers[$rule->product_id] : null,
            ];
        }

        return $items;
    }

    /**
     * Get the number of confirmed purchase orders not fully received, and the oldest of them.
     *
     * @return array{count: int, orders: Collection<int, PurchaseOrder>}
     */
    public function openPurchaseOrders(Team $team, int $limit = 5): array
    {
        $query = PurchaseOrder::query()
            ->where('team_id', $team->id)
            ->whereIn('status', [PurchaseOrderStatus::Confirmed, PurchaseOrderStatus::PartiallyReceived]);

        return [
            'count' => (clone $query)->count(),
            'orders' => $query->with('supplier')->orderBy('order_date')->orderBy('id')->limit($limit)->get(),
        ];
    }

    /**
     * Get the number of confirmed sales orders not fully shipped, and the oldest of them.
     *
     * @return array{count: int, orders: Collection<int, SalesOrder>}
     */
    public function openSalesOrders(Team $team, int $limit = 5): array
    {
        $query = SalesOrder::query()
            ->where('team_id', $team->id)
            ->whereIn('status', [SalesOrderStatus::Confirmed, SalesOrderStatus::PartiallyShipped]);

        return [
            'count' => (clone $query)->count(),
            'orders' => $query->with('customer')->orderBy('order_date')->orderBy('id')->limit($limit)->get(),
        ];
    }

    /**
     * Get the latest stock movements of the team.
     *
     * @return Collection<int, StockMovement>
     */
    public function recentMovements(Team $team, int $limit = 10): Collection
    {
        return StockMovement::query()
            ->with(['product', 'warehouse', 'reference'])
            ->where('team_id', $team->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Get the number of the document a movement came from, or its note for manual corrections.
     */
    public function movementSource(StockMovement $movement): ?string
    {
        $reference = $movement->reference;

        $number = match (true) {
            $reference instanceof GoodsReceiptItem => $reference->goodsReceipt->receipt_no,
            $reference instanceof ShipmentItem => $reference->shipment->shipment_no,
            $reference instanceof PurchaseReturnItem => $reference->purchaseReturn->return_no,
            $reference instanceof SalesReturnItem => $reference->salesReturn->return_no,
            $reference instanceof WarehouseTransferItem => $reference->warehouseTransfer->transfer_no,
            $reference instanceof StockTakeItem => $reference->stockTake->take_no,
            $reference instanceof MaterialRequisitionItem => $reference->materialRequisition->requisition_no,
            $reference instanceof Model => $reference->getAttribute('receipt_no')
                ?? $reference->getAttribute('shipment_no')
                ?? $reference->getAttribute('return_no')
                ?? $reference->getAttribute('transfer_no')
                ?? $reference->getAttribute('take_no')
                ?? $reference->getAttribute('requisition_no'),
            default => $movement->note,
        };

        return is_string($number) ? $number : null;
    }

    /**
     * Sum the quantities still open on order lines per warehouse and product, truncated to four decimals in the stock unit.
     *
     * @param  list<string>  $statuses
     * @param  array<int, mixed>  $productIds
     * @param  array<int|string, mixed>  $stockRatios
     * @return array<string, float>
     */
    protected function openQuantities(string $itemTable, string $orderTable, string $foreignKey, string $doneColumn, array $statuses, Team $team, array $productIds, array $stockRatios): array
    {
        $lines = DB::table($itemTable)
            ->join($orderTable, "{$orderTable}.id", '=', "{$itemTable}.{$foreignKey}")
            ->join('units', 'units.id', '=', "{$itemTable}.unit_id")
            ->where("{$orderTable}.team_id", $team->id)
            ->whereIn("{$itemTable}.product_id", $productIds)
            ->whereIn("{$orderTable}.status", $statuses)
            ->get(["{$orderTable}.warehouse_id", "{$itemTable}.product_id", "{$itemTable}.quantity", "{$itemTable}.{$doneColumn} as done", 'units.ratio']);

        $open = [];
        foreach ($lines as $line) {
            $key = $line->warehouse_id.'-'.$line->product_id;
            $left = max(0.0, (float) $line->quantity - (float) $line->done);
            $open[$key] = ($open[$key] ?? 0.0) + floor($left * (float) $line->ratio / (float) $stockRatios[$line->product_id] * 10000) / 10000;
        }

        return $open;
    }
}
