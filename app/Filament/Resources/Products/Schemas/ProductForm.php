<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductTracking;
use App\Filament\Support\TeamOptions;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Product'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->maxLength(255)
                            ->scopedUnique(ignoreRecord: true),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => Product::lotTrackingRequiredFor(TeamOptions::team()->id, filled($get('category_id')) ? (int) $get('category_id') : null) ? $set('tracking', ProductTracking::Lot->value) : null)
                            ->helperText(__('The category decides the cost method.')),
                        Toggle::make('is_active')
                            ->default(true)
                            ->inline(false),
                        Select::make('tracking')
                            ->options(fn () => collect(ProductTracking::cases())->mapWithKeys(fn (ProductTracking $tracking) => [$tracking->value => __("tracking.{$tracking->value}")])->all())
                            ->default(fn () => Product::lotTrackingRequiredFor(TeamOptions::team()->id, null) ? ProductTracking::Lot->value : ProductTracking::None->value)
                            ->required()
                            ->helperText(__('Cannot change after the product has stock movements.')),
                    ]),
                Section::make(__('Units and prices'))
                    ->columns(2)
                    ->schema([
                        Select::make('unit_id')
                            ->label('Stock unit')
                            ->relationship('unit', 'code')
                            ->required()
                            ->helperText(__('Cannot change after the product has stock movements.')),
                        Select::make('purchase_unit_id')
                            ->label('Purchase unit')
                            ->relationship('purchaseUnit', 'code')
                            ->helperText(__('Defaults to the stock unit. Must belong to the same unit category.')),
                        TextInput::make('default_purchase_price')
                            ->numeric()
                            ->minValue(0)
                            ->helperText(__('Base currency, per purchase unit.')),
                        TextInput::make('default_sales_price')
                            ->numeric()
                            ->minValue(0)
                            ->helperText(__('Base currency, per stock unit.')),
                        TextInput::make('reorder_point')
                            ->numeric()
                            ->minValue(0)
                            ->helperText(__('Flags the product when the available quantity of all warehouses falls below it.')),
                    ]),
            ]);
    }
}
