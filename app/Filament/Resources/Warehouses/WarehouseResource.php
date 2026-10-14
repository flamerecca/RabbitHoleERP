<?php

namespace App\Filament\Resources\Warehouses;

use App\Actions\MasterData\DeleteWarehouse;
use App\Actions\MasterData\SaveWarehouse;
use App\Filament\Resources\Warehouses\Pages\ManageWarehouses;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use App\Models\Warehouse;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getNavigationGroup(): ?string
    {
        return __('Master data');
    }

    public static function getModelLabel(): string
    {
        return __('Warehouse');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Warehouses');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(50)
                    ->scopedUnique(ignoreRecord: true),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('address')
                    ->maxLength(1000),
                TextInput::make('manager_email')
                    ->email()
                    ->maxLength(255)
                    ->helperText(__('Receives the reminders of overdue consignment holds.')),
                Toggle::make('is_default'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('manager_email'),
                IconColumn::make('is_default')
                    ->boolean(),
            ])
            ->defaultSort('code')
            ->recordActions([
                EditAction::make()
                    ->using(fn (Warehouse $record, array $data, EditAction $action) => DomainAction::run(
                        fn () => app(SaveWarehouse::class)->handle(TeamOptions::team(), $data, $record),
                        fn () => $action->halt(),
                    )),
                DeleteAction::make()
                    ->using(fn (Warehouse $record, DeleteAction $action) => DomainAction::run(
                        fn () => app(DeleteWarehouse::class)->handle($record),
                        fn () => $action->halt(),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWarehouses::route('/'),
        ];
    }
}
