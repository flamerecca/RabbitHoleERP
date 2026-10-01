<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Team;
use App\Services\ProductDashboard;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

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
            ]);
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
