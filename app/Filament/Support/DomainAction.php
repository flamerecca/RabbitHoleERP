<?php

namespace App\Filament\Support;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DomainAction
{
    /**
     * Run a domain action from the admin panel, reporting conflicts and validation failures as notifications.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @param  Closure(): void  $halt  Stops the Filament action or page without saving by throwing its halt exception.
     * @return TResult
     */
    public static function run(Closure $callback, Closure $halt): mixed
    {
        try {
            return $callback();
        } catch (HttpExceptionInterface $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title((string) collect($exception->errors())->flatten()->first())->send();
        }

        $halt();

        throw new LogicException('The halt callback must stop the Filament action or page.');
    }
}
