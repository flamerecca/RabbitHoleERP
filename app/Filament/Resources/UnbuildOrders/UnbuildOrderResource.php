<?php

namespace App\Filament\Resources\UnbuildOrders;

use App\Enums\DocumentStatus;
use App\Filament\Resources\UnbuildOrders\Pages\CreateUnbuildOrder;
use App\Filament\Resources\UnbuildOrders\Pages\EditUnbuildOrder;
use App\Filament\Resources\UnbuildOrders\Pages\ListUnbuildOrders;
use App\Filament\Support\DocumentColumns;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\TeamOptions;
use App\Models\UnbuildOrder;
use App\Models\Team;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UnbuildOrderResource extends Resource
{
    protected static ?string $model = UnbuildOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'unbuild_no';

    public static function getNavigationGroup(): ?string
    {
        return __('Planning');
    }

    public static function getModelLabel(): string
    {
        return __('Unbuild order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Unbuild orders');
    }

    /**
     * Only teams that enabled the MRP module may use the unbuild orders.
     */
    public static function canAccess(): bool
    {
        $team = Filament::getTenant();

        return $team instanceof Team && $team->isMrpEnabled();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema, 'unbuild_no', [
            Select::make('warehouse_id')
                ->label('Warehouse')
                ->options(fn () => TeamOptions::warehouses())
                ->required()
                ->disabledOn('edit'),
            Select::make('product_id')
                ->label('Product')
                ->options(fn () => TeamOptions::products())
                ->searchable()
                ->required()
                ->disabledOn('edit'),
            TextInput::make('quantity')
                ->numeric()
                ->rules(['gt:0'])
                ->required(),
            Textarea::make('reason')
                ->label(__('Reason'))
                ->maxLength(1000),
        ], [
            Repeater::make('items')
                ->label(__('Components'))
                ->visibleOn('edit')
                ->disabled()
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->columns(2)
                ->schema([
                    Select::make('component_product_id')
                        ->label('Product')
                        ->options(fn () => TeamOptions::products()),
                    TextInput::make('quantity'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unbuild_no')
                    ->label('Document no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.sku')
                    ->label('Product'),
                TextColumn::make('quantity'),
                TextColumn::make('warehouse.name')
                    ->label('Warehouse'),
                TextColumn::make('unbuilt_at')
                    ->dateTime()
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
            'index' => ListUnbuildOrders::route('/'),
            'create' => CreateUnbuildOrder::route('/create'),
            'edit' => EditUnbuildOrder::route('/{record}/edit'),
        ];
    }
}
