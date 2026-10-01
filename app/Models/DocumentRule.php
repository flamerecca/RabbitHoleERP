<?php

namespace App\Models;

use App\Enums\DocumentRuleAction;
use App\Enums\DocumentRuleCondition;
use App\Enums\DocumentRuleEvent;
use App\Enums\DocumentType;
use Database\Factories\DocumentRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * 單據檢核規則，於指定時機檢核單據，成立時阻擋操作或附上提醒。
 *
 * @property int $id
 * @property int $team_id
 * @property DocumentType $document_type
 * @property DocumentRuleEvent $event
 * @property DocumentRuleCondition $condition_type
 * @property string|null $threshold
 * @property list<int>|null $partner_ids
 * @property DocumentRuleAction $action
 * @property string $message
 * @property bool $is_active
 * @property int $sequence
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'document_type', 'event', 'condition_type', 'threshold', 'partner_ids', 'action', 'message', 'is_active', 'sequence'])]
class DocumentRule extends Model
{
    /** @use HasFactory<DocumentRuleFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'event' => DocumentRuleEvent::class,
            'condition_type' => DocumentRuleCondition::class,
            'threshold' => 'decimal:4',
            'partner_ids' => 'array',
            'action' => DocumentRuleAction::class,
            'is_active' => 'boolean',
            'sequence' => 'integer',
        ];
    }
}
