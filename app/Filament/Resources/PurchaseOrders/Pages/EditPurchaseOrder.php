<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Actions\Purchasing\CancelPurchaseOrder;
use App\Actions\Purchasing\ConfirmPurchaseOrder;
use App\Actions\Purchasing\UpdatePurchaseOrder;
use App\Data\DocumentResult;
use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Filament\Support\ActivityLogAction;
use App\Filament\Support\DocumentActions;
use App\Filament\Support\OrderForm;
use App\Filament\Support\Pages\EditDocument;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Model;

class EditPurchaseOrder extends EditDocument
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActivityLogAction::make(),
            DocumentActions::transition('confirm', 'Confirm', PurchaseOrder::class, [PurchaseOrderStatus::Draft], fn (PurchaseOrder $record) => app(ConfirmPurchaseOrder::class)->handle($record)),
            DocumentActions::transition('cancel', 'Cancel document', PurchaseOrder::class, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Confirmed], fn (PurchaseOrder $record) => app(CancelPurchaseOrder::class)->handle($record), 'danger'),
        ];
    }

    protected function updateDocument(Model $record, array $data): Model|DocumentResult
    {
        /** @var PurchaseOrder $record */
        return app(UpdatePurchaseOrder::class)->handle($record, OrderForm::editableData($data));
    }

    protected function itemsForForm(Model $record): array
    {
        /** @var PurchaseOrder $record */
        return array_values($record->items()->orderBy('id')->get()->map(fn (PurchaseOrderItem $item) => [
            'product_id' => $item->product_id,
            'unit_id' => $item->unit_id,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'tax_rate_id' => $item->tax_rate_id,
            'received_quantity' => (float) $item->received_quantity,
        ])->all());
    }
}
