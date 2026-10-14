<?php

namespace App\Filament\Resources\MaterialRequisitions\Pages;

use App\Actions\Inventory\CreateMaterialRequisition as CreateAction;
use App\Data\DocumentResult;
use App\Filament\Resources\MaterialRequisitions\MaterialRequisitionResource;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\CreateDocument;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreateMaterialRequisition extends CreateDocument
{
    protected static string $resource = MaterialRequisitionResource::class;

    protected function createDocument(Team $team, User $user, array $data): Model|DocumentResult
    {
        return app(CreateAction::class)->handle($team, $user, DocumentForm::editableLines($data, ['product_id', 'quantity', 'note']));
    }
}
