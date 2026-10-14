<?php

namespace App\Filament\Support\Pages;

use App\Data\DocumentResult;
use App\Enums\DocumentStatus;
use App\Filament\Support\DocumentResults;
use App\Filament\Support\DomainAction;
use BackedEnum;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

abstract class EditDocument extends EditRecord
{
    /**
     * Update the draft document through its domain action.
     *
     * @param  array<string, mixed>  $data
     * @return Model|DocumentResult<Model>
     */
    abstract protected function updateDocument(Model $record, array $data): Model|DocumentResult;

    /**
     * Map the lines of the document to the repeater state of the form.
     *
     * @return list<array<string, mixed>>
     */
    abstract protected function itemsForForm(Model $record): array;

    /**
     * Determine if the document can still be edited.
     */
    public function isEditable(): bool
    {
        $status = $this->getRecord()->getAttribute('status');

        return $status instanceof BackedEnum && $status->value === DocumentStatus::Draft->value;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [...$data, 'items' => $this->itemsForForm($this->getRecord())];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DocumentResults::unwrap(DomainAction::run(fn () => $this->updateDocument($record, $data), fn () => $this->halt()));
    }

    protected function getFormActions(): array
    {
        return $this->isEditable() ? parent::getFormActions() : [];
    }
}
