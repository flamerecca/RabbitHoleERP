<?php

namespace App\Filament\Resources\Suppliers;

use App\Actions\MasterData\DeleteSupplier;
use App\Actions\MasterData\SaveSupplier;
use App\Filament\Resources\Suppliers\Pages\ManageSuppliers;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use App\Models\Supplier;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('Master data');
    }

    public static function getModelLabel(): string
    {
        return __('Supplier');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Suppliers');
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
                Select::make('currency_id')
                    ->label('Currency')
                    ->options(fn () => TeamOptions::currencies())
                    ->required(),
                TextInput::make('contact_person')
                    ->maxLength(255),
                TextInput::make('phone')
                    ->maxLength(50),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('tax_id')
                    ->label('Tax ID')
                    ->maxLength(50),
                TextInput::make('payment_terms')
                    ->maxLength(255),
                Textarea::make('address')
                    ->maxLength(1000),
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
                TextColumn::make('contact_person'),
                TextColumn::make('phone'),
                TextColumn::make('currency.code')
                    ->label('Currency'),
            ])
            ->defaultSort('code')
            ->recordActions([
                EditAction::make()
                    ->using(fn (Supplier $record, array $data, EditAction $action) => DomainAction::run(
                        fn () => app(SaveSupplier::class)->handle(TeamOptions::team(), $data, $record),
                        fn () => $action->halt(),
                    )),
                DeleteAction::make()
                    ->using(fn (Supplier $record, DeleteAction $action) => DomainAction::run(
                        fn () => app(DeleteSupplier::class)->handle($record),
                        fn () => $action->halt(),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSuppliers::route('/'),
        ];
    }
}
