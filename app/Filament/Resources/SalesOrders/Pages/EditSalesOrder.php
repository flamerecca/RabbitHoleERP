<?php

namespace App\Filament\Resources\SalesOrders\Pages;

use App\Data\DocumentResult;
use App\Enums\SalesOrderStatus;
use App\Filament\Resources\SalesOrders\SalesOrderResource;
use App\Filament\Support\ActivityLogAction;
use App\Filament\Support\DocumentActions;
use App\Filament\Support\OrderForm;
use App\Filament\Support\Pages\EditDocument;
use App\Filament\Support\SalesOrderEndpoint;
use App\Filament\Support\TeamOptions;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Model;

class EditSalesOrder extends EditDocument
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        $open = [SalesOrderStatus::Confirmed, SalesOrderStatus::PartiallyShipped];

        return [
            ActivityLogAction::make(),
            DocumentActions::transition('confirm', 'Confirm', SalesOrder::class, [SalesOrderStatus::Draft], fn (SalesOrder $record) => SalesOrderEndpoint::call('confirm', TeamOptions::team(), $record)),
            DocumentActions::transition('reserve', 'Check availability', SalesOrder::class, $open, fn (SalesOrder $record) => SalesOrderEndpoint::call('reserve', TeamOptions::team(), $record)),
            DocumentActions::transition('unreserve', 'Unreserve', SalesOrder::class, $open, fn (SalesOrder $record) => SalesOrderEndpoint::call('unreserve', TeamOptions::team(), $record), 'gray'),
            DocumentActions::transition('cancel', 'Cancel document', SalesOrder::class, [SalesOrderStatus::Draft, SalesOrderStatus::Confirmed], fn (SalesOrder $record) => SalesOrderEndpoint::call('cancel', TeamOptions::team(), $record), 'danger'),
        ];
    }

    protected function updateDocument(Model $record, array $data): Model|DocumentResult
    {
        /** @var SalesOrder $record */
        return SalesOrderEndpoint::call('update', TeamOptions::team(), $record, OrderForm::editableData($data));
    }

    protected function itemsForForm(Model $record): array
    {
        /** @var SalesOrder $record */
        return array_values($record->items()->orderBy('id')->get()->map(fn (SalesOrderItem $item) => [
            'product_id' => $item->product_id,
            'unit_id' => $item->unit_id,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'tax_rate_id' => $item->tax_rate_id,
            'shipped_quantity' => (float) $item->shipped_quantity,
            'reserved_quantity' => (float) $item->reserved_quantity,
        ])->all());
    }
}
