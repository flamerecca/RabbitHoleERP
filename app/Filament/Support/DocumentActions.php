<?php

namespace App\Filament\Support;

use App\Data\DocumentResult;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class DocumentActions
{
    /**
     * Make a confirmed header action that runs a document state transition and reloads the page.
     *
     * @template TRecord of Model
     *
     * @param  class-string<TRecord>  $recordClass
     * @param  list<BackedEnum>  $statuses  The document statuses in which the action is available.
     * @param  Closure(TRecord): (Model|DocumentResult<Model>)  $handle
     */
    public static function transition(string $name, string $label, string $recordClass, array $statuses, Closure $handle, string $color = 'primary', string $page = 'edit'): Action
    {
        return Action::make($name)
            ->label(__($label))
            ->color($color)
            ->requiresConfirmation()
            ->visible(fn (Model $record): bool => in_array($record->getAttribute('status'), $statuses, true))
            ->action(function (Model $record, Action $action, Page $livewire) use ($recordClass, $handle, $label, $page): void {
                if (! $record instanceof $recordClass) {
                    throw new LogicException("The record must be a {$recordClass}.");
                }

                DocumentResults::unwrap(DomainAction::run(fn () => $handle($record), fn () => $action->halt()));

                Notification::make()->success()->title(__(':action completed.', ['action' => __($label)]))->send();

                $livewire->redirect($livewire::getResource()::getUrl($page, ['record' => $record]));
            });
    }
}
