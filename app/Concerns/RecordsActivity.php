<?php

namespace App\Concerns;

use App\Contracts\HasActivityLog;
use App\Enums\ActivityEvent;
use App\Services\ActivityRecorder;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes every create, update and delete of the model to the activity log.
 *
 * Query builder updates and deletes skip model events, so records using this trait are always changed one by one.
 */
trait RecordsActivity
{
    /**
     * Attributes that hold the document number or SKU, in the order they are looked up.
     *
     * @var list<string>
     */
    protected static array $activityLabelAttributes = ['order_no', 'receipt_no', 'return_no', 'shipment_no', 'hold_no', 'transfer_no', 'take_no', 'requisition_no', 'sku'];

    /**
     * Register the model event listeners.
     */
    public static function bootRecordsActivity(): void
    {
        static::created(fn (HasActivityLog&Model $model) => app(ActivityRecorder::class)->record($model, ActivityEvent::Created));
        static::updated(fn (HasActivityLog&Model $model) => app(ActivityRecorder::class)->record($model, ActivityEvent::Updated));
        static::deleted(fn (HasActivityLog&Model $model) => app(ActivityRecorder::class)->record($model, ActivityEvent::Deleted));
    }

    /**
     * Get the product or document header the activity belongs to, the record itself by default.
     */
    public function activityDocument(): Model&HasActivityLog
    {
        return $this;
    }

    /**
     * Get the calculated attributes left out of the activity log.
     *
     * @return list<string>
     */
    public function activityIgnoredAttributes(): array
    {
        return [];
    }

    /**
     * Get the document number or SKU of the record.
     */
    public function activityLabel(): ?string
    {
        foreach (static::$activityLabelAttributes as $attribute) {
            $value = $this->getAttribute($attribute);

            if (is_string($value)) {
                return $value;
            }
        }

        return null;
    }
}
