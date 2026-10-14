<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Actions\Purchasing\CreatePurchaseOrder as CreatePurchaseOrderAction;
use App\Data\DocumentResult;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Filament\Support\OrderForm;
use App\Filament\Support\Pages\CreateDocument;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreatePurchaseOrder extends CreateDocument
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function createDocument(Team $team, User $user, array $data): Model|DocumentResult
    {
        return app(CreatePurchaseOrderAction::class)->handle($team, $user, OrderForm::editableData($data));
    }
}
