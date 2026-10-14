<?php

namespace App\Filament\Resources\ConsignmentHolds;

use App\Enums\ConsignmentHoldStatus;
use App\Filament\Resources\ConsignmentHolds\Pages\CreateConsignmentHold;
use App\Filament\Resources\ConsignmentHolds\Pages\ListConsignmentHolds;
use App\Filament\Resources\ConsignmentHolds\Pages\ViewConsignmentHold;
use App\Filament\Support\DocumentColumns;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\TeamOptions;
use App\Models\ConsignmentHold;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ConsignmentHoldResource extends Resource
{
    protected static ?string $model = ConsignmentHold::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'hold_no';

    public static function getNavigationGroup(): ?string
    {
        return __('Sales');
    }

    public static function getModelLabel(): string
    {
        return __('Consignment hold');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Consignment holds');
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema, 'hold_no', [
            Select::make('shipment_id')
                ->label('Shipment')
                ->options(fn (string $operation) => $operation === 'create' ? TeamOptions::holdableShipments() : TeamOptions::confirmedShipments())
                ->required(),
            Select::make('warehouse_id')
                ->label('Warehouse')
                ->options(fn () => TeamOptions::warehouses())
                ->required(),
            DatePicker::make('held_from')
                ->helperText(__('Defaults to the shipping date. The goods are held for 15 days.')),
            DatePicker::make('held_until')
                ->hiddenOn('create'),
            DateTimePicker::make('picked_up_at')
                ->hiddenOn('create'),
        ], [

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('hold_no')
                    ->label('Document no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('shipment.shipment_no')
                    ->label('Shipment'),
                TextColumn::make('warehouse.name')
                    ->label('Warehouse'),
                TextColumn::make('held_until')
                    ->date()
                    ->sortable(),
                DocumentColumns::status(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => TeamOptions::statuses(ConsignmentHoldStatus::class)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('Open')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsignmentHolds::route('/'),
            'create' => CreateConsignmentHold::route('/create'),
            'view' => ViewConsignmentHold::route('/{record}'),
        ];
    }
}
