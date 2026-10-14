<?php

namespace App\Filament\Widgets;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductSupplier;
use App\Models\PurchaseOrder;
use App\Models\ReorderingRule;
use App\Models\Team;
use App\Services\ProductDashboard;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReplenishmentSuggestions extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Replenishment suggestions'))
            ->description(fn (): string => trans_choice(':count item to replenish|:count items to replenish', count($this->suggestions())))
            ->records(fn (): array => collect($this->suggestions())->take(10)->keyBy('id')->all())
            ->paginated(false)
            ->emptyStateHeading(__('No products need to be replenished.'))
            ->columns([
                TextColumn::make('product')
                    ->label('Product')
                    ->state(fn (array $record): string => "{$record['sku']} {$record['product']}")
                    ->url(fn (array $record): string => ProductResource::getUrl('edit', ['record' => $record['product_id']])),
                TextColumn::make('warehouse')
                    ->label('Warehouse'),
                TextColumn::make('forecasted')
                    ->label('Forecasted')
                    ->numeric(maxDecimalPlaces: 4),
                TextColumn::make('suggested')
                    ->label('Suggested')
                    ->numeric(maxDecimalPlaces: 4),
                TextColumn::make('supplier')
                    ->label('Supplier')
                    ->placeholder(__('No supplier')),
            ])
            ->recordActions([
                Action::make('createPurchaseOrder')
                    ->label(__('Create purchase order'))
                    ->requiresConfirmation()
                    ->action(function (array $record): void {
                        $suggestion = collect($this->suggestions())->firstWhere('id', $record['id']);

                        if ($suggestion === null) {
                            Notification::make()->warning()->title(__('The product no longer needs to be replenished.'))->send();

                            return;
                        }

                        $this->createPurchaseOrder($suggestion);
                        $this->resetTable();
                    }),
            ]);
    }

    /**
     * Create a draft purchase order for the suggestion from the preferred supplier.
     *
     * This copies the supplier lookup of ProductController::storeReplenishmentPurchaseOrders and numbers the order in a
     * dash separated sequence of its own on purpose, see docs/architecture/document-customization.md section 6.1.
     *
     * @param  array{id: int, product_id: int, suggested: float}  $record
     */
    protected function createPurchaseOrder(array $record): void
    {
        /** @var Team $team */
        $team = Filament::getTenant();
        $rule = ReorderingRule::query()->where('team_id', $team->id)->with('product')->findOrFail($record['id']);
        $today = today();

        $preferredSupplier = ProductSupplier::query()
            ->where('team_id', $team->id)
            ->where('product_id', $rule->product_id)
            ->where(fn (Builder $query) => $query->whereNull('valid_from')->orWhereDate('valid_from', '<=', $today))
            ->where(fn (Builder $query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $today))
            ->orderBy('sequence')
            ->orderBy('id')
            ->first();

        if ($preferredSupplier === null) {
            Notification::make()->danger()->title(__('The product has no supplier.'))->send();

            return;
        }

        $currency = DB::table('currencies')->where('team_id', $team->id)->where('id', $preferredSupplier->supplier->currency_id)->first()
            ?? DB::table('currencies')->where('team_id', $team->id)->where('is_base', true)->first();

        if ($currency === null) {
            Notification::make()->danger()->title(__('The team has no base currency.'))->send();

            return;
        }

        $purchaseOrder = DB::transaction(function () use ($team, $rule, $record, $preferredSupplier, $currency, $today) {
            $taxRateId = DB::table('tax_rates')->where('team_id', $team->id)->where('is_default', true)->value('id');

            $prefix = 'PO'.now()->format('Ym').'-';
            $lastOrderNo = DB::table('purchase_orders')
                ->where('team_id', $team->id)
                ->where('order_no', 'like', $prefix.'%')
                ->orderByDesc('order_no')
                ->value('order_no');
            $sequence = $lastOrderNo === null ? 1 : ((int) substr($lastOrderNo, strlen($prefix))) + 1;
            while (DB::table('purchase_orders')->where('team_id', $team->id)->where('order_no', $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT))->exists()) {
                $sequence++;
            }

            $purchaseOrder = PurchaseOrder::create([
                'team_id' => $team->id,
                'supplier_id' => $preferredSupplier->supplier_id,
                'warehouse_id' => $rule->warehouse_id,
                'currency_id' => $currency->id,
                'exchange_rate' => $currency->exchange_rate_to_base,
                'order_no' => $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
                'status' => PurchaseOrderStatus::Draft,
                'order_date' => $today,
                'created_by' => Auth::id(),
            ]);

            $purchaseOrder->items()->create([
                'product_id' => $rule->product_id,
                'unit_id' => $rule->product->unit_id,
                'quantity' => $record['suggested'],
                'unit_price' => 0,
                'tax_rate_id' => $taxRateId,
                'received_quantity' => 0,
            ]);

            $purchaseOrder->recalculateTotals();

            return $purchaseOrder;
        });

        Notification::make()->success()->title(__('Purchase order :number created.', ['number' => $purchaseOrder->order_no]))->send();
    }

    /**
     * @return list<array{id: int, product_id: int, sku: string, product: string, warehouse: string, forecasted: float|int, suggested: float, supplier: string|null}>
     */
    protected function suggestions(): array
    {
        /** @var Team $team */
        $team = Filament::getTenant();

        return once(fn () => app(ProductDashboard::class)->replenishmentSuggestions($team));
    }
}
