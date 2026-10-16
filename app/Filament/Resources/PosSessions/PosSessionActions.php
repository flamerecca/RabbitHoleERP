<?php

namespace App\Filament\Resources\PosSessions;

use App\Actions\Pos\CalculateExpectedCash;
use App\Actions\Pos\ClosePosSession;
use App\Actions\Pos\OpenPosSession;
use App\Enums\PosSessionStatus;
use App\Enums\TeamPermission;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use App\Models\PosConfig;
use App\Models\PosSession;
use App\Support\Rbac\TeamAuthorizer;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

class PosSessionActions
{
    /**
     * Make the action that opens a session of an active POS store.
     */
    public static function open(): Action
    {
        return Action::make('open')
            ->label(__('Open session'))
            ->icon('heroicon-o-play')
            ->visible(fn (): bool => TeamAuthorizer::allows(TeamPermission::OpenPosSession))
            ->schema([
                Select::make('pos_config_id')
                    ->label(__('POS store'))
                    ->options(fn (): array => TeamOptions::activePosConfigs())
                    ->required()
                    ->live(),
                TextInput::make('opening_cash')
                    ->label(__('Opening cash'))
                    ->numeric()
                    ->minValue(0)
                    ->visible(fn (Get $get): bool => static::cashControlOf($get('pos_config_id')))
                    ->required(fn (Get $get): bool => static::cashControlOf($get('pos_config_id'))),
            ])
            ->action(function (array $data, Action $action): void {
                $config = PosConfig::query()->where('team_id', TeamOptions::team()->id)->findOrFail($data['pos_config_id']);

                $session = DomainAction::run(
                    fn () => app(OpenPosSession::class)->handle($config, auth()->user(), isset($data['opening_cash']) ? (float) $data['opening_cash'] : null),
                    fn () => $action->halt(),
                );

                Notification::make()->success()->title(__('Session :no opened.', ['no' => $session->session_no]))->send();
            });
    }

    /**
     * Make the action that closes an open session.
     */
    public static function close(): Action
    {
        return Action::make('close')
            ->label(__('Close session'))
            ->icon('heroicon-o-stop')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (PosSession $record): bool => static::mayClose($record))
            ->schema(fn (PosSession $record): array => [
                TextInput::make('closing_cash_counted')
                    ->label(__('Counted cash'))
                    ->numeric()
                    ->minValue(0)
                    ->visible($record->posConfig->cash_control)
                    ->required($record->posConfig->cash_control)
                    ->helperText(__('Expected cash: :amount', ['amount' => number_format(app(CalculateExpectedCash::class)->handle($record), 2)])),
                Textarea::make('closing_note')
                    ->label(__('Closing note'))
                    ->maxLength(1000),
            ])
            ->action(function (PosSession $record, array $data, Action $action): void {
                $session = DomainAction::run(
                    fn () => app(ClosePosSession::class)->handle(
                        $record,
                        auth()->user(),
                        isset($data['closing_cash_counted']) ? (float) $data['closing_cash_counted'] : null,
                        $data['closing_note'] ?? null,
                    ),
                    fn () => $action->halt(),
                );

                Notification::make()->success()->title(__('Session :no closed.', ['no' => $session->session_no]))->send();
            });
    }

    /**
     * Determine if the signed in user may close the session, the Action checks again when it runs.
     */
    protected static function mayClose(PosSession $session): bool
    {
        if ($session->status !== PosSessionStatus::Open) {
            return false;
        }

        return TeamAuthorizer::allows(TeamPermission::CloseAnyPosSession)
            || (TeamAuthorizer::allows(TeamPermission::ClosePosSession) && $session->opened_by === auth()->id());
    }

    /**
     * Determine if the chosen POS store counts cash.
     */
    protected static function cashControlOf(mixed $configId): bool
    {
        if ($configId === null || $configId === '') {
            return true;
        }

        return (bool) PosConfig::query()->where('team_id', TeamOptions::team()->id)->whereKey($configId)->value('cash_control');
    }
}
