<?php

namespace App\Filament\Resources\StockTakes\Pages;

use App\Actions\Inventory\CancelStockTake;
use App\Actions\Inventory\ReconcileStockTake;
use App\Actions\Inventory\UpdateStockTake;
use App\Data\DocumentResult;
use App\Enums\DocumentStatus;
use App\Filament\Resources\StockTakes\StockTakeResource;
use App\Filament\Support\ActivityLogAction;
use App\Filament\Support\DocumentActions;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\EditDocument;
use App\Filament\Support\TeamOptions;
use App\Models\StockTake;
use App\Models\StockTakeItem;
use Illuminate\Database\Eloquent\Model;

class EditStockTake extends EditDocument
{
    protected static string $resource = StockTakeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActivityLogAction::make(),
            DocumentActions::transition('confirm', 'Confirm', StockTake::class, [DocumentStatus::Draft], fn (StockTake $record) => app(ReconcileStockTake::class)->handle($record, TeamOptions::user())),
            DocumentActions::transition('cancel', 'Cancel document', StockTake::class, [DocumentStatus::Draft], fn (StockTake $record) => app(CancelStockTake::class)->handle($record), 'danger'),
        ];
    }

    protected function updateDocument(Model $record, array $data): Model|DocumentResult
    {
        /** @var StockTake $record */
        return app(UpdateStockTake::class)->handle($record, DocumentForm::editableLines($data, ['product_id', 'stock_lot_id', 'counted_quantity']));
    }

    protected function itemsForForm(Model $record): array
    {
        /** @var StockTake $record */
        return array_values($record->items()->orderBy('id')->get()->map(fn (StockTakeItem $item) => [
            'product_id' => $item->product_id,
            'stock_lot_id' => $item->stock_lot_id,
            'counted_quantity' => $item->counted_quantity === null ? null : (float) $item->counted_quantity,
            'system_quantity' => (float) $item->system_quantity,
            'difference' => $item->difference === null ? null : (float) $item->difference,
        ])->all());
    }
}
