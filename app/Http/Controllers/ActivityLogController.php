<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEvent;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ActivityLogController extends Controller
{
    /**
     * List the activity of the team, newest first.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'document_type' => ['nullable', 'string', 'max:50', 'required_with:document_id'],
            'document_id' => ['nullable', 'integer', 'required_with:document_type'],
            'causer_id' => ['nullable', 'integer'],
            'event' => ['nullable', Rule::enum(ActivityEvent::class)],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $activities = $team->activityLogs()
            ->with('causer')
            ->when($validated['document_type'] ?? null, fn ($query, string $type) => $query->where('c6', $type)->where('c7', $validated['document_id']))
            ->when($validated['causer_id'] ?? null, fn ($query, int|string $id) => $query->where('c2', $id))
            ->when($validated['event'] ?? null, fn ($query, string $event) => $query->where('c3', $event))
            ->when($validated['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('c10', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('c10', '<=', $date))
            ->orderByDesc('c10')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return ActivityLogResource::collection($activities);
    }

    /**
     * Show one activity.
     */
    public function show(Team $team, ActivityLog $activityLog): ActivityLogResource
    {
        return new ActivityLogResource($activityLog->load('causer'));
    }
}
