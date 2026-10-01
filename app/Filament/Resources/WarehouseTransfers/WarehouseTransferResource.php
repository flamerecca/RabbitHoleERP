<?php

namespace App\Filament\Resources\WarehouseTransfers;

use App\Enums\DocumentStatus;
use App\Filament\Resources\WarehouseTransfers\Pages\CreateWarehouseTransfer;
use App\Filament\Resources\WarehouseTransfers\Pages\EditWarehouseTransfer;
use App\Filament\Resources\WarehouseTransfers\Pages\ListWarehouseTransfers;
use App\Filament\Support\DocumentColumns;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\TeamOptions;
use App\Models\WarehouseTransfer;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WarehouseTransferResource extends Resource
{
    protected static ?string $model = WarehouseTransfer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'transfer_no';

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory');
    }

    public static function getModelLabel(): string
    {
        return __('Warehouse transfer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Warehouse transfers');
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema, 'transfer_no', [
            Select::make('from_warehouse_id')
                ->label('From warehouse')
                ->options(fn () => TeamOptions::warehouses())
                ->required()
                ->disabledOn('edit'),
            Select::make('to_warehouse_id')
                ->label('To warehouse')
                ->options(fn () => TeamOptions::warehouses())
                ->required()
                ->different('from_warehouse_id')
                ->disabledOn('edit'),
            DatePicker::make('transfer_date')
                ->default(now())
                ->required(),
        ], [
            Repeater::make('items')
                ->label(__('Lines'))
                ->addActionLabel(__('Add line'))
                ->hiddenLabel()
                ->columns(3)
                ->minItems(1)
                ->required()
                ->schema([
                    Select::make('product_id')
                        ->label('Product')
                        ->options(fn () => TeamOptions::products())
                        ->searchable()
                        ->required()
                        ->columnSpan(2),
                    TextInput::make('quantity')
                        ->numeric()
                        ->rules(['gt:0'])
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transfer_no')
                    ->label('Document no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fromWarehouse.name')
                    ->label('From warehouse'),
                TextColumn::make('toWarehouse.name')
                    ->label('To warehouse'),
                TextColumn::make('transfer_date')
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
            'index' => ListWarehouseTransfers::route('/'),
            'create' => CreateWarehouseTransfer::route('/create'),
            'edit' => EditWarehouseTransfer::route('/{record}/edit'),
        ];
    }
}
