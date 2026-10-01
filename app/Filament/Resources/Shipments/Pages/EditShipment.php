<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Actions\Sales\CancelShipment;
use App\Actions\Sales\ShipSalesOrder;
use App\Actions\Sales\UpdateShipment;
use App\Data\DocumentResult;
use App\Enums\DocumentStatus;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Filament\Support\ActivityLogAction;
use App\Filament\Support\DocumentActions;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\EditDocument;
use App\Filament\Support\TeamOptions;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use Illuminate\Database\Eloquent\Model;

class EditShipment extends EditDocument
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActivityLogAction::make(),
            DocumentActions::transition('confirm', 'Confirm', Shipment::class, [DocumentStatus::Draft], fn (Shipment $record) => app(ShipSalesOrder::class)->handle($record, TeamOptions::user())),
            DocumentActions::transition('cancel', 'Cancel document', Shipment::class, [DocumentStatus::Draft], fn (Shipment $record) => app(CancelShipment::class)->handle($record), 'danger'),
        ];
    }

    protected function updateDocument(Model $record, array $data): Model|DocumentResult
    {
        /** @var Shipment $record */
        return app(UpdateShipment::class)->handle($record, DocumentForm::editableLines($data, ['sales_order_item_id', 'quantity', 'stock_lot_id']));
    }

    protected function itemsForForm(Model $record): array
    {
        /** @var Shipment $record */
        return array_values($record->items()->orderBy('id')->get()->map(fn (ShipmentItem $item) => [
            'sales_order_item_id' => $item->sales_order_item_id,
            'quantity' => (float) $item->quantity,
            'stock_lot_id' => $item->stock_lot_id,
        ])->all());
    }
}
