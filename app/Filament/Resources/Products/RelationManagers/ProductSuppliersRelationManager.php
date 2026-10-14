<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductSuppliersRelationManager extends RelationManager
{
    // validation duplicated from ProductController::storeSupplier and updateSupplier, see docs/architecture.md section 4.8

    protected static string $relationship = 'productSuppliers';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Supplier prices');
    }

    public static function getModelLabel(): string
    {
        return __('Supplier price');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('supplier_id')
                    ->relationship('supplier', 'name', fn (Builder $query) => $query->where('team_id', Filament::getTenant()?->getKey()))
                    ->required(),
                Select::make('currency_id')
                    ->relationship('currency', 'code', fn (Builder $query) => $query->where('team_id', Filament::getTenant()?->getKey()))
                    ->required(),
                TextInput::make('supplier_product_code')
                    ->maxLength(255),
                TextInput::make('supplier_product_name')
                    ->maxLength(255),
                TextInput::make('unit_price')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->helperText(__('Per purchase unit.')),
                TextInput::make('min_quantity')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                TextInput::make('lead_time_days')
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                TextInput::make('sequence')
                    ->integer()
                    ->default(10)
                    ->required(),
                DatePicker::make('valid_from'),
                DatePicker::make('valid_until')
                    ->afterOrEqual('valid_from'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier.name')
                    ->label('Supplier'),
                TextColumn::make('currency.code')
                    ->label('Currency'),
                TextColumn::make('unit_price')
                    ->numeric(),
                TextColumn::make('min_quantity')
                    ->numeric(),
                TextColumn::make('lead_time_days'),
                TextColumn::make('valid_from')
                    ->date(),
                TextColumn::make('valid_until')
                    ->date(),
                TextColumn::make('sequence'),
            ])
            ->defaultSort('sequence')
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data) => [...$data, 'team_id' => Filament::getTenant()?->getKey()]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
