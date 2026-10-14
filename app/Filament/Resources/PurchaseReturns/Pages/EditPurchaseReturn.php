<?php

namespace App\Filament\Resources\PurchaseReturns\Pages;

use App\Actions\Purchasing\CancelPurchaseReturn;
use App\Actions\Purchasing\ConfirmPurchaseReturn;
use App\Actions\Purchasing\UpdatePurchaseReturn;
use App\Data\DocumentResult;
use App\Enums\DocumentStatus;
use App\Filament\Resources\PurchaseReturns\PurchaseReturnResource;
use App\Filament\Support\ActivityLogAction;
use App\Filament\Support\DocumentActions;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\EditDocument;
use App\Filament\Support\TeamOptions;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Database\Eloquent\Model;

class EditPurchaseReturn extends EditDocument
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActivityLogAction::make(),
            DocumentActions::transition('confirm', 'Confirm', PurchaseReturn::class, [DocumentStatus::Draft], fn (PurchaseReturn $record) => app(ConfirmPurchaseReturn::class)->handle($record, TeamOptions::user())),
            DocumentActions::transition('cancel', 'Cancel document', PurchaseReturn::class, [DocumentStatus::Draft], fn (PurchaseReturn $record) => app(CancelPurchaseReturn::class)->handle($record), 'danger'),
        ];
    }

    protected function updateDocument(Model $record, array $data): Model|DocumentResult
    {
        /** @var PurchaseReturn $record */
        return app(UpdatePurchaseReturn::class)->handle($record, DocumentForm::editableLines($data, ['product_id', 'quantity', 'unit_cost', 'stock_lot_id', 'is_whole_lot']));
    }

    protected function itemsForForm(Model $record): array
    {
        /** @var PurchaseReturn $record */
        return array_values($record->items()->orderBy('id')->get()->map(fn (PurchaseReturnItem $item) => [
            'product_id' => $item->product_id,
            'quantity' => (float) $item->quantity,
            'unit_cost' => (float) $item->unit_cost,
            'stock_lot_id' => $item->stock_lot_id,
            'is_whole_lot' => $item->is_whole_lot,
        ])->all());
    }
}
