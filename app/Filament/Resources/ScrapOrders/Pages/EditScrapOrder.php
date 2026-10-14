<?php

namespace App\Filament\Resources\ScrapOrders\Pages;

use App\Actions\Mrp\CancelScrapOrder;
use App\Actions\Mrp\ConfirmScrapOrder;
use App\Actions\Mrp\UpdateScrapOrder;
use App\Data\DocumentResult;
use App\Enums\DocumentStatus;
use App\Filament\Resources\ScrapOrders\ScrapOrderResource;
use App\Filament\Support\ActivityLogAction;
use App\Filament\Support\DocumentActions;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\Pages\EditDocument;
use App\Filament\Support\TeamOptions;
use App\Models\ScrapOrder;
use App\Models\ScrapOrderItem;
use Illuminate\Database\Eloquent\Model;

class EditScrapOrder extends EditDocument
{
    protected static string $resource = ScrapOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActivityLogAction::make(),
            DocumentActions::transition('confirm', 'Confirm', ScrapOrder::class, [DocumentStatus::Draft], fn (ScrapOrder $record) => app(ConfirmScrapOrder::class)->handle($record, TeamOptions::user())),
            DocumentActions::transition('cancel', 'Cancel document', ScrapOrder::class, [DocumentStatus::Draft], fn (ScrapOrder $record) => app(CancelScrapOrder::class)->handle($record), 'danger'),
        ];
    }

    protected function updateDocument(Model $record, array $data): Model|DocumentResult
    {
        /** @var ScrapOrder $record */
        return app(UpdateScrapOrder::class)->handle($record, DocumentForm::editableLines($data, ['product_id', 'quantity']));
    }

    protected function itemsForForm(Model $record): array
    {
        /** @var ScrapOrder $record */
        return array_values($record->items()->orderBy('id')->get()->map(fn (ScrapOrderItem $item) => [
            'product_id' => $item->product_id,
            'quantity' => (float) $item->quantity,
        ])->all());
    }
}
