<?php

namespace App\Filament\Resources\Currencies;

use App\Actions\MasterData\DeleteCurrency;
use App\Actions\MasterData\SaveCurrency;
use App\Filament\Resources\Currencies\Pages\ManageCurrencies;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use App\Models\Currency;
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

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getNavigationGroup(): ?string
    {
        return __('Master data');
    }

    public static function getModelLabel(): string
    {
        return __('Currency');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Currencies');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(10)
                    ->scopedUnique(ignoreRecord: true),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('symbol')
                    ->maxLength(10),
                TextInput::make('exchange_rate_to_base')
                    ->required()
                    ->numeric()
                    ->rules(['gt:0'])
                    ->default(1)
                    ->helperText(__('How many base currency units one unit of this currency is worth. The base currency is always 1.')),
                Toggle::make('is_base')
                    ->label('Base currency')
                    ->helperText(__('Only the first base currency of the team can be set, and it cannot be changed later.')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name'),
                TextColumn::make('symbol'),
                TextColumn::make('exchange_rate_to_base')
                    ->numeric(),
                IconColumn::make('is_base')
                    ->label('Base currency')
                    ->boolean(),
            ])
            ->defaultSort('code')
            ->recordActions([
                EditAction::make()
                    ->using(fn (Currency $record, array $data, EditAction $action) => DomainAction::run(
                        fn () => app(SaveCurrency::class)->handle(TeamOptions::team(), $data, $record),
                        fn () => $action->halt(),
                    )),
                DeleteAction::make()
                    ->using(fn (Currency $record, DeleteAction $action) => DomainAction::run(
                        fn () => app(DeleteCurrency::class)->handle($record),
                        fn () => $action->halt(),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCurrencies::route('/'),
        ];
    }
}
