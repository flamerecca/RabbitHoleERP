<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{criteria: array<string, mixed>, rows: list<array<string, mixed>>, totals: array<string, mixed>} $resource
 */
class SalesMarginReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'criteria' => $this->resource['criteria'],
            'rows' => $this->resource['rows'],
            'totals' => $this->resource['totals'],
        ];
    }
}
