<?php

namespace App\Filament\Resources\StockTakes\Pages;

use App\Actions\Inventory\CreateStockTake as CreateAction;
use App\Data\DocumentResult;
use App\Filament\Resources\StockTakes\StockTakeResource;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\CreateDocument;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreateStockTake extends CreateDocument
{
    protected static string $resource = StockTakeResource::class;

    protected function createDocument(Team $team, User $user, array $data): Model|DocumentResult
    {
        return app(CreateAction::class)->handle($team, $user, DocumentForm::editableLines($data, ['product_id', 'stock_lot_id', 'counted_quantity']));
    }
}
