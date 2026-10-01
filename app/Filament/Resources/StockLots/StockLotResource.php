<?php

namespace App\Filament\Resources\StockLots;

use App\Filament\Resources\StockLots\Pages\ListStockLots;
use App\Filament\Support\TeamOptions;
use App\Models\StockLotBalance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockLotResource extends Resource
{
    protected static ?string $model = StockLotBalance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'stock-lots';

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory');
    }

    public static function getModelLabel(): string
    {
        return __('Lot');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Lots');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['stockLot', 'product', 'warehouse'])->where('quantity_on_hand', '!=', 0))
            ->columns([
                TextColumn::make('stockLot.lot_no')
                    ->label('Lot no')
                    ->searchable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Product'),
                TextColumn::make('warehouse.name')
                    ->label('Warehouse'),
                TextColumn::make('quantity_on_hand')
                    ->label('On hand')
                    ->numeric(),
            ])
            ->defaultSort('stock_lot_id', 'desc')
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
            'index' => ListStockLots::route('/'),
        ];
    }
}
