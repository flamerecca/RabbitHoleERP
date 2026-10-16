<?php

namespace App\Filament\Resources\PosSessions;

use App\Enums\PosSessionStatus;
use App\Enums\TeamPermission;
use App\Filament\Resources\PosSessions\Pages\ListPosSessions;
use App\Filament\Resources\PosSessions\Pages\ViewPosSession;
use App\Filament\Support\TeamOptions;
use App\Models\PosSession;
use App\Support\Rbac\TeamAuthorizer;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PosSessionResource extends Resource
{
    protected static ?string $model = PosSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'session_no';

    public static function getNavigationGroup(): ?string
    {
        return __('Point of sale');
    }

    public static function getModelLabel(): string
    {
        return __('POS session');
    }

    public static function getPluralModelLabel(): string
    {
        return __('POS sessions');
    }

    public static function canViewAny(): bool
    {
        return parent::canViewAny() && TeamAuthorizer::allows(TeamPermission::ViewPosSession);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('session_no'),
                TextEntry::make('posConfig.name')->label(__('POS store')),
                TextEntry::make('status')->badge()->formatStateUsing(fn (PosSessionStatus $state): string => __("status.{$state->value}")),
                TextEntry::make('openedBy.name')->label(__('Opened by')),
                TextEntry::make('opened_at')->dateTime(),
                TextEntry::make('opening_cash')->label(__('Opening cash')),
                TextEntry::make('closedBy.name')->label(__('Closed by')),
                TextEntry::make('closed_at')->dateTime(),
                TextEntry::make('closing_cash_expected')->label(__('Expected cash')),
                TextEntry::make('closing_cash_counted')->label(__('Counted cash')),
                TextEntry::make('cash_difference')->label(__('Cash difference')),
                TextEntry::make('closing_note')->label(__('Closing note')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('posConfig.code')
                    ->label(__('POS store')),
                TextColumn::make('openedBy.name')
                    ->label(__('Opened by')),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (PosSessionStatus $state): string => __("status.{$state->value}")),
                TextColumn::make('opened_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('closed_at')
                    ->dateTime(),
                TextColumn::make('cash_difference')
                    ->label(__('Cash difference')),
            ])
            ->defaultSort('opened_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(fn (): array => TeamOptions::statuses(PosSessionStatus::class)),
                SelectFilter::make('pos_config_id')
                    ->label(__('POS store'))
                    ->options(fn (): array => TeamOptions::activePosConfigs()),
            ])
            ->recordActions([
                ViewAction::make(),
                PosSessionActions::close(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosSessions::route('/'),
            'view' => ViewPosSession::route('/{record}'),
        ];
    }
}
