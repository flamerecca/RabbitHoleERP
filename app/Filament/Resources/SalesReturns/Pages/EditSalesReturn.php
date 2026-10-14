<?php

namespace App\Filament\Resources\SalesReturns\Pages;

use App\Actions\Sales\CancelSalesReturn;
use App\Actions\Sales\ConfirmSalesReturn;
use App\Actions\Sales\UpdateSalesReturn;
use App\Data\DocumentResult;
use App\Enums\DocumentStatus;
use App\Filament\Resources\SalesReturns\SalesReturnResource;
use App\Filament\Support\ActivityLogAction;
use App\Filament\Support\DocumentActions;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\EditDocument;
use App\Filament\Support\TeamOptions;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use Illuminate\Database\Eloquent\Model;

class EditSalesReturn extends EditDocument
{
    protected static string $resource = SalesReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActivityLogAction::make(),
            DocumentActions::transition('confirm', 'Confirm', SalesReturn::class, [DocumentStatus::Draft], fn (SalesReturn $record) => app(ConfirmSalesReturn::class)->handle($record, TeamOptions::user())),
            DocumentActions::transition('cancel', 'Cancel document', SalesReturn::class, [DocumentStatus::Draft], fn (SalesReturn $record) => app(CancelSalesReturn::class)->handle($record), 'danger'),
        ];
    }

    protected function updateDocument(Model $record, array $data): Model|DocumentResult
    {
        /** @var SalesReturn $record */
        return app(UpdateSalesReturn::class)->handle($record, DocumentForm::editableLines($data, ['product_id', 'quantity', 'disposition', 'stock_lot_id', 'is_whole_lot']));
    }

    protected function itemsForForm(Model $record): array
    {
        /** @var SalesReturn $record */
        return array_values($record->items()->orderBy('id')->get()->map(fn (SalesReturnItem $item) => [
            'product_id' => $item->product_id,
            'quantity' => (float) $item->quantity,
            'disposition' => $item->disposition->value,
            'stock_lot_id' => $item->stock_lot_id,
            'is_whole_lot' => $item->is_whole_lot,
        ])->all());
    }
}
