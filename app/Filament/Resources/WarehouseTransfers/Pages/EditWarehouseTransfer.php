<?php

namespace App\Filament\Resources\WarehouseTransfers\Pages;

use App\Actions\Inventory\CancelWarehouseTransfer;
use App\Actions\Inventory\TransferStock;
use App\Actions\Inventory\UpdateWarehouseTransfer;
use App\Data\DocumentResult;
use App\Enums\DocumentStatus;
use App\Filament\Resources\WarehouseTransfers\WarehouseTransferResource;
use App\Filament\Support\ActivityLogAction;
use App\Filament\Support\DocumentActions;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\EditDocument;
use App\Filament\Support\TeamOptions;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use Illuminate\Database\Eloquent\Model;

class EditWarehouseTransfer extends EditDocument
{
    protected static string $resource = WarehouseTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActivityLogAction::make(),
            DocumentActions::transition('confirm', 'Confirm', WarehouseTransfer::class, [DocumentStatus::Draft], fn (WarehouseTransfer $record) => app(TransferStock::class)->handle($record, TeamOptions::user())),
            DocumentActions::transition('cancel', 'Cancel document', WarehouseTransfer::class, [DocumentStatus::Draft], fn (WarehouseTransfer $record) => app(CancelWarehouseTransfer::class)->handle($record), 'danger'),
        ];
    }

    protected function updateDocument(Model $record, array $data): Model|DocumentResult
    {
        /** @var WarehouseTransfer $record */
        return app(UpdateWarehouseTransfer::class)->handle($record, DocumentForm::editableLines($data, ['product_id', 'quantity']));
    }

    protected function itemsForForm(Model $record): array
    {
        /** @var WarehouseTransfer $record */
        return array_values($record->items()->orderBy('id')->get()->map(fn (WarehouseTransferItem $item) => [
            'product_id' => $item->product_id,
            'quantity' => (float) $item->quantity,
        ])->all());
    }
}
