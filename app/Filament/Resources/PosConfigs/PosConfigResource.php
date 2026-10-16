<?php

namespace App\Filament\Resources\PosConfigs;

use App\Actions\Pos\DeletePosConfig;
use App\Actions\Pos\SavePosConfig;
use App\Enums\JournalType;
use App\Enums\TeamPermission;
use App\Filament\Resources\PosConfigs\Pages\ManagePosConfigs;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use App\Models\PosConfig;
use App\Support\Rbac\TeamAuthorizer;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PosConfigResource extends Resource
{
    protected static ?string $model = PosConfig::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getNavigationGroup(): ?string
    {
        return __('Point of sale');
    }

    public static function getModelLabel(): string
    {
        return __('POS store');
    }

    public static function getPluralModelLabel(): string
    {
        return __('POS stores');
    }

    public static function canViewAny(): bool
    {
        return parent::canViewAny() && TeamAuthorizer::allows(TeamPermission::ViewPosConfig);
    }

    public static function canCreate(): bool
    {
        return parent::canCreate() && TeamAuthorizer::allows(TeamPermission::CreatePosConfig);
    }

    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record) && TeamAuthorizer::allows(TeamPermission::UpdatePosConfig);
    }

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && TeamAuthorizer::allows(TeamPermission::DeletePosConfig);
    }

    public static function canDeleteAny(): bool
    {
        return parent::canDeleteAny() && TeamAuthorizer::allows(TeamPermission::DeletePosConfig);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(50),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('warehouse_id')
                    ->label(__('Warehouse'))
                    ->options(fn (): array => TeamOptions::warehouses())
                    ->required()
                    ->searchable(),
                Select::make('sales_journal_id')
                    ->label(__('Sales journal'))
                    ->options(fn (): array => TeamOptions::journalsOfType(JournalType::Sales))
                    ->required()
                    ->searchable(),
                Select::make('payment_method_ids')
                    ->label(__('Payment methods'))
                    ->multiple()
                    ->options(fn (): array => TeamOptions::posPaymentMethods())
                    ->afterStateHydrated(fn (Select $component, ?PosConfig $record) => $component->state($record?->paymentMethods()->pluck('pos_payment_methods.id')->all() ?? [])),
                Textarea::make('receipt_header')
                    ->label(__('Receipt header'))
                    ->maxLength(1000),
                Textarea::make('receipt_footer')
                    ->label(__('Receipt footer'))
                    ->maxLength(1000),
                Toggle::make('cash_control')
                    ->label(__('Cash control'))
                    ->helperText(__('Count the cash when a session opens and closes.'))
                    ->default(true),
                Toggle::make('is_active')
                    ->default(true),
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
                TextColumn::make('warehouse.name')
                    ->label(__('Warehouse')),
                TextColumn::make('salesJournal.code')
                    ->label(__('Sales journal')),
                TextColumn::make('payment_methods_count')
                    ->label(__('Payment methods'))
                    ->counts('paymentMethods'),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('code')
            ->recordActions([
                EditAction::make()
                    ->visible(fn (PosConfig $record): bool => static::canEdit($record))
                    ->using(fn (PosConfig $record, array $data, EditAction $action) => DomainAction::run(
                        fn () => app(SavePosConfig::class)->handle(TeamOptions::team(), $data, $record),
                        fn () => $action->halt(),
                    )),
                DeleteAction::make()
                    ->visible(fn (PosConfig $record): bool => static::canDelete($record))
                    ->using(fn (PosConfig $record, DeleteAction $action) => DomainAction::run(
                        fn () => app(DeletePosConfig::class)->handle($record),
                        fn () => $action->halt(),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePosConfigs::route('/'),
        ];
    }
}
