<?php

namespace App\Filament\Resources\ScrapOrders\Pages;

use App\Actions\Mrp\CreateScrapOrder as CreateAction;
use App\Data\DocumentResult;
use App\Filament\Resources\ScrapOrders\ScrapOrderResource;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\CreateDocument;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreateScrapOrder extends CreateDocument
{
    protected static string $resource = ScrapOrderResource::class;

    protected function createDocument(Team $team, User $user, array $data): Model|DocumentResult
    {
        return app(CreateAction::class)->handle($team, $user, DocumentForm::editableLines($data, ['product_id', 'quantity']));
    }
}
