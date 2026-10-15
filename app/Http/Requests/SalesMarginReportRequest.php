<?php

namespace App\Http\Requests;

use App\Actions\Sales\BuildSalesMarginReport;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesMarginReportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return [
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
            'group_by' => ['required', Rule::in(BuildSalesMarginReport::GROUPS)],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('team_id', $team->id)],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('team_id', $team->id)],
            'warehouse_id' => ['nullable', 'integer', Rule::exists('warehouses', 'id')->where('team_id', $team->id)],
        ];
    }

    /**
     * Get the validated report criteria in the shape the report action takes.
     *
     * @return array{from: CarbonImmutable, to: CarbonImmutable, group_by: string, filters: array{customer_id: int|null, product_id: int|null, warehouse_id: int|null}}
     */
    public function criteria(): array
    {
        $data = $this->validated();

        return [
            'from' => CarbonImmutable::parse($data['date_from']),
            'to' => CarbonImmutable::parse($data['date_to']),
            'group_by' => $data['group_by'],
            'filters' => [
                'customer_id' => isset($data['customer_id']) ? (int) $data['customer_id'] : null,
                'product_id' => isset($data['product_id']) ? (int) $data['product_id'] : null,
                'warehouse_id' => isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null,
            ],
        ];
    }
}
