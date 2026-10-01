<?php

namespace App\Http\Resources;

use App\Models\DocumentSequence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expects the `next_number_preview` attribute to be set on the model before serialising.
 *
 * @mixin DocumentSequence
 */
class DocumentSequenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'document_type' => $this->document_type->value,
            'prefix' => $this->prefix,
            'date_format' => $this->date_format->value,
            'separator' => $this->separator,
            'padding' => $this->padding,
            'reset_period' => $this->reset_period->value,
            'next_number_preview' => $this->getAttribute('next_number_preview'),
            'is_default' => ! $this->is_customized,
        ];
    }
}
