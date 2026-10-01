<?php

namespace App\Filament\Resources\WarehouseTransfers\Pages;

use App\Actions\Inventory\CreateWarehouseTransfer as CreateAction;
use App\Data\DocumentResult;
use App\Filament\Resources\WarehouseTransfers\WarehouseTransferResource;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\CreateDocument;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreateWarehouseTransfer extends CreateDocument
{
    protected static string $resource = WarehouseTransferResource::class;

    protected function createDocument(Team $team, User $user, array $data): Model|DocumentResult
    {
        return app(CreateAction::class)->handle($team, $user, DocumentForm::editableLines($data, ['product_id', 'quantity']));
    }
}
