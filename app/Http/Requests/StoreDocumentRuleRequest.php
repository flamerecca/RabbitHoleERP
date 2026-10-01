<?php

namespace App\Http\Requests;

use App\Concerns\ValidatesDocumentRuleConditions;
use App\Enums\DocumentRuleAction;
use App\Enums\DocumentRuleCondition;
use App\Enums\DocumentRuleEvent;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDocumentRuleRequest extends FormRequest
{
    use ValidatesDocumentRuleConditions;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(['purchase_order', 'goods_receipt', 'sales_order', 'shipment'])],
            'event' => ['required', Rule::enum(DocumentRuleEvent::class)],
            'condition_type' => ['required', Rule::enum(DocumentRuleCondition::class)],
            'threshold' => ['nullable', 'numeric', 'min:0'],
            'partner_ids' => ['nullable', 'array'],
            'partner_ids.*' => ['integer'],
            'action' => ['required', Rule::enum(DocumentRuleAction::class)],
            'message' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'sequence' => ['sometimes', 'integer'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $team = $this->route('team');

                if ($validator->errors()->isNotEmpty() || ! $team instanceof Team) {
                    return;
                }

                $this->validateConditionFields($validator, $team, [
                    'document_type' => $this->input('document_type'),
                    'condition_type' => $this->input('condition_type'),
                    'threshold' => $this->input('threshold'),
                    'partner_ids' => $this->input('partner_ids'),
                ]);
            },
        ];
    }
}
