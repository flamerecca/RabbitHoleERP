<?php

namespace App\Filament\Resources\ProductCategories;

use App\Actions\Products\DeleteProductCategory;
use App\Actions\Products\UpdateProductCategory;
use App\Enums\CostMethod;
use App\Enums\LotTrackingPolicy;
use App\Filament\Resources\ProductCategories\Pages\ManageProductCategories;
use App\Filament\Support\DomainAction;
use App\Models\ProductCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('Products');
    }

    public static function getModelLabel(): string
    {
        return __('Product category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Product categories');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('Parent category')
                    ->relationship('parent', 'name'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('cost_method')
                    ->options([
                        CostMethod::Average->value => __('Average cost'),
                        CostMethod::Fifo->value => __('First in, first out'),
                    ])
                    ->default(CostMethod::Average->value)
                    ->required()
                    ->helperText(__('Cannot change after products of the category have stock movements.')),
                Select::make('lot_tracking')
                    ->label('Lot tracking')
                    ->options([
                        LotTrackingPolicy::Required->value => __('Required'),
                        LotTrackingPolicy::Optional->value => __('Not required'),
                    ])
                    ->placeholder(__('Follow the team setting'))
                    ->helperText(__('Overrides the team setting for the products of this category.')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parent.name')
                    ->label('Parent category'),
                TextColumn::make('cost_method')
                    ->badge()
                    ->formatStateUsing(fn (CostMethod $state) => $state === CostMethod::Fifo ? __('FIFO') : __('Average')),
                TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make()
                    ->using(fn (ProductCategory $record, array $data, EditAction $action) => DomainAction::run(
                        fn () => app(UpdateProductCategory::class)->handle($record, $data),
                        fn () => $action->halt(),
                    )),
                DeleteAction::make()
                    ->using(fn (ProductCategory $record, DeleteAction $action) => DomainAction::run(
                        fn () => app(DeleteProductCategory::class)->handle($record),
                        fn () => $action->halt(),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProductCategories::route('/'),
        ];
    }
}
