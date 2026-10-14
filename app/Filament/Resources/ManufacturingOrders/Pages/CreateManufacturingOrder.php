<?php

namespace App\Filament\Resources\ManufacturingOrders\Pages;

use App\Actions\Mrp\CreateManufacturingOrder as CreateAction;
use App\Data\DocumentResult;
use App\Filament\Resources\ManufacturingOrders\ManufacturingOrderResource;
use App\Filament\Support\Pages\CreateDocument;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateManufacturingOrder extends CreateDocument
{
    protected static string $resource = ManufacturingOrderResource::class;

    protected function createDocument(Team $team, User $user, array $data): Model|DocumentResult
    {
        return app(CreateAction::class)->handle($team, $user, Arr::only($data, ['warehouse_id', 'product_id', 'quantity', 'planned_date']));
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
