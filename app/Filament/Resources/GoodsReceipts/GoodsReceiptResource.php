<?php

namespace App\Filament\Resources\GoodsReceipts;

use App\Enums\DocumentStatus;
use App\Filament\Resources\GoodsReceipts\Pages\CreateGoodsReceipt;
use App\Filament\Resources\GoodsReceipts\Pages\EditGoodsReceipt;
use App\Filament\Resources\GoodsReceipts\Pages\ListGoodsReceipts;
use App\Filament\Support\DocumentColumns;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\TeamOptions;
use App\Models\GoodsReceipt;
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

class GoodsReceiptResource extends Resource
{
    protected static ?string $model = GoodsReceipt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'receipt_no';

    public static function getNavigationGroup(): ?string
    {
        return __('Purchasing');
    }

    public static function getModelLabel(): string
    {
        return __('Goods receipt');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Goods receipts');
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema, 'receipt_no', [
            Select::make('purchase_order_id')
                ->label('Purchase order')
                ->options(fn () => TeamOptions::receivablePurchaseOrders())
                ->required()
                ->live()
                ->disabledOn('edit'),
            Select::make('warehouse_id')
                ->label('Warehouse')
                ->options(fn () => TeamOptions::warehouses())
                ->required(),
            DatePicker::make('received_date')
                ->default(now())
                ->maxDate(now())
                ->required(),
        ], [
            Repeater::make('items')
                ->label(__('Lines'))
                ->addActionLabel(__('Add line'))
                ->hiddenLabel()
                ->columns(5)
                ->minItems(1)
                ->required()
                ->schema([
                    Select::make('purchase_order_item_id')
                        ->label('Order line')
                        ->options(fn (Get $get) => TeamOptions::purchaseOrderLines($get('../../purchase_order_id')))
                        ->required()
                        ->live()
                        ->columnSpan(2),
                    TextInput::make('quantity')
                        ->numeric()
                        ->rules(['gt:0'])
                        ->required(),
                    TextInput::make('unit_cost')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    TextInput::make('lot_no')
                        ->label('Lot no')
                        ->maxLength(50)
                        ->required()
                        ->visible(fn (Get $get) => TeamOptions::purchaseOrderLineIsLotTracked($get('purchase_order_item_id'))),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_no')
                    ->label('Document no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchaseOrder.order_no')
                    ->label('Purchase order'),
                TextColumn::make('received_date')
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
            'index' => ListGoodsReceipts::route('/'),
            'create' => CreateGoodsReceipt::route('/create'),
            'edit' => EditGoodsReceipt::route('/{record}/edit'),
        ];
    }
}
