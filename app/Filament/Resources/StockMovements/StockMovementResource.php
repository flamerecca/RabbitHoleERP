<?php

namespace App\Filament\Resources\StockMovements;

use App\Enums\StockMovementType;
use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Filament\Support\TeamOptions;
use App\Models\StockMovement;
use App\Services\ProductDashboard;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory');
    }

    public static function getModelLabel(): string
    {
        return __('Stock movement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Stock movements');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('Warehouse'),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (StockMovementType $state): string => __("status.{$state->value}")),
                TextColumn::make('quantity')
                    ->numeric(),
                TextColumn::make('balance_after')
                    ->numeric(),
                TextColumn::make('total_cost')
                    ->numeric(),
                TextColumn::make('source')
                    ->label('Source')
                    ->state(fn (StockMovement $record): ?string => app(ProductDashboard::class)->movementSource($record)),
            ])
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['warehouse', 'product', 'reference']))
            ->filters([
                SelectFilter::make('type')
                    ->options(fn () => TeamOptions::statuses(StockMovementType::class)),
                SelectFilter::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(fn () => TeamOptions::warehouses()),
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(fn () => TeamOptions::products())
                    ->searchable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockMovements::route('/'),
        ];
    }
}
