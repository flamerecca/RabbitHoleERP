<?php

namespace App\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ActivityLog
 */
class ActivityLogResource extends JsonResource
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
            'event' => $this->c3->value,
            'subject_type' => $this->c4,
            'subject_id' => $this->c5,
            'document_type' => $this->c6,
            'document_id' => $this->c7,
            'document_label' => $this->c8,
            'causer' => $this->causer === null ? null : ['id' => $this->causer->id, 'name' => $this->causer->name],
            'changes' => (object) $this->c9,
            'created_at' => $this->c10?->toJSON(),
        ];
    }
}
