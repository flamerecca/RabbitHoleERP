<?php

namespace App\Filament\Resources\UnbuildOrders\Pages;

use App\Actions\Mrp\CreateUnbuildOrder as CreateAction;
use App\Data\DocumentResult;
use App\Filament\Resources\UnbuildOrders\UnbuildOrderResource;
use App\Filament\Support\Pages\CreateDocument;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateUnbuildOrder extends CreateDocument
{
    protected static string $resource = UnbuildOrderResource::class;

    protected function createDocument(Team $team, User $user, array $data): Model|DocumentResult
    {
        return app(CreateAction::class)->handle($team, $user, Arr::only($data, ['warehouse_id', 'product_id', 'quantity', 'reason']));
    }
}
