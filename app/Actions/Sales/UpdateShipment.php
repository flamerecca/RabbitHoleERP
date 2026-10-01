<?php

namespace App\Actions\Sales;

use App\Data\DocumentResult;
use App\Enums\DocumentRuleEvent;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Shipment;
use App\Services\DocumentRuleEvaluator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateShipment
{
    public function __construct(
        protected CreateShipment $createShipment,
        protected DocumentRuleEvaluator $documentRuleEvaluator,
    ) {}

    /**
     * Update a draft shipment and replace its lines when given, then check the update rules.
     *
     * @param  array<string, mixed>  $attributes
     * @return DocumentResult<Shipment>
     *
     * @throws ValidationException
     */
    public function handle(Shipment $document, array $attributes): DocumentResult
    {
        return DB::transaction(function () use ($document, $attributes) {
            $document = Shipment::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft shipments can be updated.'));

            $document->update(Arr::only($attributes, ['warehouse_id', 'shipped_date']));

            if (array_key_exists('items', $attributes)) {
                $document->items()->get()->each->delete();
                $this->createShipment->createLines($document, $document->salesOrder()->firstOrFail(), $attributes['items']);
            }

            $warnings = $this->documentRuleEvaluator->ensurePasses($document->team()->firstOrFail(), DocumentType::Shipment, DocumentRuleEvent::Update, $document);

            return new DocumentResult($document->fresh('items'), $warnings);
        });
    }
}
