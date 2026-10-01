<?php

namespace App\Services;

use App\Contracts\HasActivityLog;
use App\Enums\ActivityEvent;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes the activity log entry of a model change, see docs/architecture/activity-log.md.
 */
class ActivityRecorder
{
    /**
     * Attributes never recorded.
     *
     * @var list<string>
     */
    protected const ALWAYS_IGNORED = ['id', 'created_at', 'updated_at'];

    /**
     * Record the change of the model, skipping updates that changed no recorded attribute.
     *
     * @param  Model&HasActivityLog  $model
     */
    public function record(Model $model, ActivityEvent $event): void
    {
        $changes = $this->changes($model, $event);

        if ($changes === []) {
            return;
        }

        $document = $model->activityDocument();

        ActivityLog::create([
            'c1' => $document->getAttribute('team_id'),
            'c2' => auth()->id(),
            'c3' => $event,
            'c4' => $model->getMorphClass(),
            'c5' => $model->getKey(),
            'c6' => $document->getMorphClass(),
            'c7' => $document->getKey(),
            'c8' => $document->activityLabel(),
            'c9' => $changes,
        ]);
    }

    /**
     * Get the old and new raw values of every recorded attribute the event touched.
     *
     * @param  Model&HasActivityLog  $model
     * @return array<string, array{old: mixed, new: mixed}>
     */
    protected function changes(Model $model, ActivityEvent $event): array
    {
        $ignored = [...self::ALWAYS_IGNORED, ...$model->activityIgnoredAttributes()];

        $attributes = match ($event) {
            ActivityEvent::Created, ActivityEvent::Deleted => $model->getAttributes(),
            ActivityEvent::Updated => $model->getChanges(),
        };

        $changes = [];

        foreach ($attributes as $attribute => $value) {
            if (in_array($attribute, $ignored, true)) {
                continue;
            }

            $changes[$attribute] = match ($event) {
                ActivityEvent::Created => ['old' => null, 'new' => $value],
                ActivityEvent::Updated => ['old' => $model->getRawOriginal($attribute), 'new' => $value],
                ActivityEvent::Deleted => ['old' => $value, 'new' => null],
            };
        }

        return $changes;
    }
}
