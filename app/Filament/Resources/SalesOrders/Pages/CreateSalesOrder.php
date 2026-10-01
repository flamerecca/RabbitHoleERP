<?php

namespace App\Filament\Resources\SalesOrders\Pages;

use App\Data\DocumentResult;
use App\Filament\Resources\SalesOrders\SalesOrderResource;
use App\Filament\Support\OrderForm;
use App\Filament\Support\Pages\CreateDocument;
use App\Filament\Support\SalesOrderEndpoint;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreateSalesOrder extends CreateDocument
{
    protected static string $resource = SalesOrderResource::class;

    protected function createDocument(Team $team, User $user, array $data): Model|DocumentResult
    {
        return SalesOrderEndpoint::call('store', $team, input: OrderForm::editableData($data));
    }
}
