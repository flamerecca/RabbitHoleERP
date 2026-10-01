<?php

namespace App\Filament\Resources\Shipments;

use App\Enums\DocumentStatus;
use App\Filament\Resources\Shipments\Pages\CreateShipment;
use App\Filament\Resources\Shipments\Pages\EditShipment;
use App\Filament\Resources\Shipments\Pages\ListShipments;
use App\Filament\Support\DocumentColumns;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\TeamOptions;
use App\Models\Shipment;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'shipment_no';

    public static function getNavigationGroup(): ?string
    {
        return __('Sales');
    }

    public static function getModelLabel(): string
    {
        return __('Shipment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Shipments');
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema, 'shipment_no', [
            Select::make('sales_order_id')
                ->label('Sales order')
                ->options(fn () => TeamOptions::shippableSalesOrders())
                ->required()
                ->live()
                ->disabledOn('edit'),
            Select::make('warehouse_id')
                ->label('Warehouse')
                ->options(fn () => TeamOptions::warehouses())
                ->required()
                ->live(),
            DatePicker::make('shipped_date')
                ->default(now())
                ->required(),
        ], [
            Repeater::make('items')
                ->label(__('Lines'))
                ->addActionLabel(__('Add line'))
                ->hiddenLabel()
                ->columns(4)
                ->minItems(1)
                ->required()
                ->schema([
                    Select::make('sales_order_item_id')
                        ->label('Order line')
                        ->options(fn (Get $get) => TeamOptions::salesOrderLines($get('../../sales_order_id')))
                        ->required()
                        ->live()
                        ->columnSpan(2),
                    TextInput::make('quantity')
                        ->numeric()
                        ->rules(['gt:0'])
                        ->required(),
                    Select::make('stock_lot_id')
                        ->label('Lot')
                        ->placeholder(__('Oldest lots first'))
                        ->options(fn (Get $get) => TeamOptions::lotsInStockForSalesOrderLine($get('sales_order_item_id'), $get('../../warehouse_id'))),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shipment_no')
                    ->label('Document no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('salesOrder.order_no')
                    ->label('Sales order'),
                TextColumn::make('shipped_date')
                    ->date()
                    ->sortable(),
                DocumentColumns::status(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => TeamOptions::statuses(DocumentStatus::class)),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('Open')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShipments::route('/'),
            'create' => CreateShipment::route('/create'),
            'edit' => EditShipment::route('/{record}/edit'),
        ];
    }
}
