<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Actions\Sales\CreateShipment as CreateAction;
use App\Data\DocumentResult;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\CreateDocument;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreateShipment extends CreateDocument
{
    protected static string $resource = ShipmentResource::class;

    protected function createDocument(Team $team, User $user, array $data): Model|DocumentResult
    {
        return app(CreateAction::class)->handle($team, $user, DocumentForm::editableLines($data, ['sales_order_item_id', 'quantity', 'stock_lot_id']));
    }
}
