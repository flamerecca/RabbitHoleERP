<?php

namespace App\Actions\Sales;

use App\Enums\DocumentStatus;
use App\Enums\SalesReturnDisposition;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\StockMovement;
use App\Models\Team;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class BuildSalesMarginReport
{
    /**
     * The dimensions a report can be grouped by.
     *
     * @var list<string>
     */
    public const GROUPS = ['product', 'customer', 'sales_order', 'month'];

    /**
     * The most days a report period may cover, both ends included.
     */
    public const MAX_DAYS = 366;

    /**
     * Net the confirmed shipments of the period against their confirmed returns, grouped by one dimension.
     *
     * A return counts in the period of the shipment it returns, whatever its own date is.
     *
     * @param  array{customer_id?: int|null, product_id?: int|null, warehouse_id?: int|null}  $filters
     * @return array{rows: list<array<string, string|null>>, totals: array<string, string|null>}
     *
     * @throws ValidationException
     */
    public function handle(Team $team, CarbonInterface $from, CarbonInterface $to, string $groupBy, array $filters = []): array
    {
        $this->ensureRange($from, $to);

        $shipmentScope = function (Builder $query) use ($team, $from, $to, $filters): void {
            $query->where('team_id', $team->id)
                ->where('status', DocumentStatus::Confirmed)
                ->whereDate('shipped_date', '>=', $from->toDateString())
                ->whereDate('shipped_date', '<=', $to->toDateString())
                ->when($filters['warehouse_id'] ?? null, fn (Builder $query, int $id) => $query->where('warehouse_id', $id))
                ->when($filters['customer_id'] ?? null, fn (Builder $query, int $id) => $query->whereHas('salesOrder', fn (Builder $order) => $order->where('customer_id', $id)));
        };
        $productId = $filters['product_id'] ?? null;

        $shipments = Shipment::query()
            ->where($shipmentScope)
            ->with(['salesOrder.customer', 'items.product', 'items.salesOrderItem'])
            ->get();
        $returns = SalesReturn::query()
            ->where('team_id', $team->id)
            ->where('status', DocumentStatus::Confirmed)
            ->whereHas('shipment', $shipmentScope)
            ->with(['items.product', 'shipment.salesOrder.customer', 'shipment.items.product.unit', 'shipment.items.salesOrderItem.unit'])
            ->get();

        $shipmentItems = $shipments->flatMap->items;
        $returnItems = $returns->flatMap->items;
        $shippedCosts = $this->movementCosts(StockMovementType::SalesOut, (new ShipmentItem())->getMorphClass(), $shipmentItems->pluck('id')->all());
        $returnedCosts = $this->movementCosts(
            StockMovementType::SalesReturnIn,
            (new SalesReturnItem())->getMorphClass(),
            $returnItems->filter(fn (SalesReturnItem $item) => $this->restocks($item))->pluck('id')->all(),
        );

        /** @var array<string, array{label: string, shipped_revenue: float, returned_revenue: float, shipped_cost: float, returned_cost: float}> $groups */
        $groups = [];

        foreach ($shipments as $shipment) {
            $order = $shipment->salesOrder;

            foreach ($shipment->items as $item) {
                if ($productId !== null && $item->product_id !== $productId) {
                    continue;
                }

                [$key, $label] = $this->group($groupBy, $shipment, $item->product);
                $groups[$key] ??= $this->emptyGroup($label);
                $groups[$key]['shipped_revenue'] += (float) $item->quantity * (float) $item->salesOrderItem->unit_price * (float) $order->exchange_rate;
                $groups[$key]['shipped_cost'] += $shippedCosts[$item->id] ?? 0.0;
            }
        }

        foreach ($returns as $salesReturn) {
            $shipment = $salesReturn->shipment;

            foreach ($salesReturn->items as $item) {
                if ($productId !== null && $item->product_id !== $productId) {
                    continue;
                }

                [$key, $label] = $this->group($groupBy, $shipment, $item->product);
                $groups[$key] ??= $this->emptyGroup($label);
                $groups[$key]['returned_revenue'] += (float) $item->quantity * $this->shippedUnitPrice($shipment, $item->product_id) * (float) $shipment->salesOrder->exchange_rate;
                $groups[$key]['returned_cost'] += $this->restocks($item) ? ($returnedCosts[$item->id] ?? 0.0) : 0.0;
            }
        }

        ksort($groups, SORT_STRING);

        $rows = [];
        $sum = $this->emptyGroup('');

        foreach ($groups as $key => $group) {
            $rows[] = ['key' => (string) $key, 'label' => $group['label'], ...$this->format($group)];

            foreach (['shipped_revenue', 'returned_revenue', 'shipped_cost', 'returned_cost'] as $field) {
                $sum[$field] += $group[$field];
            }
        }

        return ['rows' => $rows, 'totals' => $this->format($sum)];
    }

    /**
     * Reject a reversed range and a range longer than the limit.
     *
     * @throws ValidationException
     */
    protected function ensureRange(CarbonInterface $from, CarbonInterface $to): void
    {
        if ($to->lt($from)) {
            throw ValidationException::withMessages(['date_to' => __('The end date cannot be earlier than the start date.')]);
        }

        if ($from->startOfDay()->diffInDays($to->startOfDay()) + 1 > self::MAX_DAYS) {
            throw ValidationException::withMessages(['date_to' => __('The report period cannot exceed :days days.', ['days' => self::MAX_DAYS])]);
        }
    }

    /**
     * Whether the return line takes the goods back into stock, so their cost moves back out of cost of goods sold.
     */
    protected function restocks(SalesReturnItem $item): bool
    {
        return in_array($item->disposition, [SalesReturnDisposition::Restock, SalesReturnDisposition::Refurbish], true);
    }

    /**
     * Get the key and label of the group a line belongs to.
     *
     * @return array{0: string, 1: string}
     */
    protected function group(string $groupBy, Shipment $shipment, Product $product): array
    {
        $order = $shipment->salesOrder;

        return match ($groupBy) {
            'product' => [(string) $product->id, "{$product->sku} {$product->name}"],
            'customer' => [(string) $order->customer_id, "{$order->customer->code} {$order->customer->name}"],
            'sales_order' => [(string) $order->id, $order->order_no],
            'month' => [$shipment->shipped_date->format('Y-m'), $shipment->shipped_date->format('Y-m')],
        };
    }

    /**
     * @return array{label: string, shipped_revenue: float, returned_revenue: float, shipped_cost: float, returned_cost: float}
     */
    protected function emptyGroup(string $label): array
    {
        return ['label' => $label, 'shipped_revenue' => 0.0, 'returned_revenue' => 0.0, 'shipped_cost' => 0.0, 'returned_cost' => 0.0];
    }

    /**
     * Turn the raw sums of a group into the net figures and the margin rate.
     *
     * @param  array{shipped_revenue: float, returned_revenue: float, shipped_cost: float, returned_cost: float}  $group
     * @return array<string, string|null>
     */
    protected function format(array $group): array
    {
        $netRevenue = round($group['shipped_revenue'] - $group['returned_revenue'], 4);
        $netCost = round($group['shipped_cost'] - $group['returned_cost'], 4);
        $margin = round($netRevenue - $netCost, 4);

        return [
            'shipped_revenue' => $this->money($group['shipped_revenue']),
            'returned_revenue' => $this->money($group['returned_revenue']),
            'shipped_cost' => $this->money($group['shipped_cost']),
            'returned_cost' => $this->money($group['returned_cost']),
            'net_revenue' => $this->money($netRevenue),
            'net_cost' => $this->money($netCost),
            'gross_margin' => $this->money($margin),
            'margin_rate' => abs($netRevenue) < 0.00005 ? null : number_format(round($margin / $netRevenue, 4), 4, '.', ''),
        ];
    }

    protected function money(float $amount): string
    {
        return number_format(round($amount, 4) + 0.0, 4, '.', '');
    }

    /**
     * Sum the absolute movement cost per referenced line.
     *
     * @param  list<int|string>  $ids
     * @return array<int, float>
     */
    protected function movementCosts(StockMovementType $type, string $morphClass, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return StockMovement::query()
            ->where('reference_type', $morphClass)
            ->whereIn('reference_id', $ids)
            ->where('type', $type)
            ->groupBy('reference_id')
            ->selectRaw('reference_id, SUM(ABS(total_cost)) AS cost')
            ->pluck('cost', 'reference_id')
            ->map(fn ($cost) => (float) $cost)
            ->all();
    }

    /**
     * Get the average sales price per stock unit, in the order currency, at which the shipment sent the product out.
     *
     * Mirrors PostDocumentJournalEntries::shippedUnitPrice, so a return is valued as the ledger values it.
     */
    protected function shippedUnitPrice(Shipment $shipment, int $productId): float
    {
        $lines = $shipment->items->where('product_id', $productId);
        $quantity = (float) $lines->sum(fn (ShipmentItem $item) => $item->salesOrderItem->unit->convertQuantity($item->quantity, $item->product->unit));
        $price = (float) $lines->sum(fn (ShipmentItem $item) => (float) $item->quantity * (float) $item->salesOrderItem->unit_price);

        return $quantity > 0 ? $price / $quantity : 0.0;
    }
}
