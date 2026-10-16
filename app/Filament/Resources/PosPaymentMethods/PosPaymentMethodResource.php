<?php

namespace App\Filament\Resources\PosPaymentMethods;

use App\Actions\Pos\DeletePosPaymentMethod;
use App\Actions\Pos\SavePosPaymentMethod;
use App\Enums\PosPaymentMethodType;
use App\Enums\TeamPermission;
use App\Filament\Resources\PosPaymentMethods\Pages\ManagePosPaymentMethods;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use App\Models\PosPaymentMethod;
use App\Support\Rbac\TeamAuthorizer;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PosPaymentMethodResource extends Resource
{
    protected static ?string $model = PosPaymentMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('Point of sale');
    }

    public static function getModelLabel(): string
    {
        return __('POS payment method');
    }

    public static function getPluralModelLabel(): string
    {
        return __('POS payment methods');
    }

    public static function canViewAny(): bool
    {
        return parent::canViewAny() && TeamAuthorizer::allows(TeamPermission::ViewPosPaymentMethod);
    }

    public static function canCreate(): bool
    {
        return parent::canCreate() && TeamAuthorizer::allows(TeamPermission::CreatePosPaymentMethod);
    }

    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record) && TeamAuthorizer::allows(TeamPermission::UpdatePosPaymentMethod);
    }

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && TeamAuthorizer::allows(TeamPermission::DeletePosPaymentMethod);
    }

    public static function canDeleteAny(): bool
    {
        return parent::canDeleteAny() && TeamAuthorizer::allows(TeamPermission::DeletePosPaymentMethod);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->options(fn (): array => TeamOptions::posPaymentMethodTypes())
                    ->required()
                    ->live(),
                Select::make('journal_id')
                    ->label(__('Journal'))
                    ->options(function (Get $get): array {
                        $type = PosPaymentMethodType::tryFrom((string) $get('type'));

                        return $type === null ? [] : TeamOptions::journalsOfType($type->journalType());
                    })
                    ->required()
                    ->searchable(),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->formatStateUsing(fn (PosPaymentMethodType $state): string => __("pos_payment_type.{$state->value}")),
                TextColumn::make('journal.code')
                    ->label(__('Journal')),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make()
                    ->visible(fn (PosPaymentMethod $record): bool => static::canEdit($record))
                    ->using(fn (PosPaymentMethod $record, array $data, EditAction $action) => DomainAction::run(
                        fn () => app(SavePosPaymentMethod::class)->handle(TeamOptions::team(), $data, $record),
                        fn () => $action->halt(),
                    )),
                DeleteAction::make()
                    ->visible(fn (PosPaymentMethod $record): bool => static::canDelete($record))
                    ->using(fn (PosPaymentMethod $record, DeleteAction $action) => DomainAction::run(
                        fn () => app(DeletePosPaymentMethod::class)->handle($record),
                        fn () => $action->halt(),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePosPaymentMethods::route('/'),
        ];
    }
}
