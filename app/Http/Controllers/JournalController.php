<?php

namespace App\Http\Controllers;

use App\Actions\Accounting\DeleteJournal;
use App\Actions\Accounting\SaveJournal;
use App\Enums\JournalType;
use App\Http\Requests\SaveJournalRequest;
use App\Http\Resources\JournalResource;
use App\Models\Journal;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class JournalController extends Controller
{
    /**
     * List the journals of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'type' => ['nullable', Rule::enum(JournalType::class)],
        ]);

        $journals = $team->journals()
            ->when($validated['type'] ?? null, fn ($query, string $value) => $query->where('type', $value))
            ->orderBy('code')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return JournalResource::collection($journals);
    }

    /**
     * Create a journal.
     */
    public function store(SaveJournalRequest $request, Team $team, SaveJournal $save): JsonResponse
    {
        return new JournalResource($save->handle($team, $request->validated()))->response()->setStatusCode(201);
    }

    /**
     * Show the journal.
     */
    public function show(Team $team, Journal $journal): JournalResource
    {
        return new JournalResource($journal);
    }

    /**
     * Update the given fields of the journal.
     */
    public function update(SaveJournalRequest $request, Team $team, Journal $journal, SaveJournal $save): JournalResource
    {
        return new JournalResource($save->handle($team, $request->validated(), $journal));
    }

    /**
     * Delete the journal when nothing refers to it.
     */
    public function destroy(Team $team, Journal $journal, DeleteJournal $delete): Response
    {
        $delete->handle($journal);

        return response()->noContent();
    }
}
