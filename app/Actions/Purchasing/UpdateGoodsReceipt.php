<?php

namespace App\Actions\Purchasing;

use App\Data\DocumentResult;
use App\Enums\DocumentRuleEvent;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\GoodsReceipt;
use App\Services\DocumentRuleEvaluator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateGoodsReceipt
{
    public function __construct(
        protected CreateGoodsReceipt $createGoodsReceipt,
        protected DocumentRuleEvaluator $documentRuleEvaluator,
    ) {}

    /**
     * Update a draft goods receipt and replace its lines when given, then check the update rules.
     *
     * @param  array<string, mixed>  $attributes
     * @return DocumentResult<GoodsReceipt>
     *
     * @throws ValidationException
     */
    public function handle(GoodsReceipt $document, array $attributes): DocumentResult
    {
        return DB::transaction(function () use ($document, $attributes) {
            $document = GoodsReceipt::query()->lockForUpdate()->findOrFail($document->id);

            abort_if($document->status !== DocumentStatus::Draft, 409, __('Only draft goods receipts can be updated.'));

            $document->update(Arr::only($attributes, ['warehouse_id', 'received_date']));

            if (array_key_exists('items', $attributes)) {
                $document->items()->get()->each->delete();
                $this->createGoodsReceipt->createLines($document, $document->purchaseOrder()->firstOrFail(), $attributes['items']);
            }

            $warnings = $this->documentRuleEvaluator->ensurePasses($document->team()->firstOrFail(), DocumentType::GoodsReceipt, DocumentRuleEvent::Update, $document);

            return new DocumentResult($document->fresh('items'), $warnings);
        });
    }
}
