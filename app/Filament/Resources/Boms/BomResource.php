<?php

namespace App\Filament\Resources\Boms;

use App\Actions\Mrp\SaveBom;
use App\Filament\Resources\Boms\Pages\ManageBoms;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Team;
use App\Models\UnbuildOrder;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BomResource extends Resource
{
    protected static ?string $model = Bom::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Planning');
    }

    public static function getModelLabel(): string
    {
        return __('Bill of materials');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Bills of materials');
    }

    /**
     * Only teams that enabled the MRP module may use the bills of materials.
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
        return $schema
            ->components([
                Select::make('product_id')
                    ->label(__('Finished product'))
                    ->options(fn (): array => TeamOptions::products())
                    ->searchable()
                    ->required(),
                TextInput::make('quantity')
                    ->label(__('Output quantity'))
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(0.0001),
                Toggle::make('is_active')
                    ->default(true),
                Repeater::make('items')
                    ->label(__('Components'))
                    ->schema([
                        Select::make('component_product_id')
                            ->label(__('Component'))
                            ->options(fn (): array => TeamOptions::products())
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('unit_id', null)),
                        TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->minValue(0.0001),
                        Select::make('unit_id')
                            ->label(__('Unit'))
                            ->options(fn (Get $get): array => TeamOptions::unitsFor($get('component_product_id')))
                            ->required(),
                    ])
                    ->columns(3)
                    ->minItems(1)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('Finished product'))
                    ->description(fn (Bom $record): string => $record->product->sku)
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label(__('Output quantity'))
                    ->numeric(maxDecimalPlaces: 4),
                TextColumn::make('items_count')
                    ->label(__('Components'))
                    ->counts('items'),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, Bom $record): array => [
                        ...$data,
                        'items' => $record->items
                            ->map(fn (BomItem $item): array => $item->only(['component_product_id', 'quantity', 'unit_id']))
                            ->all(),
                    ])
                    ->using(fn (Bom $record, array $data, EditAction $action) => DomainAction::run(
                        fn () => app(SaveBom::class)->handle(TeamOptions::team(), $data, $record),
                        fn () => $action->halt(),
                    )),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, Bom $record): void {
                        if (UnbuildOrder::query()->where('bom_id', $record->id)->exists()) {
                            Notification::make()->danger()->title(__('A bill of materials used by an unbuild order cannot be deleted.'))->send();
                            $action->halt();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBoms::route('/'),
        ];
    }
}
