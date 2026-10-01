<?php

namespace App\Filament\Resources\PurchaseReturns\Pages;

use App\Actions\Purchasing\CreatePurchaseReturn as CreateAction;
use App\Data\DocumentResult;
use App\Filament\Resources\PurchaseReturns\PurchaseReturnResource;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\CreateDocument;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreatePurchaseReturn extends CreateDocument
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function createDocument(Team $team, User $user, array $data): Model|DocumentResult
    {
        return app(CreateAction::class)->handle($team, DocumentForm::editableLines($data, ['product_id', 'quantity', 'unit_cost', 'stock_lot_id', 'is_whole_lot']));
    }
}
