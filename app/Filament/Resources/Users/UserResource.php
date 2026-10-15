<?php

namespace App\Filament\Resources\Users;

use App\Actions\Rbac\AssignSiteRoles;
use App\Enums\SitePermission;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Support\DomainAction;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 91;

    protected static bool $isScopedToTenant = false;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('System administration');
    }

    public static function getModelLabel(): string
    {
        return __('User');
    }

    public static function getPluralModelLabel(): string
    {
        return __('User roles');
    }

    /**
     * Only users with the assign roles permission may open the user role pages.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->checkPermissionTo(SitePermission::AssignRoles->value) === true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('roles')
                    ->multiple()
                    ->options(fn (): array => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name', 'name')->all())
                    ->afterStateHydrated(fn (Select $component, ?User $record) => $component->state($record?->getRoleNames()->all() ?? [])),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->badge(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make()
                    ->using(fn (User $record, array $data, EditAction $action) => DomainAction::run(
                        fn () => app(AssignSiteRoles::class)->handle($record, $data['roles'] ?? []),
                        fn () => $action->halt(),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
