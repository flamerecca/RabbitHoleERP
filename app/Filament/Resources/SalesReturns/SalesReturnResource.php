<?php

namespace App\Filament\Resources\SalesReturns;

use App\Enums\DocumentStatus;
use App\Enums\SalesReturnDisposition;
use App\Filament\Resources\SalesReturns\Pages\CreateSalesReturn;
use App\Filament\Resources\SalesReturns\Pages\EditSalesReturn;
use App\Filament\Resources\SalesReturns\Pages\ListSalesReturns;
use App\Filament\Support\DocumentColumns;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\TeamOptions;
use App\Models\SalesReturn;
use App\Models\ShipmentItem;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesReturnResource extends Resource
{
    protected static ?string $model = SalesReturn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnRight;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'return_no';

    public static function getNavigationGroup(): ?string
    {
        return __('Sales');
    }

    public static function getModelLabel(): string
    {
        return __('Sales return');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Sales returns');
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema, 'return_no', [
            Select::make('shipment_id')
                ->label('Shipment')
                ->options(fn () => TeamOptions::confirmedShipments())
                ->required()
                ->live()
                ->disabledOn('edit'),
            Select::make('warehouse_id')
                ->label('Warehouse')
                ->options(fn () => TeamOptions::warehouses())
                ->required(),
            DatePicker::make('return_date')
                ->default(now())
                ->required(),
            TextInput::make('reason')
                ->required()
                ->maxLength(255),
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
                        ->options(fn (Get $get) => TeamOptions::shipmentProducts($get('../../shipment_id')))
                        ->required()
                        ->live()
                        ->columnSpan(2),
                    Select::make('stock_lot_id')
                        ->label('Lot')
                        ->options(fn (Get $get) => TeamOptions::lotsMovedBy(ShipmentItem::class, 'shipment_id', $get('../../shipment_id'), $get('product_id')))
                        ->required(fn (Get $get) => TeamOptions::isLotTracked($get('product_id')))
                        ->visible(fn (Get $get) => TeamOptions::isLotTracked($get('product_id'))),
                    Toggle::make('is_whole_lot')
                        ->label('Return the whole lot')
                        ->live()
                        ->inline(false)
                        ->visible(fn (Get $get) => TeamOptions::isLotTracked($get('product_id'))),
                    TextInput::make('quantity')
                        ->numeric()
                        ->rules(['gt:0'])
                        ->required(fn (Get $get) => ! $get('is_whole_lot'))
                        ->disabled(fn (Get $get) => (bool) $get('is_whole_lot'))
                        ->helperText(fn (Get $get) => $get('is_whole_lot') ? __('Filled in by the system from the lot.') : null),
                    Select::make('disposition')
                        ->options(fn () => collect(SalesReturnDisposition::cases())->mapWithKeys(fn (SalesReturnDisposition $disposition) => [$disposition->value => __("disposition.{$disposition->value}")])->all())
                        ->default(SalesReturnDisposition::Restock->value)
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('return_no')
                    ->label('Document no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer'),
                TextColumn::make('return_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->numeric(),
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
            'index' => ListSalesReturns::route('/'),
            'create' => CreateSalesReturn::route('/create'),
            'edit' => EditSalesReturn::route('/{record}/edit'),
        ];
    }
}
