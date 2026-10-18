<?php

namespace App\Filament\Resources\StockLots;

use App\Actions\Inventory\SetLotExpiryDate;
use App\Enums\ExpiryStatus;
use App\Enums\TeamPermission;
use App\Filament\Resources\StockLots\Pages\ListStockLots;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use App\Models\StockLot;
use App\Models\StockLotBalance;
use App\Support\Expiry;
use App\Support\Rbac\TeamAuthorizer;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockLotResource extends Resource
{
    protected static ?string $model = StockLotBalance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'stock-lots';

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory');
    }

    public static function getModelLabel(): string
    {
        return __('Lot');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Lots');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['stockLot', 'product', 'warehouse'])->where('quantity_on_hand', '!=', 0))
            ->columns([
                TextColumn::make('stockLot.lot_no')
                    ->label('Lot no')
                    ->searchable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Product'),
                TextColumn::make('warehouse.name')
                    ->label('Warehouse'),
                TextColumn::make('quantity_on_hand')
                    ->label('On hand')
                    ->numeric(),
                TextColumn::make('stockLot.expiry_date')
                    ->label(__('Expiry date'))
                    ->date('Y-m-d')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy(StockLot::query()->select('expiry_date')->whereColumn('stock_lots.id', 'stock_lot_balances.stock_lot_id'), $direction === 'desc' ? 'desc' : 'asc')),
                TextColumn::make('expiry_status')
                    ->label(__('Expiry status'))
                    ->state(fn (StockLotBalance $record): string => Expiry::statusOf($record->stockLot->expiry_date, TeamOptions::team()->expiry_warning_days)->value)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'expired' => 'danger',
                        'near' => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => __("expiry.{$state}")),
            ])
            ->defaultSort('stock_lot_id', 'desc')
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(fn () => TeamOptions::warehouses()),
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(fn () => TeamOptions::products())
                    ->searchable(),
                SelectFilter::make('expiry_status')
                    ->label(__('Expiry status'))
                    ->options(fn (): array => collect(ExpiryStatus::cases())->mapWithKeys(fn (ExpiryStatus $status) => [$status->value => __("expiry.{$status->value}")])->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('stockLot', fn (Builder $lots) => Expiry::applyStatusFilter($lots, ExpiryStatus::from($data['value']), TeamOptions::team()->expiry_warning_days))
                        : $query),
            ])
            ->recordActions([
                Action::make('setExpiry')
                    ->label(__('Set expiry date'))
                    ->icon('heroicon-o-calendar')
                    ->visible(fn (StockLotBalance $record): bool => static::canEdit($record) && TeamAuthorizer::allows(TeamPermission::UpdateStockLot))
                    ->fillForm(fn (StockLotBalance $record): array => ['expiry_date' => $record->stockLot->expiry_date?->toDateString()])
                    ->schema([
                        DatePicker::make('expiry_date')
                            ->label(__('Expiry date'))
                            ->native(false),
                    ])
                    ->action(function (StockLotBalance $record, array $data, Action $action): void {
                        DomainAction::run(
                            fn () => app(SetLotExpiryDate::class)->handle(
                                $record->stockLot,
                                filled($data['expiry_date'] ?? null) ? CarbonImmutable::parse($data['expiry_date']) : null,
                                auth()->user(),
                            ),
                            fn () => $action->halt(),
                        );

                        Notification::make()->success()->title(__('Saved.'))->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockLots::route('/'),
        ];
    }
}
