<?php

namespace App\Http\Resources;

use App\Models\DocumentRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DocumentRule
 */
class DocumentRuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type->value,
            'event' => $this->event->value,
            'condition_type' => $this->condition_type->value,
            'threshold' => $this->threshold === null ? null : (float) $this->threshold,
            'partner_ids' => $this->partner_ids,
            'action' => $this->action->value,
            'message' => $this->message,
            'is_active' => $this->is_active,
            'sequence' => $this->sequence,
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
