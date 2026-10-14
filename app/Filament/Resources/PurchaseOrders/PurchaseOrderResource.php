<?php

namespace App\Filament\Resources\PurchaseOrders;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Support\DocumentColumns;
use App\Filament\Support\OrderForm;
use App\Filament\Support\TeamOptions;
use App\Models\PurchaseOrder;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'order_no';

    public static function getNavigationGroup(): ?string
    {
        return __('Purchasing');
    }

    public static function getModelLabel(): string
    {
        return __('Purchase order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Purchase orders');
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema, 'order_no', 'supplier_id', 'Supplier', fn () => TeamOptions::suppliers(), ['received_quantity']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_no')
                    ->label('Document no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                TextColumn::make('order_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->numeric(),
                DocumentColumns::status(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(PurchaseOrderStatus::cases())->mapWithKeys(fn (PurchaseOrderStatus $status) => [$status->value => __("status.{$status->value}")])->all()),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('Open')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
