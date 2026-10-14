<?php

namespace App\Filament\Resources\TaxRates;

use App\Actions\MasterData\DeleteTaxRate;
use App\Actions\MasterData\SaveTaxRate;
use App\Filament\Resources\TaxRates\Pages\ManageTaxRates;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use App\Models\TaxRate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaxRateResource extends Resource
{
    protected static ?string $model = TaxRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('Master data');
    }

    public static function getModelLabel(): string
    {
        return __('Tax rate');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Tax rates');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('rate')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1)
                    ->helperText(__('A fraction, for example 0.05 for 5%.')),
                Toggle::make('is_default'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rate')
                    ->numeric(),
                IconColumn::make('is_default')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make()
                    ->using(fn (TaxRate $record, array $data, EditAction $action) => DomainAction::run(
                        fn () => app(SaveTaxRate::class)->handle(TeamOptions::team(), $data, $record),
                        fn () => $action->halt(),
                    )),
                DeleteAction::make()
                    ->using(fn (TaxRate $record, DeleteAction $action) => DomainAction::run(
                        fn () => app(DeleteTaxRate::class)->handle($record),
                        fn () => $action->halt(),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTaxRates::route('/'),
        ];
    }
}
