<?php

namespace App\Http\Controllers;

use App\Actions\Products\CreateProduct;
use App\Actions\Products\CreateProductCategory;
use App\Actions\Products\CreateUnit;
use App\Actions\Products\DeleteProduct;
use App\Actions\Products\DeleteProductCategory;
use App\Actions\Products\DeleteUnit;
use App\Actions\Products\UpdateProduct;
use App\Actions\Products\UpdateProductCategory;
use App\Actions\Products\UpdateUnit;
use App\Enums\CostMethod;
use App\Enums\DocumentStatus;
use App\Enums\LotTrackingPolicy;
use App\Enums\ProductTracking;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSupplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ReorderingRule;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\Unit;
use App\Models\UnitCategory;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use stdClass;

class ProductController extends Controller
{
    /**
     * Show the product's stock per warehouse, including incoming and outgoing order quantities.
     */
    public function showStock(Team $team, Product $product): JsonResponse
    {
        $product->load('unit');
        $stockRatio = (float) $product->unit->ratio;

        $balances = DB::table('stock_balances')
            ->where('team_id', $team->id)
            ->where('product_id', $product->id)
            ->get()
            ->keyBy('warehouse_id');

        $incomingLines = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->join('units', 'units.id', '=', 'purchase_order_items.unit_id')
            ->where('purchase_orders.team_id', $team->id)
            ->where('purchase_order_items.product_id', $product->id)
            ->whereIn('purchase_orders.status', [PurchaseOrderStatus::Confirmed->value, PurchaseOrderStatus::PartiallyReceived->value])
            ->get(['purchase_orders.warehouse_id', 'purchase_order_items.quantity', 'purchase_order_items.received_quantity', 'units.ratio']);

        $incoming = collect();
        foreach ($incomingLines as $line) {
            $remaining = max(0.0, (float) $line->quantity - (float) $line->received_quantity);
            $incoming->put($line->warehouse_id, ($incoming->get($line->warehouse_id) ?? 0.0) + round($remaining * (float) $line->ratio / $stockRatio, 4));
        }

        $outgoingLines = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->join('units', 'units.id', '=', 'sales_order_items.unit_id')
            ->where('sales_orders.team_id', $team->id)
            ->where('sales_order_items.product_id', $product->id)
            ->whereIn('sales_orders.status', [SalesOrderStatus::Confirmed->value, SalesOrderStatus::PartiallyShipped->value])
            ->get(['sales_orders.warehouse_id', 'sales_order_items.quantity', 'sales_order_items.shipped_quantity', 'units.ratio']);

        $outgoing = collect();
        foreach ($outgoingLines as $line) {
            $remaining = max(0.0, (float) $line->quantity - (float) $line->shipped_quantity);
            $outgoing->put($line->warehouse_id, ($outgoing->get($line->warehouse_id) ?? 0.0) + round($remaining * (float) $line->ratio / $stockRatio, 4));
        }

        $warehouseIds = $balances->keys()
            ->merge($incoming->keys())
            ->merge($outgoing->keys())
            ->unique()
            ->values();

        $warehouses = DB::table('warehouses')
            ->where('team_id', $team->id)
            ->whereIn('id', $warehouseIds)
            ->orderBy('code')
            ->get();

        $rows = [];
        $totals = [
            'quantity_on_hand' => 0.0,
            'quantity_reserved' => 0.0,
            'quantity_available' => 0.0,
            'quantity_incoming' => 0.0,
            'quantity_outgoing' => 0.0,
        ];

        foreach ($warehouses as $warehouse) {
            $balance = $balances->get($warehouse->id);
            $onHand = $balance ? (float) $balance->quantity_on_hand : 0.0;
            $reserved = $balance ? (float) $balance->quantity_reserved : 0.0;
            $incomingQuantity = (float) ($incoming->get($warehouse->id) ?? 0);
            $outgoingQuantity = (float) ($outgoing->get($warehouse->id) ?? 0);

            $rows[] = [
                'warehouse' => ['id' => $warehouse->id, 'code' => $warehouse->code, 'name' => $warehouse->name],
                'quantity_on_hand' => $onHand,
                'quantity_reserved' => $reserved,
                'quantity_available' => $onHand - $reserved,
                'average_cost' => $balance ? (float) $balance->average_cost : null,
                'quantity_incoming' => $incomingQuantity,
                'quantity_outgoing' => $outgoingQuantity,
            ];

            $totals['quantity_on_hand'] += $onHand;
            $totals['quantity_reserved'] += $reserved;
            $totals['quantity_available'] += $onHand - $reserved;
            $totals['quantity_incoming'] += $incomingQuantity;
            $totals['quantity_outgoing'] += $outgoingQuantity;
        }

        return response()->json([
            'data' => [
                'product' => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'unit' => ['code' => $product->unit->code],
                    'reorder_point' => $product->reorder_point === null ? null : (float) $product->reorder_point,
                    'is_active' => $product->is_active,
                ],
                'warehouses' => $rows,
                'totals' => $totals,
                'is_below_reorder_point' => $product->reorder_point !== null
                    && $totals['quantity_available'] < (float) $product->reorder_point,
            ],
        ]);
    }

    /**
     * List the confirmed goods receipt lines of the product.
     */
    public function listGoodsReceipts(Request $request, Team $team, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'warehouse_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $paginator = DB::table('goods_receipt_items')
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_items.goods_receipt_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'goods_receipts.purchase_order_id')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->join('warehouses', 'warehouses.id', '=', 'goods_receipts.warehouse_id')
            ->join('currencies', 'currencies.id', '=', 'purchase_orders.currency_id')
            ->join('purchase_order_items', 'purchase_order_items.id', '=', 'goods_receipt_items.purchase_order_item_id')
            ->join('units', 'units.id', '=', 'purchase_order_items.unit_id')
            ->where('goods_receipts.team_id', $team->id)
            ->where('goods_receipt_items.product_id', $product->id)
            ->where('goods_receipts.status', DocumentStatus::Confirmed->value)
            ->when($validated['warehouse_id'] ?? null, fn (Builder $query, int $id) => $query->where('goods_receipts.warehouse_id', $id))
            ->when($validated['supplier_id'] ?? null, fn (Builder $query, int $id) => $query->where('purchase_orders.supplier_id', $id))
            ->when($validated['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('goods_receipts.received_date', '>=', $date))
            ->when($validated['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('goods_receipts.received_date', '<=', $date))
            ->orderByDesc('goods_receipts.received_date')
            ->orderByDesc('goods_receipt_items.id')
            ->select([
                'goods_receipt_items.id as item_id',
                'goods_receipt_items.quantity',
                'goods_receipt_items.unit_cost',
                'goods_receipts.id as goods_receipt_id',
                'goods_receipts.receipt_no',
                'goods_receipts.received_date',
                'purchase_orders.id as purchase_order_id',
                'purchase_orders.order_no',
                'purchase_orders.exchange_rate',
                'suppliers.id as supplier_id',
                'suppliers.code as supplier_code',
                'suppliers.name as supplier_name',
                'warehouses.id as warehouse_id',
                'warehouses.code as warehouse_code',
                'warehouses.name as warehouse_name',
                'currencies.code as currency_code',
                'units.id as unit_id',
                'units.code as unit_code',
            ])
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        $data = $this->rows($paginator)->map(fn (stdClass $row) => [
            'goods_receipt_item_id' => $row->item_id,
            'goods_receipt' => [
                'id' => $row->goods_receipt_id,
                'receipt_no' => $row->receipt_no,
                'received_date' => CarbonImmutable::parse($row->received_date)->toDateString(),
            ],
            'purchase_order' => ['id' => $row->purchase_order_id, 'order_no' => $row->order_no],
            'supplier' => ['id' => $row->supplier_id, 'code' => $row->supplier_code, 'name' => $row->supplier_name],
            'warehouse' => ['id' => $row->warehouse_id, 'code' => $row->warehouse_code, 'name' => $row->warehouse_name],
            'quantity' => (float) $row->quantity,
            'unit' => ['id' => $row->unit_id, 'code' => $row->unit_code],
            'unit_cost' => (float) $row->unit_cost,
            'currency_code' => $row->currency_code,
            'exchange_rate' => (float) $row->exchange_rate,
            'unit_cost_base' => round((float) $row->unit_cost * (float) $row->exchange_rate, 4),
        ]);

        return $this->paginatedResponse($paginator, $data);
    }

    /**
     * List the confirmed shipment lines of the product.
     */
    public function listShipments(Request $request, Team $team, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'warehouse_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $paginator = DB::table('shipment_items')
            ->join('shipments', 'shipments.id', '=', 'shipment_items.shipment_id')
            ->join('sales_order_items', 'sales_order_items.id', '=', 'shipment_items.sales_order_item_id')
            ->join('units', 'units.id', '=', 'sales_order_items.unit_id')
            ->join('sales_orders', 'sales_orders.id', '=', 'shipments.sales_order_id')
            ->join('customers', 'customers.id', '=', 'sales_orders.customer_id')
            ->join('warehouses', 'warehouses.id', '=', 'shipments.warehouse_id')
            ->join('currencies', 'currencies.id', '=', 'sales_orders.currency_id')
            ->leftJoin('consignment_holds', 'consignment_holds.shipment_id', '=', 'shipments.id')
            ->where('shipments.team_id', $team->id)
            ->where('shipment_items.product_id', $product->id)
            ->where('shipments.status', DocumentStatus::Confirmed->value)
            ->when($validated['warehouse_id'] ?? null, fn (Builder $query, int $id) => $query->where('shipments.warehouse_id', $id))
            ->when($validated['customer_id'] ?? null, fn (Builder $query, int $id) => $query->where('sales_orders.customer_id', $id))
            ->when($validated['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('shipments.shipped_date', '>=', $date))
            ->when($validated['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('shipments.shipped_date', '<=', $date))
            ->orderByDesc('shipments.shipped_date')
            ->orderByDesc('shipment_items.id')
            ->select([
                'shipment_items.id as item_id',
                'shipment_items.quantity',
                'sales_order_items.unit_price',
                'shipments.id as shipment_id',
                'shipments.shipment_no',
                'shipments.shipped_date',
                'sales_orders.id as sales_order_id',
                'sales_orders.order_no',
                'sales_orders.exchange_rate',
                'customers.id as customer_id',
                'customers.code as customer_code',
                'customers.name as customer_name',
                'warehouses.id as warehouse_id',
                'warehouses.code as warehouse_code',
                'warehouses.name as warehouse_name',
                'currencies.code as currency_code',
                'consignment_holds.id as hold_id',
                'consignment_holds.hold_no',
                'consignment_holds.status as hold_status',
                'units.id as unit_id',
                'units.code as unit_code',
            ])
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        $data = $this->rows($paginator)->map(fn (stdClass $row) => [
            'shipment_item_id' => $row->item_id,
            'shipment' => [
                'id' => $row->shipment_id,
                'shipment_no' => $row->shipment_no,
                'shipped_date' => CarbonImmutable::parse($row->shipped_date)->toDateString(),
            ],
            'sales_order' => ['id' => $row->sales_order_id, 'order_no' => $row->order_no],
            'customer' => ['id' => $row->customer_id, 'code' => $row->customer_code, 'name' => $row->customer_name],
            'warehouse' => ['id' => $row->warehouse_id, 'code' => $row->warehouse_code, 'name' => $row->warehouse_name],
            'quantity' => (float) $row->quantity,
            'unit' => ['id' => $row->unit_id, 'code' => $row->unit_code],
            'unit_price' => (float) $row->unit_price,
            'currency_code' => $row->currency_code,
            'exchange_rate' => (float) $row->exchange_rate,
            'unit_price_base' => round((float) $row->unit_price * (float) $row->exchange_rate, 4),
            'consignment_hold' => $row->hold_id === null ? null : [
                'id' => $row->hold_id,
                'hold_no' => $row->hold_no,
                'status' => $row->hold_status,
            ],
        ]);

        return $this->paginatedResponse($paginator, $data);
    }

    /**
     * List the stock movements of the product, resolving each source document number.
     */
    public function listStockMovements(Request $request, Team $team, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'warehouse_id' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::enum(StockMovementType::class)],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $paginator = DB::table('stock_movements')
            ->join('warehouses', 'warehouses.id', '=', 'stock_movements.warehouse_id')
            ->join('users', 'users.id', '=', 'stock_movements.created_by')
            ->where('stock_movements.team_id', $team->id)
            ->where('stock_movements.product_id', $product->id)
            ->when($validated['warehouse_id'] ?? null, fn (Builder $query, int $id) => $query->where('stock_movements.warehouse_id', $id))
            ->when($validated['type'] ?? null, fn (Builder $query, string $type) => $query->where('stock_movements.type', $type))
            ->when($validated['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('stock_movements.created_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('stock_movements.created_at', '<=', $date))
            ->orderByDesc('stock_movements.created_at')
            ->orderByDesc('stock_movements.id')
            ->select([
                'stock_movements.id',
                'stock_movements.created_at',
                'stock_movements.type',
                'stock_movements.quantity',
                'stock_movements.balance_after',
                'stock_movements.reference_type',
                'stock_movements.reference_id',
                'stock_movements.note',
                'warehouses.id as warehouse_id',
                'warehouses.code as warehouse_code',
                'warehouses.name as warehouse_name',
                'users.id as user_id',
                'users.name as user_name',
            ])
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        // reference_type may point at a document header or one of its line items, see docs/architecture.md section 5.
        $headerDocuments = [
            'goods_receipt' => ['goods_receipts', 'receipt_no'],
            'shipment' => ['shipments', 'shipment_no'],
            'purchase_return' => ['purchase_returns', 'return_no'],
            'sales_return' => ['sales_returns', 'return_no'],
            'warehouse_transfer' => ['warehouse_transfers', 'transfer_no'],
            'stock_take' => ['stock_takes', 'take_no'],
            'material_requisition' => ['material_requisitions', 'requisition_no'],
        ];
        $itemDocuments = [
            'goods_receipt_item' => ['goods_receipt_items', 'goods_receipt_id', 'goods_receipt'],
            'shipment_item' => ['shipment_items', 'shipment_id', 'shipment'],
            'purchase_return_item' => ['purchase_return_items', 'purchase_return_id', 'purchase_return'],
            'sales_return_item' => ['sales_return_items', 'sales_return_id', 'sales_return'],
            'warehouse_transfer_item' => ['warehouse_transfer_items', 'warehouse_transfer_id', 'warehouse_transfer'],
            'stock_take_item' => ['stock_take_items', 'stock_take_id', 'stock_take'],
            'material_requisition_item' => ['material_requisition_items', 'material_requisition_id', 'material_requisition'],
        ];

        /** @var array<string, array<int, array{type: string, id: int, number: string}>> $documents */
        $documents = [];

        $rows = $this->rows($paginator);

        foreach ($rows->whereNotNull('reference_type')->groupBy('reference_type') as $referenceType => $movements) {
            $referenceIds = $movements->pluck('reference_id')->unique()->values();

            if (isset($headerDocuments[$referenceType])) {
                [$table, $numberColumn] = $headerDocuments[$referenceType];

                foreach (DB::table($table)->where('team_id', $team->id)->whereIn('id', $referenceIds)->get(['id', $numberColumn]) as $header) {
                    $documents[$referenceType][$header->id] = ['type' => $referenceType, 'id' => $header->id, 'number' => $header->{$numberColumn}];
                }
            } elseif (isset($itemDocuments[$referenceType])) {
                [$itemTable, $foreignKey, $headerType] = $itemDocuments[$referenceType];
                [$table, $numberColumn] = $headerDocuments[$headerType];

                $items = DB::table($itemTable)
                    ->join($table, "{$table}.id", '=', "{$itemTable}.{$foreignKey}")
                    ->where("{$table}.team_id", $team->id)
                    ->whereIn("{$itemTable}.id", $referenceIds)
                    ->get(["{$itemTable}.id as item_id", "{$table}.id as header_id", "{$table}.{$numberColumn} as number"]);

                foreach ($items as $item) {
                    $documents[$referenceType][$item->item_id] = ['type' => $headerType, 'id' => $item->header_id, 'number' => $item->number];
                }
            }
        }

        $transferIds = collect($documents)->flatten(1)->where('type', 'warehouse_transfer')->pluck('id')->unique()->values();
        $transfers = DB::table('warehouse_transfers')
            ->where('team_id', $team->id)
            ->whereIn('id', $transferIds)
            ->get()
            ->keyBy('id');
        $counterpartWarehouses = DB::table('warehouses')
            ->where('team_id', $team->id)
            ->whereIn('id', $transfers->pluck('from_warehouse_id')->merge($transfers->pluck('to_warehouse_id'))->unique()->values())
            ->get()
            ->keyBy('id');

        $data = $rows->map(function (stdClass $row) use ($documents, $transfers, $counterpartWarehouses) {
            $type = StockMovementType::from($row->type);
            $document = $row->reference_type === null ? null : ($documents[$row->reference_type][$row->reference_id] ?? null);

            $counterpartWarehouse = null;
            if ($document !== null && $document['type'] === 'warehouse_transfer' && $transfers->has($document['id'])) {
                $transfer = $transfers->get($document['id']);
                $warehouse = $counterpartWarehouses->get($type === StockMovementType::TransferOut ? $transfer->to_warehouse_id : $transfer->from_warehouse_id);
                $counterpartWarehouse = $warehouse ? ['id' => $warehouse->id, 'code' => $warehouse->code, 'name' => $warehouse->name] : null;
            }

            return [
                'id' => $row->id,
                'created_at' => CarbonImmutable::parse($row->created_at)->toJSON(),
                'warehouse' => ['id' => $row->warehouse_id, 'code' => $row->warehouse_code, 'name' => $row->warehouse_name],
                'type' => $type->value,
                'quantity' => (float) $row->quantity,
                'balance_after' => (float) $row->balance_after,
                'virtual_location' => $type->virtualLocation()?->value,
                'counterpart_warehouse' => $counterpartWarehouse,
                'document' => $document === null ? null : ['type' => $document['type'], 'number' => $document['number']],
                'note' => $row->note,
                'created_by' => ['id' => $row->user_id, 'name' => $row->user_name],
            ];
        });

        return $this->paginatedResponse($paginator, $data);
    }

    /**
     * List the confirmed purchase return and sales return lines of the product in one list.
     */
    public function listReturns(Request $request, Team $team, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'return_type' => ['nullable', Rule::in(['purchase', 'sales'])],
            'warehouse_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $purchaseReturns = DB::table('purchase_return_items')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
            ->join('goods_receipts', 'goods_receipts.id', '=', 'purchase_returns.goods_receipt_id')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_returns.supplier_id')
            ->join('warehouses', 'warehouses.id', '=', 'purchase_returns.warehouse_id')
            ->where('purchase_returns.team_id', $team->id)
            ->where('purchase_return_items.product_id', $product->id)
            ->where('purchase_returns.status', DocumentStatus::Confirmed->value)
            ->when($validated['warehouse_id'] ?? null, fn (Builder $query, int $id) => $query->where('purchase_returns.warehouse_id', $id))
            ->when($validated['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('purchase_returns.return_date', '>=', $date))
            ->when($validated['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('purchase_returns.return_date', '<=', $date))
            ->select([
                DB::raw("'purchase' as return_type"),
                'purchase_return_items.id as return_item_id',
                'purchase_returns.id as return_id',
                'purchase_returns.return_no',
                'purchase_returns.return_date',
                'purchase_returns.reason',
                DB::raw("'goods_receipt' as source_type"),
                'goods_receipts.id as source_id',
                'goods_receipts.receipt_no as source_number',
                DB::raw("'supplier' as partner_type"),
                'suppliers.id as partner_id',
                'suppliers.code as partner_code',
                'suppliers.name as partner_name',
                'warehouses.id as warehouse_id',
                'warehouses.code as warehouse_code',
                'warehouses.name as warehouse_name',
                'purchase_return_items.quantity',
                'purchase_return_items.unit_cost',
                DB::raw('NULL as disposition'),
            ]);

        $salesReturns = DB::table('sales_return_items')
            ->join('sales_returns', 'sales_returns.id', '=', 'sales_return_items.sales_return_id')
            ->join('shipments', 'shipments.id', '=', 'sales_returns.shipment_id')
            ->join('customers', 'customers.id', '=', 'sales_returns.customer_id')
            ->join('warehouses', 'warehouses.id', '=', 'sales_returns.warehouse_id')
            ->where('sales_returns.team_id', $team->id)
            ->where('sales_return_items.product_id', $product->id)
            ->where('sales_returns.status', DocumentStatus::Confirmed->value)
            ->when($validated['warehouse_id'] ?? null, fn (Builder $query, int $id) => $query->where('sales_returns.warehouse_id', $id))
            ->when($validated['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('sales_returns.return_date', '>=', $date))
            ->when($validated['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('sales_returns.return_date', '<=', $date))
            ->select([
                DB::raw("'sales' as return_type"),
                'sales_return_items.id as return_item_id',
                'sales_returns.id as return_id',
                'sales_returns.return_no',
                'sales_returns.return_date',
                'sales_returns.reason',
                DB::raw("'shipment' as source_type"),
                'shipments.id as source_id',
                'shipments.shipment_no as source_number',
                DB::raw("'customer' as partner_type"),
                'customers.id as partner_id',
                'customers.code as partner_code',
                'customers.name as partner_name',
                'warehouses.id as warehouse_id',
                'warehouses.code as warehouse_code',
                'warehouses.name as warehouse_name',
                'sales_return_items.quantity',
                DB::raw('NULL as unit_cost'),
                'sales_return_items.disposition',
            ]);

        $returns = match ($validated['return_type'] ?? null) {
            'purchase' => $purchaseReturns,
            'sales' => $salesReturns,
            default => $purchaseReturns->unionAll($salesReturns),
        };

        $paginator = DB::query()
            ->fromSub($returns, 'product_returns')
            ->orderByDesc('return_date')
            ->orderBy('return_type')
            ->orderByDesc('return_item_id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        $data = $this->rows($paginator)->map(fn (stdClass $row) => [
            'return_type' => $row->return_type,
            'return_item_id' => $row->return_item_id,
            'return' => [
                'id' => $row->return_id,
                'return_no' => $row->return_no,
                'return_date' => CarbonImmutable::parse($row->return_date)->toDateString(),
                'reason' => $row->reason,
            ],
            'source_document' => ['type' => $row->source_type, 'id' => $row->source_id, 'number' => $row->source_number],
            'partner' => ['type' => $row->partner_type, 'id' => $row->partner_id, 'code' => $row->partner_code, 'name' => $row->partner_name],
            'warehouse' => ['id' => $row->warehouse_id, 'code' => $row->warehouse_code, 'name' => $row->warehouse_name],
            'quantity' => (float) $row->quantity,
            'unit_cost' => $row->unit_cost === null ? null : (float) $row->unit_cost,
            'disposition' => $row->disposition,
        ]);

        return $this->paginatedResponse($paginator, $data);
    }

    /**
     * List the unit categories of the team.
     */
    public function listUnitCategories(Request $request, Team $team): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = DB::table('unit_categories')
            ->where('team_id', $team->id)
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        $data = $this->rows($paginator)->map(fn (stdClass $row) => [
            'id' => $row->id,
            'name' => $row->name,
            'created_at' => CarbonImmutable::parse($row->created_at)->toJSON(),
            'updated_at' => CarbonImmutable::parse($row->updated_at)->toJSON(),
        ]);

        return $this->paginatedResponse($paginator, $data);
    }

    /**
     * Create a unit category for the team.
     */
    public function storeUnitCategory(Request $request, Team $team): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('unit_categories')->where('team_id', $team->id)],
        ]);

        $unitCategory = UnitCategory::create(['team_id' => $team->id, 'name' => $validated['name']]);

        return response()->json(['data' => [
            'id' => $unitCategory->id,
            'name' => $unitCategory->name,
            'created_at' => $unitCategory->created_at?->toJSON(),
            'updated_at' => $unitCategory->updated_at?->toJSON(),
        ]], 201);
    }

    /**
     * Update the given unit category.
     */
    public function updateUnitCategory(Request $request, Team $team, UnitCategory $unitCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('unit_categories')->where('team_id', $team->id)->ignore($unitCategory->id)],
        ]);

        $unitCategory->update($validated);

        return response()->json(['data' => [
            'id' => $unitCategory->id,
            'name' => $unitCategory->name,
            'created_at' => $unitCategory->created_at?->toJSON(),
            'updated_at' => $unitCategory->updated_at?->toJSON(),
        ]]);
    }

    /**
     * Delete the given unit category when no unit belongs to it.
     */
    public function destroyUnitCategory(Team $team, UnitCategory $unitCategory): Response
    {
        abort_if($unitCategory->units()->exists(), 409, __('The unit category still has units.'));

        $unitCategory->delete();

        return response()->noContent();
    }

    /**
     * List the vendor pricelist entries of the product.
     */
    public function listSuppliers(Request $request, Team $team, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'supplier_id' => ['nullable', 'integer'],
        ]);

        $paginator = ProductSupplier::query()
            ->with(['supplier', 'currency', 'product.purchaseUnit'])
            ->where('team_id', $team->id)
            ->where('product_id', $product->id)
            ->when($validated['supplier_id'] ?? null, fn (EloquentBuilder $query, int $id) => $query->where('supplier_id', $id))
            ->orderBy('sequence')
            ->orderBy('supplier_id')
            ->orderBy('min_quantity')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        $data = collect($paginator->items())->map(fn (ProductSupplier $productSupplier) => $this->productSupplierPayload($productSupplier));

        return $this->paginatedResponse($paginator, $data);
    }

    /**
     * Create a vendor pricelist entry for the product.
     */
    public function storeSupplier(Request $request, Team $team, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('team_id', $team->id)],
            'supplier_product_code' => ['nullable', 'string', 'max:255'],
            'supplier_product_name' => ['nullable', 'string', 'max:255'],
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')->where('team_id', $team->id)],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'min_quantity' => ['sometimes', 'numeric', 'min:0'],
            'lead_time_days' => ['sometimes', 'integer', 'min:0'],
            'valid_from' => ['nullable', 'date_format:Y-m-d'],
            'valid_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:valid_from'],
            'sequence' => ['sometimes', 'integer'],
        ]);

        $productSupplier = ProductSupplier::create([
            'min_quantity' => 0,
            'lead_time_days' => 0,
            'sequence' => 10,
            ...$validated,
            'team_id' => $team->id,
            'product_id' => $product->id,
        ]);

        return response()->json(['data' => $this->productSupplierPayload($productSupplier)], 201);
    }

    /**
     * Update the given vendor pricelist entry, changing only the provided fields.
     */
    public function updateSupplier(Request $request, Team $team, Product $product, ProductSupplier $productSupplier): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['sometimes', 'required', 'integer', Rule::exists('suppliers', 'id')->where('team_id', $team->id)],
            'supplier_product_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'supplier_product_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'currency_id' => ['sometimes', 'required', 'integer', Rule::exists('currencies', 'id')->where('team_id', $team->id)],
            'unit_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'min_quantity' => ['sometimes', 'required', 'numeric', 'min:0'],
            'lead_time_days' => ['sometimes', 'required', 'integer', 'min:0'],
            'valid_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'valid_until' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'sequence' => ['sometimes', 'required', 'integer'],
        ]);

        $validFrom = array_key_exists('valid_from', $validated) ? $validated['valid_from'] : $productSupplier->valid_from?->toDateString();
        $validUntil = array_key_exists('valid_until', $validated) ? $validated['valid_until'] : $productSupplier->valid_until?->toDateString();
        if ($validFrom !== null && $validUntil !== null && $validUntil < $validFrom) {
            throw ValidationException::withMessages(['valid_until' => __('The valid until field must be a date after or equal to valid from.')]);
        }

        $productSupplier->update($validated);

        return response()->json(['data' => $this->productSupplierPayload($productSupplier)]);
    }

    /**
     * Delete the given vendor pricelist entry.
     */
    public function destroySupplier(Team $team, Product $product, ProductSupplier $productSupplier): Response
    {
        $productSupplier->delete();

        return response()->noContent();
    }

    /**
     * Suggest the purchase unit price of the product for a supplier, currency, unit and quantity.
     */
    public function showPurchasePrice(Request $request, Team $team, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('team_id', $team->id)],
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')->where('team_id', $team->id)],
            'exchange_rate' => ['required', 'numeric', 'gt:0'],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('team_id', $team->id)],
            'quantity' => ['nullable', 'numeric', 'gt:0'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $product->load(['unit', 'purchaseUnit']);
        $purchaseUnit = $product->purchaseUnit;
        $unit = isset($validated['unit_id']) ? Unit::query()->whereKey($validated['unit_id'])->firstOrFail() : $purchaseUnit;

        if ($unit->category_id !== $product->unit->category_id) {
            throw ValidationException::withMessages(['unit_id' => __('The unit must belong to the same unit category as the product stock unit.')]);
        }

        $date = $validated['date'] ?? now()->toDateString();
        $quantityInPurchaseUnit = round((float) ($validated['quantity'] ?? 1) * (float) $unit->ratio / (float) $purchaseUnit->ratio, 2);

        $productSupplier = ProductSupplier::query()
            ->where('team_id', $team->id)
            ->where('product_id', $product->id)
            ->where('supplier_id', $validated['supplier_id'])
            ->where('currency_id', $validated['currency_id'])
            ->where(fn (EloquentBuilder $query) => $query->whereNull('valid_from')->orWhereDate('valid_from', '<=', $date))
            ->where(fn (EloquentBuilder $query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $date))
            ->where('min_quantity', '<=', $quantityInPurchaseUnit)
            ->orderByDesc('min_quantity')
            ->orderBy('sequence')
            ->orderBy('id')
            ->first();

        if ($productSupplier !== null) {
            $unitPrice = round((float) $productSupplier->unit_price * (float) $unit->ratio / (float) $purchaseUnit->ratio, 2);
            $source = 'supplier_pricelist';
        } elseif ($product->default_purchase_price !== null) {
            $unitPrice = round((float) $product->default_purchase_price / (float) $validated['exchange_rate'] * (float) $unit->ratio / (float) $purchaseUnit->ratio, 2);
            $source = 'default_price';
        } else {
            $unitPrice = null;
            $source = 'none';
        }

        return response()->json(['data' => [
            'unit_price' => $unitPrice,
            'unit' => ['id' => $unit->id, 'code' => $unit->code],
            'source' => $source,
            'product_supplier_id' => $productSupplier?->id,
            'lead_time_days' => $productSupplier?->lead_time_days,
        ]]);
    }

    /**
     * List the reordering rules of the product with their forecasted quantity.
     */
    public function listReorderingRules(Request $request, Team $team, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = ReorderingRule::query()
            ->with(['warehouse', 'product.unit'])
            ->join('warehouses', 'warehouses.id', '=', 'reordering_rules.warehouse_id')
            ->where('reordering_rules.team_id', $team->id)
            ->where('reordering_rules.product_id', $product->id)
            ->orderBy('warehouses.code')
            ->select('reordering_rules.*')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        $data = collect($paginator->items())->map(fn (ReorderingRule $reorderingRule) => $this->reorderingRulePayload($reorderingRule));

        return $this->paginatedResponse($paginator, $data);
    }

    /**
     * Create a reordering rule for the product in a warehouse.
     */
    public function storeReorderingRule(Request $request, Team $team, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('team_id', $team->id),
                Rule::unique('reordering_rules')->where('product_id', $product->id),
            ],
            'min_quantity' => ['required', 'numeric', 'min:0'],
            'max_quantity' => ['required', 'numeric', 'gte:min_quantity'],
            'multiple_quantity' => ['sometimes', 'numeric', 'gt:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $reorderingRule = ReorderingRule::create([
            'multiple_quantity' => 1,
            'is_active' => true,
            ...$validated,
            'team_id' => $team->id,
            'product_id' => $product->id,
        ]);

        return response()->json(['data' => $this->reorderingRulePayload($reorderingRule)], 201);
    }

    /**
     * Update the given reordering rule, changing only the provided fields.
     */
    public function updateReorderingRule(Request $request, Team $team, Product $product, ReorderingRule $reorderingRule): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('team_id', $team->id),
                Rule::unique('reordering_rules')->where('product_id', $product->id)->ignore($reorderingRule->id),
            ],
            'min_quantity' => ['sometimes', 'required', 'numeric', 'min:0'],
            'max_quantity' => ['sometimes', 'required', 'numeric'],
            'multiple_quantity' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ]);

        $minQuantity = (float) ($validated['min_quantity'] ?? $reorderingRule->min_quantity);
        $maxQuantity = (float) ($validated['max_quantity'] ?? $reorderingRule->max_quantity);
        if ($maxQuantity < $minQuantity) {
            throw ValidationException::withMessages(['max_quantity' => __('The max quantity field must be greater than or equal to min quantity.')]);
        }

        $reorderingRule->update($validated);

        return response()->json(['data' => $this->reorderingRulePayload($reorderingRule)]);
    }

    /**
     * Delete the given reordering rule.
     */
    public function destroyReorderingRule(Team $team, Product $product, ReorderingRule $reorderingRule): Response
    {
        $reorderingRule->delete();

        return response()->noContent();
    }

    /**
     * List the active reordering rules whose forecasted quantity is below the minimum, with the suggested quantity.
     */
    public function listReplenishment(Request $request, Team $team): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'warehouse_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
        ]);

        $rules = DB::table('reordering_rules')
            ->join('products', 'products.id', '=', 'reordering_rules.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'reordering_rules.warehouse_id')
            ->join('units as stock_units', 'stock_units.id', '=', 'products.unit_id')
            ->join('units as purchase_units', 'purchase_units.id', '=', 'products.purchase_unit_id')
            ->where('reordering_rules.team_id', $team->id)
            ->where('reordering_rules.is_active', true)
            ->where('products.is_active', true)
            ->when($validated['warehouse_id'] ?? null, fn (Builder $query, int $id) => $query->where('reordering_rules.warehouse_id', $id))
            ->orderBy('warehouses.code')
            ->orderBy('products.sku')
            ->get([
                'reordering_rules.id',
                'reordering_rules.warehouse_id',
                'reordering_rules.product_id',
                'reordering_rules.min_quantity',
                'reordering_rules.max_quantity',
                'reordering_rules.multiple_quantity',
                'warehouses.code as warehouse_code',
                'warehouses.name as warehouse_name',
                'products.sku',
                'products.name as product_name',
                'stock_units.id as stock_unit_id',
                'stock_units.code as stock_unit_code',
                'stock_units.ratio as stock_ratio',
                'purchase_units.id as purchase_unit_id',
                'purchase_units.code as purchase_unit_code',
                'purchase_units.ratio as purchase_ratio',
            ]);

        $productIds = $rules->pluck('product_id')->unique()->values();
        $stockRatios = $rules->pluck('stock_ratio', 'product_id');

        $onHand = DB::table('stock_balances')
            ->where('team_id', $team->id)
            ->whereIn('product_id', $productIds)
            ->get()
            ->mapWithKeys(fn (stdClass $balance) => ["{$balance->warehouse_id}-{$balance->product_id}" => (float) $balance->quantity_on_hand]);

        $incomingLines = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->join('units', 'units.id', '=', 'purchase_order_items.unit_id')
            ->where('purchase_orders.team_id', $team->id)
            ->whereIn('purchase_order_items.product_id', $productIds)
            ->whereIn('purchase_orders.status', [PurchaseOrderStatus::Confirmed->value, PurchaseOrderStatus::PartiallyReceived->value])
            ->get(['purchase_orders.warehouse_id', 'purchase_order_items.product_id', 'purchase_order_items.quantity', 'purchase_order_items.received_quantity', 'units.ratio']);

        $incoming = [];
        foreach ($incomingLines as $line) {
            $key = "{$line->warehouse_id}-{$line->product_id}";
            $remaining = max(0.0, (float) $line->quantity - (float) $line->received_quantity);
            $incoming[$key] = ($incoming[$key] ?? 0.0) + floor($remaining * (float) $line->ratio / (float) $stockRatios[$line->product_id] * 10000) / 10000;
        }

        $outgoingLines = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->join('units', 'units.id', '=', 'sales_order_items.unit_id')
            ->where('sales_orders.team_id', $team->id)
            ->whereIn('sales_order_items.product_id', $productIds)
            ->whereIn('sales_orders.status', [SalesOrderStatus::Confirmed->value, SalesOrderStatus::PartiallyShipped->value])
            ->get(['sales_orders.warehouse_id', 'sales_order_items.product_id', 'sales_order_items.quantity', 'sales_order_items.shipped_quantity', 'units.ratio']);

        $outgoing = [];
        foreach ($outgoingLines as $line) {
            $key = "{$line->warehouse_id}-{$line->product_id}";
            $remaining = max(0.0, (float) $line->quantity - (float) $line->shipped_quantity);
            $outgoing[$key] = ($outgoing[$key] ?? 0.0) + floor($remaining * (float) $line->ratio / (float) $stockRatios[$line->product_id] * 10000) / 10000;
        }

        $today = now()->toDateString();
        $preferredSuppliers = DB::table('product_suppliers')
            ->join('suppliers', 'suppliers.id', '=', 'product_suppliers.supplier_id')
            ->where('product_suppliers.team_id', $team->id)
            ->whereIn('product_suppliers.product_id', $productIds)
            ->where(fn (Builder $query) => $query->whereNull('product_suppliers.valid_from')->orWhereDate('product_suppliers.valid_from', '<=', $today))
            ->where(fn (Builder $query) => $query->whereNull('product_suppliers.valid_until')->orWhereDate('product_suppliers.valid_until', '>=', $today))
            ->orderBy('product_suppliers.sequence')
            ->orderBy('product_suppliers.id')
            ->get(['product_suppliers.product_id', 'product_suppliers.lead_time_days', 'suppliers.id', 'suppliers.code', 'suppliers.name'])
            ->unique('product_id')
            ->keyBy('product_id');

        $items = collect();
        foreach ($rules as $rule) {
            $key = "{$rule->warehouse_id}-{$rule->product_id}";
            $forecasted = ($onHand[$key] ?? 0.0) + ($incoming[$key] ?? 0.0) - ($outgoing[$key] ?? 0.0);

            if ($forecasted >= (float) $rule->min_quantity) {
                continue;
            }

            $supplier = $preferredSuppliers->get($rule->product_id);
            if (($validated['supplier_id'] ?? null) !== null && $supplier?->id !== (int) $validated['supplier_id']) {
                continue;
            }

            $multiple = (float) $rule->multiple_quantity;
            $suggested = ceil(((float) $rule->max_quantity - $forecasted) / $multiple) * $multiple;
            $suggestedInPurchaseUnit = floor($suggested * (float) $rule->stock_ratio / (float) $rule->purchase_ratio * 10000) / 10000;

            $items->push([
                'reordering_rule_id' => $rule->id,
                'warehouse' => ['id' => $rule->warehouse_id, 'code' => $rule->warehouse_code, 'name' => $rule->warehouse_name],
                'product' => [
                    'id' => $rule->product_id,
                    'sku' => $rule->sku,
                    'name' => $rule->product_name,
                    'stock_unit' => ['id' => $rule->stock_unit_id, 'code' => $rule->stock_unit_code],
                    'purchase_unit' => ['id' => $rule->purchase_unit_id, 'code' => $rule->purchase_unit_code],
                ],
                'min_quantity' => (float) $rule->min_quantity,
                'max_quantity' => (float) $rule->max_quantity,
                'multiple_quantity' => $multiple,
                'forecasted_quantity' => $forecasted,
                'suggested_quantity' => $suggested,
                'suggested_purchase_quantity' => (int) ceil($suggestedInPurchaseUnit),
                'preferred_supplier' => $supplier === null ? null : ['id' => $supplier->id, 'code' => $supplier->code, 'name' => $supplier->name],
                'lead_time_days' => $supplier?->lead_time_days,
                'can_create_purchase_order' => $supplier !== null,
            ]);
        }

        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $paginator = new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return $this->paginatedResponse($paginator, $items->forPage($page, $perPage)->values());
    }

    /**
     * Create draft purchase orders for the given reordering rules, grouped by preferred supplier and warehouse.
     */
    public function storeReplenishmentPurchaseOrders(Request $request, Team $team): JsonResponse
    {
        $validated = $request->validate([
            'reordering_rule_ids' => ['required', 'array', 'min:1'],
            'reordering_rule_ids.*' => ['integer', 'distinct', Rule::exists('reordering_rules', 'id')->where('team_id', $team->id)],
        ]);

        $result = DB::transaction(function () use ($request, $team, $validated) {
            $rules = ReorderingRule::query()
                ->with(['product.unit', 'product.purchaseUnit', 'warehouse'])
                ->where('team_id', $team->id)
                ->whereIn('id', $validated['reordering_rule_ids'])
                ->orderBy('id')
                ->get();

            $today = now()->toDateString();
            $skipped = [];
            $groups = [];

            foreach ($rules as $rule) {
                $product = $rule->product;

                if (! $rule->is_active || ! $product->is_active) {
                    $skipped[] = ['reordering_rule_id' => $rule->id, 'reason' => 'inactive'];

                    continue;
                }

                $stockRatio = (float) $product->unit->ratio;

                $onHand = (float) DB::table('stock_balances')
                    ->where('team_id', $team->id)
                    ->where('warehouse_id', $rule->warehouse_id)
                    ->where('product_id', $product->id)
                    ->value('quantity_on_hand');

                $incoming = 0.0;
                $incomingLines = DB::table('purchase_order_items')
                    ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
                    ->join('units', 'units.id', '=', 'purchase_order_items.unit_id')
                    ->where('purchase_orders.team_id', $team->id)
                    ->where('purchase_orders.warehouse_id', $rule->warehouse_id)
                    ->where('purchase_order_items.product_id', $product->id)
                    ->whereIn('purchase_orders.status', [PurchaseOrderStatus::Confirmed->value, PurchaseOrderStatus::PartiallyReceived->value])
                    ->get(['purchase_order_items.quantity', 'purchase_order_items.received_quantity', 'units.ratio']);
                foreach ($incomingLines as $line) {
                    $remaining = max(0.0, (float) $line->quantity - (float) $line->received_quantity);
                    $incoming += floor($remaining * (float) $line->ratio / $stockRatio * 10000) / 10000;
                }

                $outgoing = 0.0;
                $outgoingLines = DB::table('sales_order_items')
                    ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
                    ->join('units', 'units.id', '=', 'sales_order_items.unit_id')
                    ->where('sales_orders.team_id', $team->id)
                    ->where('sales_orders.warehouse_id', $rule->warehouse_id)
                    ->where('sales_order_items.product_id', $product->id)
                    ->whereIn('sales_orders.status', [SalesOrderStatus::Confirmed->value, SalesOrderStatus::PartiallyShipped->value])
                    ->get(['sales_order_items.quantity', 'sales_order_items.shipped_quantity', 'units.ratio']);
                foreach ($outgoingLines as $line) {
                    $remaining = max(0.0, (float) $line->quantity - (float) $line->shipped_quantity);
                    $outgoing += floor($remaining * (float) $line->ratio / $stockRatio * 10000) / 10000;
                }

                $forecasted = $onHand + $incoming - $outgoing;
                if ($forecasted >= (float) $rule->min_quantity) {
                    $skipped[] = ['reordering_rule_id' => $rule->id, 'reason' => 'not_below_minimum'];

                    continue;
                }

                $preferredSupplier = ProductSupplier::query()
                    ->where('team_id', $team->id)
                    ->where('product_id', $product->id)
                    ->where(fn (EloquentBuilder $query) => $query->whereNull('valid_from')->orWhereDate('valid_from', '<=', $today))
                    ->where(fn (EloquentBuilder $query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $today))
                    ->orderBy('sequence')
                    ->orderBy('id')
                    ->first();
                if ($preferredSupplier === null) {
                    $skipped[] = ['reordering_rule_id' => $rule->id, 'reason' => 'no_supplier'];

                    continue;
                }

                $multiple = (float) $rule->multiple_quantity;
                $suggested = ceil(((float) $rule->max_quantity - $forecasted) / $multiple) * $multiple;
                $suggestedInPurchaseUnit = floor($suggested * $stockRatio / (float) $product->purchaseUnit->ratio * 10000) / 10000;

                $groups["{$preferredSupplier->supplier_id}-{$rule->warehouse_id}"][] = [
                    'supplier_id' => $preferredSupplier->supplier_id,
                    'warehouse' => $rule->warehouse,
                    'product' => $product,
                    'quantity' => (int) ceil($suggestedInPurchaseUnit),
                ];
            }

            $taxRate = DB::table('tax_rates')->where('team_id', $team->id)->where('is_default', true)->first();
            $baseCurrency = DB::table('currencies')->where('team_id', $team->id)->where('is_base', true)->first();
            $created = [];

            foreach ($groups as $lines) {
                $supplier = Supplier::query()->findOrFail($lines[0]['supplier_id']);
                $warehouse = $lines[0]['warehouse'];
                $currency = $supplier->currency_id !== null
                    ? DB::table('currencies')->where('id', $supplier->currency_id)->first()
                    : $baseCurrency;
                if ($currency === null) {
                    throw ValidationException::withMessages(['reordering_rule_ids' => __('The team has no base currency.')]);
                }

                $prefix = 'PO'.now()->format('Ym').'/';
                $lastOrderNo = DB::table('purchase_orders')
                    ->where('team_id', $team->id)
                    ->where('order_no', 'like', $prefix.'%')
                    ->orderByDesc('order_no')
                    ->value('order_no');
                $sequence = $lastOrderNo === null ? 1 : ((int) substr($lastOrderNo, strlen($prefix))) + 1;

                $purchaseOrder = PurchaseOrder::create([
                    'team_id' => $team->id,
                    'supplier_id' => $supplier->id,
                    'warehouse_id' => $warehouse->id,
                    'currency_id' => $currency->id,
                    'exchange_rate' => $currency->exchange_rate_to_base,
                    'order_no' => $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
                    'status' => PurchaseOrderStatus::Draft,
                    'order_date' => $today,
                    'created_by' => $request->user()?->id,
                ]);

                $subtotal = 0.0;
                foreach ($lines as $line) {
                    $product = $line['product'];

                    $productSupplier = ProductSupplier::query()
                        ->where('team_id', $team->id)
                        ->where('product_id', $product->id)
                        ->where('supplier_id', $supplier->id)
                        ->where('currency_id', $currency->id)
                        ->where(fn (EloquentBuilder $query) => $query->whereNull('valid_from')->orWhereDate('valid_from', '<=', $today))
                        ->where(fn (EloquentBuilder $query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $today))
                        ->where('min_quantity', '<=', $line['quantity'])
                        ->orderByDesc('min_quantity')
                        ->orderBy('sequence')
                        ->orderBy('id')
                        ->first();

                    if ($productSupplier !== null) {
                        $unitPrice = round((float) $productSupplier->unit_price, 4);
                    } elseif ($product->default_purchase_price !== null) {
                        $unitPrice = round((float) $product->default_purchase_price / (float) $currency->exchange_rate_to_base, 4);
                    } else {
                        $unitPrice = 0.0;
                    }

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_id' => $product->id,
                        'unit_id' => $product->purchase_unit_id,
                        'quantity' => $line['quantity'],
                        'unit_price' => $unitPrice,
                        'tax_rate_id' => $taxRate?->id,
                        'received_quantity' => 0,
                    ]);

                    $subtotal += $line['quantity'] * $unitPrice;
                }

                $taxAmount = round($subtotal * (float) ($taxRate->rate ?? 0), 4);
                $purchaseOrder->update([
                    'subtotal' => round($subtotal, 4),
                    'tax_amount' => $taxAmount,
                    'total_amount' => round($subtotal + $taxAmount, 4),
                ]);

                $created[] = [
                    'id' => $purchaseOrder->id,
                    'order_no' => $purchaseOrder->order_no,
                    'supplier' => ['id' => $supplier->id, 'code' => $supplier->code, 'name' => $supplier->name],
                    'warehouse' => ['id' => $warehouse->id, 'code' => $warehouse->code, 'name' => $warehouse->name],
                    'items_count' => count($lines),
                ];
            }

            return ['data' => $created, 'skipped' => $skipped];
        });

        return response()->json($result, 201);
    }

    /**
     * Build the response shape of a vendor pricelist entry.
     *
     * @return array<string, mixed>
     */
    private function productSupplierPayload(ProductSupplier $productSupplier): array
    {
        $productSupplier->loadMissing(['supplier', 'currency', 'product.purchaseUnit']);
        $purchaseUnit = $productSupplier->product->purchaseUnit;

        return [
            'id' => $productSupplier->id,
            'supplier' => ['id' => $productSupplier->supplier->id, 'code' => $productSupplier->supplier->code, 'name' => $productSupplier->supplier->name],
            'supplier_product_code' => $productSupplier->supplier_product_code,
            'supplier_product_name' => $productSupplier->supplier_product_name,
            'currency_id' => (int) $productSupplier->currency_id,
            'currency_code' => $productSupplier->currency->code,
            'unit_price' => (float) $productSupplier->unit_price,
            'min_quantity' => (float) $productSupplier->min_quantity,
            'unit' => ['id' => $purchaseUnit->id, 'code' => $purchaseUnit->code],
            'lead_time_days' => $productSupplier->lead_time_days,
            'valid_from' => $productSupplier->valid_from?->toDateString(),
            'valid_until' => $productSupplier->valid_until?->toDateString(),
            'sequence' => $productSupplier->sequence,
            'created_at' => $productSupplier->created_at?->toJSON(),
            'updated_at' => $productSupplier->updated_at?->toJSON(),
        ];
    }

    /**
     * Build the response shape of a reordering rule, computing its forecasted quantity in the stock unit.
     *
     * @return array<string, mixed>
     */
    private function reorderingRulePayload(ReorderingRule $reorderingRule): array
    {
        $reorderingRule->loadMissing(['warehouse', 'product.unit']);
        $stockRatio = (float) $reorderingRule->product->unit->ratio;

        $onHand = (float) DB::table('stock_balances')
            ->where('team_id', $reorderingRule->team_id)
            ->where('warehouse_id', $reorderingRule->warehouse_id)
            ->where('product_id', $reorderingRule->product_id)
            ->value('quantity_on_hand');

        $forecasted = $onHand;
        $incomingLines = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->join('units', 'units.id', '=', 'purchase_order_items.unit_id')
            ->where('purchase_orders.team_id', $reorderingRule->team_id)
            ->where('purchase_orders.warehouse_id', $reorderingRule->warehouse_id)
            ->where('purchase_order_items.product_id', $reorderingRule->product_id)
            ->whereIn('purchase_orders.status', [PurchaseOrderStatus::Confirmed->value, PurchaseOrderStatus::PartiallyReceived->value])
            ->get(['purchase_order_items.quantity', 'purchase_order_items.received_quantity', 'units.ratio']);
        foreach ($incomingLines as $line) {
            $remaining = max(0.0, (float) $line->quantity - (float) $line->received_quantity);
            $forecasted += floor($remaining * (float) $line->ratio / $stockRatio * 10000) / 10000;
        }

        $outgoingLines = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->join('units', 'units.id', '=', 'sales_order_items.unit_id')
            ->where('sales_orders.team_id', $reorderingRule->team_id)
            ->where('sales_orders.warehouse_id', $reorderingRule->warehouse_id)
            ->where('sales_order_items.product_id', $reorderingRule->product_id)
            ->whereIn('sales_orders.status', [SalesOrderStatus::Confirmed->value, SalesOrderStatus::PartiallyShipped->value])
            ->get(['sales_order_items.quantity', 'sales_order_items.shipped_quantity', 'units.ratio']);
        foreach ($outgoingLines as $line) {
            $remaining = max(0.0, (float) $line->quantity - (float) $line->shipped_quantity);
            $forecasted -= floor($remaining * (float) $line->ratio / $stockRatio * 10000) / 10000;
        }

        $warehouse = $reorderingRule->warehouse;

        return [
            'id' => $reorderingRule->id,
            'warehouse' => ['id' => $warehouse->id, 'code' => $warehouse->code, 'name' => $warehouse->name],
            'min_quantity' => (float) $reorderingRule->min_quantity,
            'max_quantity' => (float) $reorderingRule->max_quantity,
            'multiple_quantity' => (float) $reorderingRule->multiple_quantity,
            'is_active' => $reorderingRule->is_active,
            'forecasted_quantity' => $forecasted,
            'created_at' => $reorderingRule->created_at?->toJSON(),
            'updated_at' => $reorderingRule->updated_at?->toJSON(),
        ];
    }

    /**
     * List the units of the team.
     */
    public function listUnits(Request $request, Team $team): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $paginator = Unit::query()
            ->where('team_id', $team->id)
            ->when($validated['q'] ?? null, fn (EloquentBuilder $query, string $q) => $query->where(fn (EloquentBuilder $inner) => $inner->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%")))
            ->orderBy('category_id')
            ->orderBy('ratio')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        $data = collect($paginator->items())->map(fn (Unit $unit) => $this->unitToArray($unit));

        return $this->paginatedResponse($paginator, $data);
    }

    /**
     * Create a unit.
     */
    public function storeUnit(Request $request, Team $team, CreateUnit $createUnit): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('unit_categories', 'id')->where('team_id', $team->id)],
            'code' => ['required', 'string', 'max:255', Rule::unique('units')->where('team_id', $team->id)],
            'name' => ['required', 'string', 'max:255'],
            'ratio' => ['required', 'numeric', 'gt:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return response()->json(['data' => $this->unitToArray($createUnit->handle($team, $validated))], 201);
    }

    /**
     * Show a unit.
     */
    public function showUnit(Team $team, Unit $unit): JsonResponse
    {
        return response()->json(['data' => $this->unitToArray($unit)]);
    }

    /**
     * Update a unit, changing only the provided fields.
     */
    public function updateUnit(Request $request, Team $team, Unit $unit, UpdateUnit $updateUnit): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['sometimes', 'required', 'integer', Rule::exists('unit_categories', 'id')->where('team_id', $team->id)],
            'code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('units')->where('team_id', $team->id)->ignore($unit->id)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'ratio' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ]);

        return response()->json(['data' => $this->unitToArray($updateUnit->handle($unit, $validated))]);
    }

    /**
     * Delete a unit.
     */
    public function destroyUnit(Team $team, Unit $unit, DeleteUnit $deleteUnit): Response
    {
        $deleteUnit->handle($unit);

        return response()->noContent();
    }

    /**
     * List the product categories of the team.
     */
    public function listProductCategories(Request $request, Team $team): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $paginator = ProductCategory::query()
            ->where('team_id', $team->id)
            ->when($validated['parent_id'] ?? null, fn (EloquentBuilder $query, int|string $id) => $query->where('parent_id', $id))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        $data = collect($paginator->items())->map(fn (ProductCategory $category) => $this->productCategoryToArray($category));

        return $this->paginatedResponse($paginator, $data);
    }

    /**
     * Create a product category.
     */
    public function storeProductCategory(Request $request, Team $team, CreateProductCategory $createProductCategory): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')->where('team_id', $team->id)],
            'name' => ['required', 'string', 'max:255'],
            'cost_method' => ['sometimes', Rule::enum(CostMethod::class)],
            'lot_tracking' => ['sometimes', 'nullable', Rule::enum(LotTrackingPolicy::class)],
        ]);

        return response()->json(['data' => $this->productCategoryToArray($createProductCategory->handle($team, $validated))], 201);
    }

    /**
     * Show a product category.
     */
    public function showProductCategory(Team $team, ProductCategory $productCategory): JsonResponse
    {
        return response()->json(['data' => $this->productCategoryToArray($productCategory)]);
    }

    /**
     * Update a product category, changing only the provided fields.
     */
    public function updateProductCategory(Request $request, Team $team, ProductCategory $productCategory, UpdateProductCategory $updateProductCategory): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => ['sometimes', 'nullable', 'integer', Rule::exists('product_categories', 'id')->where('team_id', $team->id)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'cost_method' => ['sometimes', 'required', Rule::enum(CostMethod::class)],
            'lot_tracking' => ['sometimes', 'nullable', Rule::enum(LotTrackingPolicy::class)],
        ]);

        return response()->json(['data' => $this->productCategoryToArray($updateProductCategory->handle($productCategory, $validated))]);
    }

    /**
     * Delete a product category.
     */
    public function destroyProductCategory(Team $team, ProductCategory $productCategory, DeleteProductCategory $deleteProductCategory): Response
    {
        $deleteProductCategory->handle($productCategory);

        return response()->noContent();
    }

    /**
     * List the products of the team.
     */
    public function listProducts(Request $request, Team $team): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'category_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $paginator = Product::query()
            ->where('team_id', $team->id)
            ->when($validated['category_id'] ?? null, fn (EloquentBuilder $query, int|string $id) => $query->where('category_id', $id))
            ->when(array_key_exists('is_active', $validated) && $validated['is_active'] !== null, fn (EloquentBuilder $query) => $query->where('is_active', $request->boolean('is_active')))
            ->when($validated['q'] ?? null, fn (EloquentBuilder $query, string $q) => $query->where(fn (EloquentBuilder $inner) => $inner->where('sku', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")))
            ->orderBy('sku')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        $data = collect($paginator->items())->map(fn (Product $product) => $this->productToArray($product));

        return $this->paginatedResponse($paginator, $data);
    }

    /**
     * Create a product.
     */
    public function storeProduct(Request $request, Team $team, CreateProduct $createProduct): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')->where('team_id', $team->id)],
            'unit_id' => ['required', 'integer', Rule::exists('units', 'id')->where('team_id', $team->id)],
            'purchase_unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('team_id', $team->id)],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products')->where('team_id', $team->id)],
            'name' => ['required', 'string', 'max:255'],
            'default_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'default_sales_price' => ['nullable', 'numeric', 'min:0'],
            'reorder_point' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'tracking' => ['sometimes', Rule::enum(ProductTracking::class)],
        ]);

        return response()->json(['data' => $this->productToArray($createProduct->handle($team, $validated))], 201);
    }

    /**
     * Show a product.
     */
    public function showProduct(Team $team, Product $product): JsonResponse
    {
        return response()->json(['data' => $this->productToArray($product)]);
    }

    /**
     * Update a product, changing only the provided fields.
     */
    public function updateProduct(Request $request, Team $team, Product $product, UpdateProduct $updateProduct): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('product_categories', 'id')->where('team_id', $team->id)],
            'unit_id' => ['sometimes', 'required', 'integer', Rule::exists('units', 'id')->where('team_id', $team->id)],
            'purchase_unit_id' => ['sometimes', 'required', 'integer', Rule::exists('units', 'id')->where('team_id', $team->id)],
            'sku' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('products')->where('team_id', $team->id)->ignore($product->id)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'default_purchase_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'default_sales_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'reorder_point' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'tracking' => ['sometimes', 'required', Rule::enum(ProductTracking::class)],
        ]);

        return response()->json(['data' => $this->productToArray($updateProduct->handle($product, $validated))]);
    }

    /**
     * Delete a product.
     */
    public function destroyProduct(Team $team, Product $product, DeleteProduct $deleteProduct): Response
    {
        $deleteProduct->handle($product);

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function unitToArray(Unit $unit): array
    {
        return [
            'id' => $unit->id,
            'category_id' => $unit->category_id,
            'code' => $unit->code,
            'name' => $unit->name,
            'ratio' => (float) $unit->ratio,
            'is_reference' => $unit->is_reference,
            'is_active' => $unit->is_active,
            'created_at' => $unit->created_at?->toJSON(),
            'updated_at' => $unit->updated_at?->toJSON(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productCategoryToArray(ProductCategory $category): array
    {
        return [
            'id' => $category->id,
            'parent_id' => $category->parent_id,
            'name' => $category->name,
            'cost_method' => $category->cost_method->value,
            'lot_tracking' => $category->lot_tracking?->value,
            'created_at' => $category->created_at?->toJSON(),
            'updated_at' => $category->updated_at?->toJSON(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productToArray(Product $product): array
    {
        return [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'unit_id' => $product->unit_id,
            'purchase_unit_id' => $product->purchase_unit_id,
            'sku' => $product->sku,
            'name' => $product->name,
            'default_purchase_price' => $product->default_purchase_price === null ? null : (float) $product->default_purchase_price,
            'default_sales_price' => $product->default_sales_price === null ? null : (float) $product->default_sales_price,
            'reorder_point' => $product->reorder_point === null ? null : (float) $product->reorder_point,
            'is_active' => $product->is_active,
            'tracking' => $product->tracking->value,
            'requires_lot_tracking' => $product->requiresLotTracking(),
            'created_at' => $product->created_at?->toJSON(),
            'updated_at' => $product->updated_at?->toJSON(),
        ];
    }

    /**
     * Get the query builder rows of the current page.
     *
     * @param  LengthAwarePaginator<covariant array-key, covariant mixed>  $paginator
     * @return Collection<int, stdClass>
     */
    private function rows(LengthAwarePaginator $paginator): Collection
    {
        return Collection::make($paginator->items())
            ->filter(fn (mixed $row) => $row instanceof stdClass)
            ->values();
    }

    /**
     * Build the paginated response shape declared by the API contract.
     *
     * @param  LengthAwarePaginator<covariant array-key, covariant mixed>  $paginator
     * @param  Collection<int, covariant array<string, mixed>>  $data
     */
    private function paginatedResponse(LengthAwarePaginator $paginator, Collection $data): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
