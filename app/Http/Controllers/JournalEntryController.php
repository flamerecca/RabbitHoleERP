<?php

namespace App\Http\Controllers;

use App\Enums\JournalEntryStatus;
use App\Http\Resources\JournalEntryResource;
use App\Models\JournalEntry;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class JournalEntryController extends Controller
{
    /**
     * List the journal entries of the team, posted by confirmed documents and payments.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(JournalEntryStatus::class)],
            'journal_id' => ['nullable', 'integer'],
            'reference_type' => ['nullable', 'string'],
            'reference_id' => ['nullable', 'integer'],
        ]);

        $entries = $team->journalEntries()
            ->with('lines')
            ->when($validated['status'] ?? null, fn ($query, string $value) => $query->where('status', $value))
            ->when($validated['journal_id'] ?? null, fn ($query, int|string $value) => $query->where('journal_id', $value))
            ->when($validated['reference_type'] ?? null, fn ($query, string $value) => $query->where('reference_type', $value))
            ->when($validated['reference_id'] ?? null, fn ($query, int|string $value) => $query->where('reference_id', $value))
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return JournalEntryResource::collection($entries);
    }

    /**
     * Show the journal entry with its lines.
     */
    public function show(Team $team, JournalEntry $journalEntry): JournalEntryResource
    {
        return new JournalEntryResource($journalEntry->load('lines'));
    }
}
