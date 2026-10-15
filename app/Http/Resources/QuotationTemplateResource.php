<?php

namespace App\Http\Resources;

use App\Models\QuotationTemplate;
use App\Models\QuotationTemplateItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuotationTemplate
 */
class QuotationTemplateResource extends JsonResource
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
            'name' => $this->name,
            'is_active' => $this->is_active,
            'items' => $this->items->map(fn (QuotationTemplateItem $item) => [
                'product_id' => $item->product_id,
                'quantity' => (float) $item->quantity,
            ])->values(),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
