<?php

namespace App\Filament\Support;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class ActivityLogAction
{
    /**
     * Make a header action linking to the activity log of the record, shown to team owners and admins.
     */
    public static function make(): Action
    {
        return Action::make('activityLog')
            ->label(__('Activity log'))
            ->icon(Heroicon::OutlinedClipboardDocumentList)
            ->color('gray')
            ->visible(fn (): bool => ActivityLogResource::canAccess())
            ->url(fn (Model $record): string => ActivityLogResource::urlFor($record->getMorphClass(), (int) $record->getKey()));
    }
}
