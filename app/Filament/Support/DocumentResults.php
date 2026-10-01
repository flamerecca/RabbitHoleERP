<?php

namespace App\Filament\Support;

use App\Data\DocumentResult;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class DocumentResults
{
    /**
     * Get the document of an action result, showing the warnings of the matched document rules as notifications.
     *
     * @param  Model|DocumentResult<Model>  $result
     */
    public static function unwrap(Model|DocumentResult $result): Model
    {
        if ($result instanceof Model) {
            return $result;
        }

        foreach ($result->warnings as $warning) {
            Notification::make()->warning()->title($warning)->send();
        }

        return $result->document;
    }
}
