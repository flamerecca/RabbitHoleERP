<?php

namespace App\Filament\Resources\SalesOrders;

use App\Enums\SalesOrderStatus;
use App\Filament\Resources\SalesOrders\Pages\CreateSalesOrder;
use App\Filament\Resources\SalesOrders\Pages\EditSalesOrder;
use App\Filament\Resources\SalesOrders\Pages\ListSalesOrders;
use App\Filament\Support\DocumentColumns;
use App\Filament\Support\OrderForm;
use App\Filament\Support\TeamOptions;
use App\Models\SalesOrder;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'order_no';

    public static function getNavigationGroup(): ?string
    {
        return __('Sales');
    }

    public static function getModelLabel(): string
    {
        return __('Sales order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Sales orders');
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema, 'order_no', 'customer_id', 'Customer', fn () => TeamOptions::customers(), ['shipped_quantity', 'reserved_quantity']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_no')
                    ->label('Document no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
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
                    ->options(collect(SalesOrderStatus::cases())->mapWithKeys(fn (SalesOrderStatus $status) => [$status->value => __("status.{$status->value}")])->all()),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('Open')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesOrders::route('/'),
            'create' => CreateSalesOrder::route('/create'),
            'edit' => EditSalesOrder::route('/{record}/edit'),
        ];
    }
}
