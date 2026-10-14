<?php

namespace App\Filament\Resources\StockTakes;

use App\Enums\DocumentStatus;
use App\Filament\Resources\StockTakes\Pages\CreateStockTake;
use App\Filament\Resources\StockTakes\Pages\EditStockTake;
use App\Filament\Resources\StockTakes\Pages\ListStockTakes;
use App\Filament\Support\DocumentColumns;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\TeamOptions;
use App\Models\StockTake;
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

class StockTakeResource extends Resource
{
    protected static ?string $model = StockTake::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'take_no';

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory');
    }

    public static function getModelLabel(): string
    {
        return __('Stock take');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Stock takes');
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema, 'take_no', [
            Select::make('warehouse_id')
                ->label('Warehouse')
                ->options(fn () => TeamOptions::warehouses())
                ->required()
                ->disabledOn('edit'),
            DatePicker::make('taken_date')
                ->default(now())
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
                        ->columnSpan(2),
                    Select::make('stock_lot_id')
                        ->label('Lot')
                        ->options(fn (Get $get) => TeamOptions::lotsOf($get('product_id')))
                        ->required(fn (Get $get) => TeamOptions::isLotTracked($get('product_id')))
                        ->visible(fn (Get $get) => TeamOptions::isLotTracked($get('product_id'))),
                    TextInput::make('counted_quantity')
                        ->numeric()
                        ->minValue(0)
                        ->helperText(__('Leave empty until counted.')),
                    TextInput::make('system_quantity')
                        ->disabled()
                        ->dehydrated(false)
                        ->hiddenOn('create'),
                    TextInput::make('difference')
                        ->disabled()
                        ->dehydrated(false)
                        ->hiddenOn('create'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('take_no')
                    ->label('Document no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('Warehouse'),
                TextColumn::make('taken_date')
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
            'index' => ListStockTakes::route('/'),
            'create' => CreateStockTake::route('/create'),
            'edit' => EditStockTake::route('/{record}/edit'),
        ];
    }
}
