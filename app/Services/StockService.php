<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Enums\SalesOrderStatus;
use App\Models\Product;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StockService
{
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

}
