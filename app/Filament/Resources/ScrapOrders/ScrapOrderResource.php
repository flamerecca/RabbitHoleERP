<?php

namespace App\Filament\Resources\ScrapOrders;

use App\Enums\DocumentStatus;
use App\Filament\Resources\ScrapOrders\Pages\CreateScrapOrder;
use App\Filament\Resources\ScrapOrders\Pages\EditScrapOrder;
use App\Filament\Resources\ScrapOrders\Pages\ListScrapOrders;
use App\Filament\Support\DocumentColumns;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\TeamOptions;
use App\Models\ScrapOrder;
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

class ScrapOrderResource extends Resource
{
    protected static ?string $model = ScrapOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrash;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'scrap_no';

    public static function getNavigationGroup(): ?string
    {
        return __('Planning');
    }

    public static function getModelLabel(): string
    {
        return __('Scrap order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Scrap orders');
    }

    /**
     * Only teams with scrap orders enabled, which also needs the MRP module, may use them.
     */
    public static function canAccess(): bool
    {
        $team = Filament::getTenant();

        return $team instanceof Team && $team->isScrapEnabled();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema, 'scrap_no', [
            Select::make('warehouse_id')
                ->label('Warehouse')
                ->options(fn () => TeamOptions::warehouses())
                ->required()
                ->disabledOn('edit'),
            Textarea::make('reason')
                ->label(__('Reason'))
                ->maxLength(1000),
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
                TextColumn::make('scrap_no')
                    ->label('Document no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('Warehouse'),
                TextColumn::make('reason')
                    ->label(__('Reason'))
                    ->limit(40),
                TextColumn::make('scrapped_at')
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
            'index' => ListScrapOrders::route('/'),
            'create' => CreateScrapOrder::route('/create'),
            'edit' => EditScrapOrder::route('/{record}/edit'),
        ];
    }
}
