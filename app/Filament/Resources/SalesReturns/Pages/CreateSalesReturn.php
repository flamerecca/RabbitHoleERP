<?php

namespace App\Filament\Resources\SalesReturns\Pages;

use App\Actions\Sales\CreateSalesReturn as CreateAction;
use App\Data\DocumentResult;
use App\Filament\Resources\SalesReturns\SalesReturnResource;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\CreateDocument;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreateSalesReturn extends CreateDocument
{
    protected static string $resource = SalesReturnResource::class;

    protected function createDocument(Team $team, User $user, array $data): Model|DocumentResult
    {
        return app(CreateAction::class)->handle($team, DocumentForm::editableLines($data, ['product_id', 'quantity', 'disposition', 'stock_lot_id', 'is_whole_lot']));
    }
}
