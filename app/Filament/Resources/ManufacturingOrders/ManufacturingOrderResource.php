<?php

namespace App\Filament\Resources\ManufacturingOrders;

use App\Enums\ManufacturingOrderStatus;
use App\Filament\Resources\ManufacturingOrders\Pages\CreateManufacturingOrder;
use App\Filament\Resources\ManufacturingOrders\Pages\ViewManufacturingOrder;
use App\Filament\Resources\ManufacturingOrders\Pages\ListManufacturingOrders;
use App\Filament\Support\DocumentColumns;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\TeamOptions;
use App\Models\ManufacturingOrder;
use App\Models\Team;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ManufacturingOrderResource extends Resource
{
    protected static ?string $model = ManufacturingOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'mo_no';

    public static function getNavigationGroup(): ?string
    {
        return __('Planning');
    }

    public static function getModelLabel(): string
    {
        return __('Manufacturing order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Manufacturing orders');
    }

    /**
     * Only teams that enabled the MRP module may use the manufacturing orders.
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
        return DocumentForm::configure($schema, 'mo_no', [
            Select::make('warehouse_id')
                ->label('Warehouse')
                ->options(fn () => TeamOptions::warehouses())
                ->required(),
            Select::make('product_id')
                ->label('Product')
                ->options(fn () => TeamOptions::products())
                ->searchable()
                ->required(),
            TextInput::make('quantity')
                ->numeric()
                ->rules(['gt:0'])
                ->required(),
            DatePicker::make('planned_date'),
        ], [
            Repeater::make('items')
                ->label(__('Components'))
                ->relationship()
                ->visibleOn('view')
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
                TextColumn::make('mo_no')
                    ->label('Document no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.sku')
                    ->label('Product'),
                TextColumn::make('quantity'),
                TextColumn::make('warehouse.name')
                    ->label('Warehouse'),
                TextColumn::make('planned_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                DocumentColumns::status(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => TeamOptions::statuses(ManufacturingOrderStatus::class)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('Open')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManufacturingOrders::route('/'),
            'create' => CreateManufacturingOrder::route('/create'),
            'view' => ViewManufacturingOrder::route('/{record}'),
        ];
    }
}
