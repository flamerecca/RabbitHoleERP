<?php

namespace App\Filament\Resources\ConsignmentHolds\Pages;

use App\Actions\Sales\CreateConsignmentHold as CreateAction;
use App\Data\DocumentResult;
use App\Filament\Resources\ConsignmentHolds\ConsignmentHoldResource;
use App\Filament\Support\Pages\CreateDocument;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreateConsignmentHold extends CreateDocument
{
    protected static string $resource = ConsignmentHoldResource::class;

    protected function createDocument(Team $team, User $user, array $data): Model|DocumentResult
    {
        return app(CreateAction::class)->handle($team, $user, $data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
