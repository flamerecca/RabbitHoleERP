<?php

namespace App\Http\Requests;

use App\Concerns\ValidatesDocumentRuleConditions;
use App\Enums\DocumentRuleAction;
use App\Enums\DocumentRuleCondition;
use App\Enums\DocumentRuleEvent;
use App\Models\DocumentRule;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDocumentRuleRequest extends FormRequest
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
            'document_type' => ['sometimes', 'required', Rule::in(['purchase_order', 'goods_receipt', 'sales_order', 'shipment'])],
            'event' => ['sometimes', 'required', Rule::enum(DocumentRuleEvent::class)],
            'condition_type' => ['sometimes', 'required', Rule::enum(DocumentRuleCondition::class)],
            'threshold' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'partner_ids' => ['sometimes', 'nullable', 'array'],
            'partner_ids.*' => ['integer'],
            'action' => ['sometimes', 'required', Rule::enum(DocumentRuleAction::class)],
            'message' => ['sometimes', 'required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'sequence' => ['sometimes', 'required', 'integer'],
        ];
    }

    /**
     * Get the "after" validation callables for the request, checking the merged rule.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $team = $this->route('team');
                $rule = $this->route('documentRule');

                if ($validator->errors()->isNotEmpty() || ! $team instanceof Team || ! $rule instanceof DocumentRule) {
                    return;
                }

                $this->validateConditionFields($validator, $team, [
                    'document_type' => $this->has('document_type') ? $this->input('document_type') : $rule->document_type->value,
                    'condition_type' => $this->has('condition_type') ? $this->input('condition_type') : $rule->condition_type->value,
                    'threshold' => $this->has('threshold') ? $this->input('threshold') : $rule->threshold,
                    'partner_ids' => $this->has('partner_ids') ? $this->input('partner_ids') : $rule->partner_ids,
                ]);
            },
        ];
    }
}
