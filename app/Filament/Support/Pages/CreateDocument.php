<?php

namespace App\Filament\Support\Pages;

use App\Data\DocumentResult;
use App\Filament\Support\DocumentResults;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use App\Models\Team;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

abstract class CreateDocument extends CreateRecord
{
    /**
     * Create the draft document through its domain action.
     *
     * @param  array<string, mixed>  $data
     * @return Model|DocumentResult<Model>
     */
    abstract protected function createDocument(Team $team, User $user, array $data): Model|DocumentResult;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $user */
        $user = auth()->user();

        return DocumentResults::unwrap(DomainAction::run(fn () => $this->createDocument(TeamOptions::team(), $user, $data), fn () => $this->halt()));
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
