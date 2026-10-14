<?php

namespace App\Actions\Mrp;

use App\Data\RequirementLine;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SalesOrderStatus;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrderItem;
use App\Models\StockBalance;
use App\Models\Team;
use Illuminate\Support\Collection;
use LogicException;

class CalculateRequirements
{
    public function __construct(protected BomComponentFactor $factor)
    {
    }

    /**
     * Calculate the net requirement of every product with open sales demand, exploding active boms level by level.
     *
     * @return Collection<int, RequirementLine>
     */
    public function handle(Team $team): Collection
    {
        $boms = Bom::query()->with(['items.unit', 'items.component.unit'])
            ->where('team_id', $team->id)->where('is_active', true)->get()->keyBy('product_id');

        $gross = $this->salesDemand($team);
        $levels = $this->lowLevelCodes($boms, array_keys($gross));
        $onHand = $this->stockOnHand($team);
        $onOrder = $this->quantityOnOrder($team);

        $order = array_keys($levels);
        usort($order, fn (int $left, int $right) => [$levels[$left], $left] <=> [$levels[$right], $right]);

        $lines = collect();

        foreach ($order as $productId) {
            $productGross = round($gross[$productId] ?? 0.0, 4);

            if ($productGross <= 0) {
                continue;
            }

            $productOnHand = round($onHand[$productId] ?? 0.0, 4);
            $productOnOrder = round($onOrder[$productId] ?? 0.0, 4);
            $net = max(0.0, round($productGross - $productOnHand - $productOnOrder, 4));

            $lines->push(new RequirementLine(
                $productId,
                $levels[$productId],
                $this->format($productGross),
                $this->format($productOnHand),
                $this->format($productOnOrder),
                $this->format($net),
            ));

            $bom = $boms->get($productId);

            if ($net <= 0 || $bom === null) {
                continue;
            }

            foreach ($bom->items as $item) {
                $perUnit = $this->factor->handle($bom, $item);
                $gross[$item->component_product_id] = ($gross[$item->component_product_id] ?? 0.0) + $net * $perUnit;
            }
        }

        return $lines->values();
    }

    /**
     * Get the open sales demand per product in the stock unit.
     *
     * @return array<int, float>
     */
    protected function salesDemand(Team $team): array
    {
        $demand = [];

        SalesOrderItem::query()->with(['unit', 'product.unit'])
            ->whereHas('salesOrder', fn ($query) => $query->where('team_id', $team->id)
                ->whereIn('status', [SalesOrderStatus::Confirmed, SalesOrderStatus::PartiallyShipped]))
            ->get()
            ->each(function (SalesOrderItem $item) use (&$demand) {
                $remaining = max(0.0, (float) $item->quantity - (float) $item->shipped_quantity);
                $demand[$item->product_id] = ($demand[$item->product_id] ?? 0.0) + $item->unit->convertQuantity($remaining, $item->product->unit);
            });

        return $demand;
    }

    /**
     * Get the quantity on hand per product, summed over all warehouses.
     *
     * @return array<int, float>
     */
    protected function stockOnHand(Team $team): array
    {
        $onHand = [];

        StockBalance::query()->where('team_id', $team->id)->get()
            ->each(function (StockBalance $balance) use (&$onHand) {
                $onHand[$balance->product_id] = ($onHand[$balance->product_id] ?? 0.0) + (float) $balance->quantity_on_hand;
            });

        return $onHand;
    }

    /**
     * Get the quantity still to receive from open purchase orders per product in the stock unit.
     *
     * @return array<int, float>
     */
    protected function quantityOnOrder(Team $team): array
    {
        $onOrder = [];

        PurchaseOrderItem::query()->with(['unit', 'product.unit'])
            ->whereHas('purchaseOrder', fn ($query) => $query->where('team_id', $team->id)
                ->whereIn('status', [PurchaseOrderStatus::Confirmed, PurchaseOrderStatus::PartiallyReceived]))
            ->get()
            ->each(function (PurchaseOrderItem $item) use (&$onOrder) {
                $remaining = max(0.0, (float) $item->quantity - (float) $item->received_quantity);
                $onOrder[$item->product_id] = ($onOrder[$item->product_id] ?? 0.0) + $item->unit->convertQuantity($remaining, $item->product->unit);
            });

        return $onOrder;
    }

    /**
     * Get the low level code of every product reachable from the demand: the deepest position it takes in any bom chain.
     *
     * @param  Collection<int, Bom>  $boms
     * @param  list<int>  $demandProductIds
     * @return array<int, int>
     *
     * @throws LogicException
     */
    protected function lowLevelCodes(Collection $boms, array $demandProductIds): array
    {
        $levels = [];
        $stack = $demandProductIds;

        while ($stack !== []) {
            $productId = array_pop($stack);

            if (isset($levels[$productId])) {
                continue;
            }

            $levels[$productId] = 0;

            foreach ($this->itemsOf($boms, $productId) as $item) {
                $stack[] = $item->component_product_id;
            }
        }

        for ($pass = 0; $pass <= count($levels); $pass++) {
            $changed = false;

            foreach ($levels as $productId => $level) {
                foreach ($this->itemsOf($boms, $productId) as $item) {
                    if ($levels[$item->component_product_id] < $level + 1) {
                        $levels[$item->component_product_id] = $level + 1;
                        $changed = true;
                    }
                }
            }

            if (! $changed) {
                return $levels;
            }
        }

        throw new LogicException('The active bills of materials contain a cycle.');
    }

    /**
     * Get the components of the active bom of the product, or none when it has no bom.
     *
     * @param  Collection<int, Bom>  $boms
     * @return Collection<int, BomItem>|array{}
     */
    protected function itemsOf(Collection $boms, int $productId): Collection|array
    {
        return $boms->has($productId) ? $boms->get($productId)->items : [];
    }

    protected function format(float $quantity): string
    {
        return number_format($quantity, 4, '.', '');
    }
}
