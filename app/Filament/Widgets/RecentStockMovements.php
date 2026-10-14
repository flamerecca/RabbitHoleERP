<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\StockMovement;
use App\Models\Team;
use App\Services\ProductDashboard;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentStockMovements extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Recent stock movements'))
            ->records(function (): array {
                /** @var Team $team */
                $team = Filament::getTenant();
                $dashboard = app(ProductDashboard::class);

                return $dashboard->recentMovements($team)
                    ->mapWithKeys(fn (StockMovement $movement) => [$movement->id => [
                        'product_id' => $movement->product_id,
                        'time' => $movement->created_at?->format('Y-m-d H:i'),
                        'product' => "{$movement->product->sku} {$movement->product->name}",
                        'warehouse' => $movement->warehouse->code,
                        'type' => $movement->type->value,
                        'quantity' => (float) $movement->quantity,
                        'source' => $dashboard->movementSource($movement),
                    ]])
                    ->all();
            })
            ->paginated(false)
            ->emptyStateHeading(__('No stock movements yet.'))
            ->columns([
                TextColumn::make('time')
                    ->label('Time'),
                TextColumn::make('product')
                    ->label('Product')
                    ->url(fn (array $record): string => ProductResource::getUrl('edit', ['record' => $record['product_id']])),
                TextColumn::make('warehouse')
                    ->label('Warehouse'),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("status.{$state}")),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric(maxDecimalPlaces: 4),
                TextColumn::make('source')
                    ->label('Source'),
            ]);
    }
}
