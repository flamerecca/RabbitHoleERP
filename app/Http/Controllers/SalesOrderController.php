<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\ReserveSalesOrderStock;
use App\Actions\Inventory\UnreserveSalesOrderStock;
use App\Actions\Products\ResolveSalesPrice;
use App\Enums\DocumentRuleEvent;
use App\Enums\DocumentType;
use App\Enums\SalesOrderStatus;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Team;
use App\Models\Unit;
use App\Services\DocumentRuleEvaluator;
use App\Services\DocumentSequence;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SalesOrderController extends Controller
{
    public function index(Request $request, Team $team): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(SalesOrderStatus::class)],
            'customer_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
        ]);

        $query = SalesOrder::query()->with('items')->where('team_id', $team->id);
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['customer_id'])) {
            $query->where('customer_id', $validated['customer_id']);
        }
        if (! empty($validated['warehouse_id'])) {
            $query->where('warehouse_id', $validated['warehouse_id']);
        }
        $paginator = $query->orderByDesc('order_date')->orderByDesc('id')->paginate($validated['per_page'] ?? 15)->withQueryString();

        $data = [];
        foreach ($paginator->items() as $order) {
            $data[] = $this->salesOrderToArray($order);
        }

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

    public function store(Request $request, Team $team, DocumentSequence $documentSequence, DocumentRuleEvaluator $documentRuleEvaluator): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('team_id', $team->id)],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('team_id', $team->id)],
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')->where('team_id', $team->id)],
            'exchange_rate' => ['required', 'numeric', 'gt:0'],
            'order_date' => ['required', 'date_format:Y-m-d'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('team_id', $team->id)],
            'items.*.unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('team_id', $team->id)],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate_id' => ['nullable', 'integer', Rule::exists('tax_rates', 'id')->where('team_id', $team->id)],
        ]);

        // unit must be in the same category as the product stock unit
        foreach ($data['items'] as $i => $line) {
            if (! empty($line['unit_id'])) {
                $product = Product::with('unit')->whereKey($line['product_id'])->first();
                $unit = Unit::whereKey($line['unit_id'])->first();
                if ($product && $unit && $unit->category_id != $product->unit->category_id) {
                    throw ValidationException::withMessages(["items.$i.unit_id" => __('The unit must belong to the same unit category as the product stock unit.')]);
                }
            }
        }

        $prices = $this->linePrices($data['items'], $data['order_date']);

        $result = DB::transaction(function () use ($data, $prices, $team, $request, $documentSequence, $documentRuleEvaluator) {
            $order = new SalesOrder();
            $order->team_id = $team->id;
            $order->customer_id = $data['customer_id'];
            $order->warehouse_id = $data['warehouse_id'];
            $order->currency_id = $data['currency_id'];
            $order->exchange_rate = $data['exchange_rate'];
            $order->order_date = $data['order_date'];
            $order->order_no = $documentSequence->next($team, DocumentType::SalesOrder, CarbonImmutable::parse($data['order_date']));
            $order->status = SalesOrderStatus::Draft;
            $order->created_by = $request->user()->id;
            $order->save();

            foreach ($data['items'] as $i => $line) {
                $product = Product::whereKey($line['product_id'])->firstOrFail();
                $item = new SalesOrderItem();
                $item->sales_order_id = $order->id;
                $item->product_id = $line['product_id'];
                $item->unit_id = $line['unit_id'] ?? $product->unit_id;
                $item->quantity = $line['quantity'];
                $item->unit_price = $prices[$i]['unit_price'];
                $item->list_price = $prices[$i]['list_price'];
                $item->discount_rate = $prices[$i]['discount_rate'];
                $item->tax_rate_id = $line['tax_rate_id'] ?? null;
                $item->shipped_quantity = '0';
                $item->save();
            }

            // recalc totals
            $subtotal = 0;
            $tax = 0;
            foreach ($order->items()->with('taxRate')->get() as $item) {
                $subtotal += round((float) $item->quantity * (float) $item->unit_price, 4);
                $tax += round((float) $item->quantity * (float) $item->unit_price * ($item->taxRate ? (float) $item->taxRate->rate : 0), 4);
            }
            $order->subtotal = (string) round($subtotal, 4);
            $order->tax_amount = (string) round($tax, 4);
            $order->total_amount = (string) round($subtotal + $tax, 4);
            $order->save();

            $warnings = $documentRuleEvaluator->ensurePasses($team, DocumentType::SalesOrder, DocumentRuleEvent::Create, $order);

            return [$order, $warnings];
        });

        return response()->json(['data' => $this->salesOrderToArray($result[0]->fresh('items')), 'meta' => ['warnings' => $result[1]]], 201);
    }

    public function show(Team $team, SalesOrder $salesOrder): JsonResponse
    {
        return response()->json(['data' => $this->salesOrderToArray($salesOrder->load('items'))]);
    }

    public function update(Request $request, Team $team, SalesOrder $salesOrder, DocumentRuleEvaluator $documentRuleEvaluator): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => ['sometimes', 'required', 'integer', Rule::exists('customers', 'id')->where('team_id', $team->id)],
            'warehouse_id' => ['sometimes', 'required', 'integer', Rule::exists('warehouses', 'id')->where('team_id', $team->id)],
            'currency_id' => ['sometimes', 'required', 'integer', Rule::exists('currencies', 'id')->where('team_id', $team->id)],
            'exchange_rate' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'order_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('team_id', $team->id)],
            'items.*.unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('team_id', $team->id)],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate_id' => ['nullable', 'integer', Rule::exists('tax_rates', 'id')->where('team_id', $team->id)],
        ]);

        foreach ($data['items'] ?? [] as $i => $line) {
            if (! empty($line['unit_id'])) {
                $product = Product::with('unit')->whereKey($line['product_id'])->first();
                $unit = Unit::whereKey($line['unit_id'])->first();
                if ($product && $unit && $unit->category_id != $product->unit->category_id) {
                    throw ValidationException::withMessages(["items.$i.unit_id" => __('The unit must belong to the same unit category as the product stock unit.')]);
                }
            }
        }

        $prices = isset($data['items']) ? $this->linePrices($data['items'], $data['order_date'] ?? $salesOrder->order_date->toDateString()) : [];

        $result = DB::transaction(function () use ($data, $prices, $team, $salesOrder, $documentRuleEvaluator) {
            $order = SalesOrder::lockForUpdate()->findOrFail($salesOrder->id);
            if ($order->status != SalesOrderStatus::Draft) {
                abort(409, __('Only draft sales orders can be updated.'));
            }

            foreach (['customer_id', 'warehouse_id', 'currency_id', 'exchange_rate', 'order_date'] as $field) {
                if (array_key_exists($field, $data)) {
                    $order->$field = $data[$field];
                }
            }
            $order->save();

            if (isset($data['items'])) {
                $previous = $order->items()->get();
                $snapshots = $previous
                    ->filter(fn (SalesOrderItem $item) => $item->discount_rate !== null)
                    ->mapWithKeys(fn (SalesOrderItem $item) => [$item->product_id.'|'.number_format((float) $item->unit_price, 4, '.', '') => [$item->list_price, $item->discount_rate]]);
                $previous->each->delete();
                foreach ($data['items'] as $i => $line) {
                    $product = Product::whereKey($line['product_id'])->firstOrFail();
                    $price = $prices[$i];
                    if (isset($line['unit_price'])) {
                        [$price['list_price'], $price['discount_rate']] = $snapshots->get($line['product_id'].'|'.number_format((float) $line['unit_price'], 4, '.', ''), [null, null]);
                    }
                    $item = new SalesOrderItem();
                    $item->sales_order_id = $order->id;
                    $item->product_id = $line['product_id'];
                    $item->unit_id = $line['unit_id'] ?? $product->unit_id;
                    $item->quantity = $line['quantity'];
                    $item->unit_price = $price['unit_price'];
                    $item->list_price = $price['list_price'];
                    $item->discount_rate = $price['discount_rate'];
                    $item->tax_rate_id = $line['tax_rate_id'] ?? null;
                    $item->shipped_quantity = '0';
                    $item->save();
                }
            }

            // recalc totals
            $subtotal = 0;
            $tax = 0;
            foreach ($order->items()->with('taxRate')->get() as $item) {
                $subtotal += round((float) $item->quantity * (float) $item->unit_price, 4);
                $tax += round((float) $item->quantity * (float) $item->unit_price * ($item->taxRate ? (float) $item->taxRate->rate : 0), 4);
            }
            $order->subtotal = (string) round($subtotal, 4);
            $order->tax_amount = (string) round($tax, 4);
            $order->total_amount = (string) round($subtotal + $tax, 4);
            $order->save();

            $warnings = $documentRuleEvaluator->ensurePasses($team, DocumentType::SalesOrder, DocumentRuleEvent::Update, $order);

            return [$order, $warnings];
        });

        return response()->json(['data' => $this->salesOrderToArray($result[0]->fresh('items')), 'meta' => ['warnings' => $result[1]]]);
    }

    public function addItem(Request $request, Team $team, SalesOrder $salesOrder, DocumentRuleEvaluator $documentRuleEvaluator, ReserveSalesOrderStock $reserveSalesOrderStock): JsonResponse
    {
        $line = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('team_id', $team->id)],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('team_id', $team->id)],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'tax_rate_id' => ['nullable', 'integer', Rule::exists('tax_rates', 'id')->where('team_id', $team->id)],
        ]);

        $product = Product::with('unit')->whereKey($line['product_id'])->firstOrFail();
        if (! empty($line['unit_id'])) {
            $unit = Unit::whereKey($line['unit_id'])->first();
            if ($unit && $unit->category_id != $product->unit->category_id) {
                throw ValidationException::withMessages(['unit_id' => __('The unit must belong to the same unit category as the product stock unit.')]);
            }
        }

        $price = $this->linePrice($product, $line, $salesOrder->order_date->toDateString(), 'unit_price');

        $result = DB::transaction(function () use ($line, $price, $product, $team, $salesOrder, $documentRuleEvaluator, $reserveSalesOrderStock) {
            $order = SalesOrder::lockForUpdate()->findOrFail($salesOrder->id);
            if (! in_array($order->status, [SalesOrderStatus::Draft, SalesOrderStatus::Confirmed, SalesOrderStatus::PartiallyShipped])) {
                abort(409, __('Lines can only be added to draft, confirmed or partially shipped sales orders.'));
            }

            $item = new SalesOrderItem();
            $item->sales_order_id = $order->id;
            $item->product_id = $product->id;
            $item->unit_id = $line['unit_id'] ?? $product->unit_id;
            $item->quantity = $line['quantity'];
            $item->unit_price = $price['unit_price'];
            $item->list_price = $price['list_price'];
            $item->discount_rate = $price['discount_rate'];
            $item->tax_rate_id = $line['tax_rate_id'] ?? null;
            $item->shipped_quantity = '0';
            $item->save();

            // recalc totals
            $subtotal = 0;
            $tax = 0;
            foreach ($order->items()->with('taxRate')->get() as $orderItem) {
                $subtotal += round((float) $orderItem->quantity * (float) $orderItem->unit_price, 4);
                $tax += round((float) $orderItem->quantity * (float) $orderItem->unit_price * ($orderItem->taxRate ? (float) $orderItem->taxRate->rate : 0), 4);
            }
            $order->subtotal = (string) round($subtotal, 4);
            $order->tax_amount = (string) round($tax, 4);
            $order->total_amount = (string) round($subtotal + $tax, 4);
            $order->save();

            $warnings = $documentRuleEvaluator->ensurePasses($team, DocumentType::SalesOrder, DocumentRuleEvent::Update, $order);

            if ($order->status != SalesOrderStatus::Draft) {
                $reserveSalesOrderStock->handle($order);
            }

            return [$order, $warnings];
        });

        return response()->json(['data' => $this->salesOrderToArray($result[0]->fresh('items')), 'meta' => ['warnings' => $result[1]]], 201);
    }

    public function confirm(Team $team, SalesOrder $salesOrder, DocumentRuleEvaluator $documentRuleEvaluator, ReserveSalesOrderStock $reserveSalesOrderStock): JsonResponse
    {
        $result = DB::transaction(function () use ($team, $salesOrder, $documentRuleEvaluator, $reserveSalesOrderStock) {
            $order = SalesOrder::lockForUpdate()->findOrFail($salesOrder->id);
            if ($order->status != SalesOrderStatus::Draft) {
                abort(409, __('Only draft sales orders can be confirmed.'));
            }

            $warnings = $documentRuleEvaluator->ensurePasses($team, DocumentType::SalesOrder, DocumentRuleEvent::Confirm, $order);

            $order->status = SalesOrderStatus::Confirmed;
            $order->save();

            $reserveSalesOrderStock->handle($order);

            return [$order, $warnings];
        });

        return response()->json(['data' => $this->salesOrderToArray($result[0]->fresh('items')), 'meta' => ['warnings' => $result[1]]]);
    }

    public function cancel(Team $team, SalesOrder $salesOrder, UnreserveSalesOrderStock $unreserveSalesOrderStock): JsonResponse
    {
        $order = DB::transaction(function () use ($salesOrder, $unreserveSalesOrderStock) {
            $order = SalesOrder::lockForUpdate()->findOrFail($salesOrder->id);
            $shipped = $order->items()->where('shipped_quantity', '>', 0)->exists();
            if (! in_array($order->status, [SalesOrderStatus::Draft, SalesOrderStatus::Confirmed]) || $shipped) {
                abort(409, __('Sales orders with shipped goods cannot be cancelled.'));
            }
            $unreserveSalesOrderStock->handle($order);

            $order->status = SalesOrderStatus::Cancelled;
            $order->save();

            return $order;
        });

        return response()->json(['data' => $this->salesOrderToArray($order->fresh('items'))]);
    }

    public function reserve(Team $team, SalesOrder $salesOrder, ReserveSalesOrderStock $reserveSalesOrderStock): JsonResponse
    {
        $order = DB::transaction(function () use ($salesOrder, $reserveSalesOrderStock) {
            $order = SalesOrder::lockForUpdate()->findOrFail($salesOrder->id);
            if (! in_array($order->status, [SalesOrderStatus::Confirmed, SalesOrderStatus::PartiallyShipped])) {
                abort(409, __('Stock can only be reserved for confirmed or partially shipped sales orders.'));
            }
            $reserveSalesOrderStock->handle($order);

            return $order;
        });

        return response()->json(['data' => $this->salesOrderToArray($order->fresh('items'))]);
    }

    public function unreserve(Team $team, SalesOrder $salesOrder, UnreserveSalesOrderStock $unreserveSalesOrderStock): JsonResponse
    {
        $order = DB::transaction(function () use ($salesOrder, $unreserveSalesOrderStock) {
            $order = SalesOrder::lockForUpdate()->findOrFail($salesOrder->id);
            if (! in_array($order->status, [SalesOrderStatus::Confirmed, SalesOrderStatus::PartiallyShipped])) {
                abort(409, __('Stock can only be unreserved for confirmed or partially shipped sales orders.'));
            }
            $unreserveSalesOrderStock->handle($order);

            return $order;
        });

        return response()->json(['data' => $this->salesOrderToArray($order->fresh('items'))]);
    }

    /**
     * Resolve the unit price and discount snapshot of every order line before anything is written.
     *
     * @param  list<array<string, mixed>>  $lines
     * @return list<array{unit_price: string, list_price: string|null, discount_rate: string|null}>
     */
    private function linePrices(array $lines, string $orderDate): array
    {
        $prices = [];
        foreach ($lines as $line) {
            $product = Product::whereKey($line['product_id'])->firstOrFail();
            $prices[] = $this->linePrice($product, $line, $orderDate, 'items.'.count($prices).'.unit_price');
        }

        return $prices;
    }

    /**
     * Resolve the unit price and discount snapshot of an order line, keeping an explicit unit price as it is.
     *
     * @param  array<string, mixed>  $line
     * @return array{unit_price: string, list_price: string|null, discount_rate: string|null}
     */
    private function linePrice(Product $product, array $line, string $orderDate, string $errorKey): array
    {
        if (isset($line['unit_price'])) {
            return ['unit_price' => (string) $line['unit_price'], 'list_price' => null, 'discount_rate' => null];
        }

        $resolved = app(ResolveSalesPrice::class)->handle($product, $orderDate);

        if ($resolved['unit_price'] === null) {
            throw ValidationException::withMessages([$errorKey => __('The product has no default sales price, so the unit price is required.')]);
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    private function salesOrderToArray(SalesOrder $order): array
    {
        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'unit_id' => $item->unit_id,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'list_price' => $item->list_price === null ? null : (float) $item->list_price,
                'discount_rate' => $item->discount_rate === null ? null : (float) $item->discount_rate,
                'tax_rate_id' => $item->tax_rate_id,
                'subtotal' => round((float) $item->quantity * (float) $item->unit_price, 4),
                'shipped_quantity' => (float) $item->shipped_quantity,
                'reserved_quantity' => (float) $item->reserved_quantity,
            ];
        }

        return [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'customer_id' => $order->customer_id,
            'warehouse_id' => $order->warehouse_id,
            'currency_id' => $order->currency_id,
            'exchange_rate' => (float) $order->exchange_rate,
            'status' => $order->status->value,
            'order_date' => $order->order_date->toDateString(),
            'subtotal' => (float) $order->subtotal,
            'tax_amount' => (float) $order->tax_amount,
            'total_amount' => (float) $order->total_amount,
            'created_by' => $order->created_by,
            'items' => $items,
            'created_at' => $order->created_at?->toJSON(),
            'updated_at' => $order->updated_at?->toJSON(),
        ];
    }
}
