<?php

namespace App\Filament\Resources\GoodsReceipts\Pages;

use App\Actions\Purchasing\CancelGoodsReceipt;
use App\Actions\Purchasing\ReceiveGoods;
use App\Actions\Purchasing\UpdateGoodsReceipt;
use App\Data\DocumentResult;
use App\Enums\DocumentStatus;
use App\Filament\Resources\GoodsReceipts\GoodsReceiptResource;
use App\Filament\Support\ActivityLogAction;
use App\Filament\Support\DocumentActions;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\EditDocument;
use App\Filament\Support\TeamOptions;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use Illuminate\Database\Eloquent\Model;

class EditGoodsReceipt extends EditDocument
{
    protected static string $resource = GoodsReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActivityLogAction::make(),
            DocumentActions::transition('confirm', 'Confirm', GoodsReceipt::class, [DocumentStatus::Draft], fn (GoodsReceipt $record) => app(ReceiveGoods::class)->handle($record, TeamOptions::user())),
            DocumentActions::transition('cancel', 'Cancel document', GoodsReceipt::class, [DocumentStatus::Draft], fn (GoodsReceipt $record) => app(CancelGoodsReceipt::class)->handle($record), 'danger'),
        ];
    }

    protected function updateDocument(Model $record, array $data): Model|DocumentResult
    {
        /** @var GoodsReceipt $record */
        return app(UpdateGoodsReceipt::class)->handle($record, DocumentForm::editableLines($data, ['purchase_order_item_id', 'quantity', 'unit_cost', 'lot_no']));
    }

    protected function itemsForForm(Model $record): array
    {
        /** @var GoodsReceipt $record */
        return array_values($record->items()->orderBy('id')->get()->map(fn (GoodsReceiptItem $item) => [
            'purchase_order_item_id' => $item->purchase_order_item_id,
            'quantity' => (float) $item->quantity,
            'unit_cost' => (float) $item->unit_cost,
            'lot_no' => $item->lot_no,
        ])->all());
    }
}
