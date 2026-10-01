<?php

namespace App\Filament\Resources\Customers;

use App\Actions\MasterData\DeleteCustomer;
use App\Actions\MasterData\SaveCustomer;
use App\Filament\Resources\Customers\Pages\ManageCustomers;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use App\Models\Customer;
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

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('Master data');
    }

    public static function getModelLabel(): string
    {
        return __('Customer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Customers');
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
                TextInput::make('credit_limit')
                    ->numeric()
                    ->minValue(0)
                    ->helperText(__('Base currency. Leave empty for no limit.')),
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
                TextColumn::make('credit_limit')
                    ->numeric(),
                TextColumn::make('currency.code')
                    ->label('Currency'),
            ])
            ->defaultSort('code')
            ->recordActions([
                EditAction::make()
                    ->using(fn (Customer $record, array $data, EditAction $action) => DomainAction::run(
                        fn () => app(SaveCustomer::class)->handle(TeamOptions::team(), $data, $record),
                        fn () => $action->halt(),
                    )),
                DeleteAction::make()
                    ->using(fn (Customer $record, DeleteAction $action) => DomainAction::run(
                        fn () => app(DeleteCustomer::class)->handle($record),
                        fn () => $action->halt(),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCustomers::route('/'),
        ];
    }
}
