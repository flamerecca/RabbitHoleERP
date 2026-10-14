<?php

namespace App\Filament\Resources\MaterialRequisitions\Pages;

use App\Actions\Inventory\CancelMaterialRequisition;
use App\Actions\Inventory\ConfirmMaterialRequisition;
use App\Actions\Inventory\UpdateMaterialRequisition;
use App\Data\DocumentResult;
use App\Enums\DocumentStatus;
use App\Filament\Resources\MaterialRequisitions\MaterialRequisitionResource;
use App\Filament\Support\ActivityLogAction;
use App\Filament\Support\DocumentActions;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\EditDocument;
use App\Filament\Support\TeamOptions;
use App\Models\MaterialRequisition;
use App\Models\MaterialRequisitionItem;
use Illuminate\Database\Eloquent\Model;

class EditMaterialRequisition extends EditDocument
{
    protected static string $resource = MaterialRequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActivityLogAction::make(),
            DocumentActions::transition('confirm', 'Confirm', MaterialRequisition::class, [DocumentStatus::Draft], fn (MaterialRequisition $record) => app(ConfirmMaterialRequisition::class)->handle($record, TeamOptions::user())),
            DocumentActions::transition('cancel', 'Cancel document', MaterialRequisition::class, [DocumentStatus::Draft], fn (MaterialRequisition $record) => app(CancelMaterialRequisition::class)->handle($record), 'danger'),
        ];
    }

    protected function updateDocument(Model $record, array $data): Model|DocumentResult
    {
        /** @var MaterialRequisition $record */
        return app(UpdateMaterialRequisition::class)->handle($record, DocumentForm::editableLines($data, ['product_id', 'quantity', 'note']));
    }

    protected function itemsForForm(Model $record): array
    {
        /** @var MaterialRequisition $record */
        return array_values($record->items()->orderBy('id')->get()->map(fn (MaterialRequisitionItem $item) => [
            'product_id' => $item->product_id,
            'quantity' => (float) $item->quantity,
            'note' => $item->note,
        ])->all());
    }
}
