<?php

namespace App\Filament\Support;

use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OrderForm
{
    /**
     * Configure the form shared by purchase and sales orders.
     *
     * @param  Closure(): array<int, string>  $partnerOptions
     * @param  list<string>  $fulfilledFields  Read-only line quantities written back by receipts or shipments.
     */
    public static function configure(Schema $schema, string $numberAttribute, string $partnerField, string $partnerLabel, Closure $partnerOptions, array $fulfilledFields): Schema
    {
        return DocumentForm::configure($schema, $numberAttribute, [
            Select::make($partnerField)
                ->label($partnerLabel)
                ->options($partnerOptions)
                ->searchable()
                ->required(),
            Select::make('warehouse_id')
                ->label('Warehouse')
                ->options(fn () => TeamOptions::warehouses())
                ->required(),
            DatePicker::make('order_date')
                ->default(now())
                ->required(),
            Select::make('currency_id')
                ->label('Currency')
                ->options(fn () => TeamOptions::currencies())
                ->required(),
            TextInput::make('exchange_rate')
                ->numeric()
                ->rules(['gt:0'])
                ->default(1)
                ->required(),
        ], [
            Repeater::make('items')
                ->label(__('Lines'))
                ->addActionLabel(__('Add line'))
                ->hiddenLabel()
                ->columns(6)
                ->minItems(1)
                ->required()
                ->schema([
                    Select::make('product_id')
                        ->label('Product')
                        ->options(fn () => TeamOptions::products())
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('unit_id', null))
                        ->columnSpan(2),
                    Select::make('unit_id')
                        ->label('Unit')
                        ->options(fn (Get $get) => TeamOptions::unitsFor($get('product_id')))
                        ->helperText(__('Leave empty for the default unit of the product.')),
                    TextInput::make('quantity')
                        ->numeric()
                        ->rules(['gt:0'])
                        ->required(),
                    TextInput::make('unit_price')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    Select::make('tax_rate_id')
                        ->label('Tax rate')
                        ->options(fn () => TeamOptions::taxRates()),
                    ...array_map(fn (string $field) => TextInput::make($field)->disabled()->dehydrated(false)->hiddenOn('create'), $fulfilledFields),
                ]),
        ]);
    }

    /**
     * Keep only the editable fields of the order lines.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function editableData(array $data): array
    {
        $data['items'] = array_values(array_map(
            fn (array $line) => array_intersect_key($line, array_flip(['product_id', 'unit_id', 'quantity', 'unit_price', 'tax_rate_id'])),
            $data['items'] ?? [],
        ));

        return $data;
    }
}
