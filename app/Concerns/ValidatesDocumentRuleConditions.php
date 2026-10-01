<?php

namespace App\Concerns;

use App\Enums\DocumentRuleCondition;
use App\Enums\DocumentType;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

trait ValidatesDocumentRuleConditions
{
    /**
     * Validate that the threshold, partner list and document type fit the condition type.
     *
     * @param  array{document_type: ?string, condition_type: ?string, threshold: mixed, partner_ids: mixed}  $rule
     */
    protected function validateConditionFields(Validator $validator, Team $team, array $rule): void
    {
        $documentType = DocumentType::tryFrom((string) $rule['document_type']);
        $condition = DocumentRuleCondition::tryFrom((string) $rule['condition_type']);

        if ($documentType === null || $condition === null) {
            return;
        }

        if (! $condition->supports($documentType)) {
            $validator->errors()->add('condition_type', __('The condition cannot be used for this document type.'));
        }

        if ($condition->needsThreshold() && $rule['threshold'] === null) {
            $validator->errors()->add('threshold', __('The threshold field is required for this condition.'));
        }

        if (! $condition->needsThreshold() && $rule['threshold'] !== null) {
            $validator->errors()->add('threshold', __('The threshold field must be empty for this condition.'));
        }

        if ($condition !== DocumentRuleCondition::PartnerInList) {
            if ($rule['partner_ids'] !== null) {
                $validator->errors()->add('partner_ids', __('The partner ids field must be empty for this condition.'));
            }

            return;
        }

        $partnerIds = is_array($rule['partner_ids']) ? array_values(array_unique($rule['partner_ids'])) : [];

        if ($partnerIds === []) {
            $validator->errors()->add('partner_ids', __('The partner ids field is required for this condition.'));

            return;
        }

        $table = in_array($documentType, [DocumentType::PurchaseOrder, DocumentType::GoodsReceipt], true) ? 'suppliers' : 'customers';
        $found = DB::table($table)->where('team_id', $team->id)->whereIn('id', $partnerIds)->count();

        if ($found !== count($partnerIds)) {
            $validator->errors()->add('partner_ids', __('Every partner must be a :partner of the team.', ['partner' => $table === 'suppliers' ? 'supplier' : 'customer']));
        }
    }
}
