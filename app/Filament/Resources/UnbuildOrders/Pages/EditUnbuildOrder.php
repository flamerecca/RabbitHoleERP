<?php

namespace App\Filament\Resources\UnbuildOrders\Pages;

use App\Actions\Mrp\CancelUnbuildOrder;
use App\Actions\Mrp\ConfirmUnbuildOrder;
use App\Actions\Mrp\UpdateUnbuildOrder;
use App\Data\DocumentResult;
use App\Enums\DocumentStatus;
use App\Filament\Resources\UnbuildOrders\UnbuildOrderResource;
use App\Filament\Support\ActivityLogAction;
use App\Filament\Support\DocumentActions;
use App\Filament\Support\Pages\EditDocument;
use App\Filament\Support\TeamOptions;
use App\Models\UnbuildOrder;
use App\Models\UnbuildOrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditUnbuildOrder extends EditDocument
{
    protected static string $resource = UnbuildOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActivityLogAction::make(),
            DocumentActions::transition('confirm', 'Confirm', UnbuildOrder::class, [DocumentStatus::Draft], fn (UnbuildOrder $record) => app(ConfirmUnbuildOrder::class)->handle($record, TeamOptions::user())),
            DocumentActions::transition('cancel', 'Cancel document', UnbuildOrder::class, [DocumentStatus::Draft], fn (UnbuildOrder $record) => app(CancelUnbuildOrder::class)->handle($record), 'danger'),
        ];
    }

    protected function updateDocument(Model $record, array $data): Model|DocumentResult
    {
        /** @var UnbuildOrder $record */
        return app(UpdateUnbuildOrder::class)->handle($record, Arr::only($data, ['quantity', 'reason']));
    }

    protected function itemsForForm(Model $record): array
    {
        /** @var UnbuildOrder $record */
        return array_values($record->items()->orderBy('id')->get()->map(fn (UnbuildOrderItem $item) => [
            'component_product_id' => $item->component_product_id,
            'quantity' => (float) $item->quantity,
        ])->all());
    }
}
