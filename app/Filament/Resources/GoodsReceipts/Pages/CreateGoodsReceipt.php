<?php

namespace App\Filament\Resources\GoodsReceipts\Pages;

use App\Actions\Purchasing\CreateGoodsReceipt as CreateAction;
use App\Data\DocumentResult;
use App\Filament\Resources\GoodsReceipts\GoodsReceiptResource;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\CreateDocument;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreateGoodsReceipt extends CreateDocument
{
    protected static string $resource = GoodsReceiptResource::class;

    protected function createDocument(Team $team, User $user, array $data): Model|DocumentResult
    {
        return app(CreateAction::class)->handle($team, $user, DocumentForm::editableLines($data, ['purchase_order_item_id', 'quantity', 'unit_cost', 'lot_no']));
    }
}
