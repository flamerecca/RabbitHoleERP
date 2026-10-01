<?php

namespace App\Filament\Resources\StockBalances;

use App\Filament\Resources\StockBalances\Pages\ListStockBalances;
use App\Filament\Support\TeamOptions;
use App\Models\StockBalance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockBalanceResource extends Resource
{
    protected static ?string $model = StockBalance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory');
    }

    public static function getModelLabel(): string
    {
        return __('Stock balance');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Stock balances');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label('Warehouse')
                    ->sortable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable(),
                TextColumn::make('quantity_on_hand')
                    ->label('On hand')
                    ->numeric(),
                TextColumn::make('quantity_reserved')
                    ->label('Reserved')
                    ->numeric(),
                TextColumn::make('quantity_available')
                    ->label('Available')
                    ->state(fn (StockBalance $record): float => round((float) $record->quantity_on_hand - (float) $record->quantity_reserved, 4))
                    ->numeric(),
                TextColumn::make('average_cost')
                    ->numeric(),
            ])
            ->defaultSort('warehouse_id')
            ->filters([
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
            'index' => ListStockBalances::route('/'),
        ];
    }
}
