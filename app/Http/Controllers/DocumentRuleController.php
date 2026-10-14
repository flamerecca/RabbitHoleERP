<?php

namespace App\Http\Controllers;

use App\Enums\DocumentRuleEvent;
use App\Http\Requests\StoreDocumentRuleRequest;
use App\Http\Requests\UpdateDocumentRuleRequest;
use App\Http\Resources\DocumentRuleResource;
use App\Models\DocumentRule;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class DocumentRuleController extends Controller
{
    /**
     * List the document rules of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'document_type' => ['nullable', Rule::in(['purchase_order', 'goods_receipt', 'sales_order', 'shipment'])],
            'event' => ['nullable', Rule::enum(DocumentRuleEvent::class)],
        ]);

        $rules = DocumentRule::query()
            ->where('team_id', $team->id)
            ->when($validated['document_type'] ?? null, fn ($query, string $type) => $query->where('document_type', $type))
            ->when($validated['event'] ?? null, fn ($query, string $event) => $query->where('event', $event))
            ->orderBy('document_type')
            ->orderBy('event')
            ->orderBy('sequence')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return DocumentRuleResource::collection($rules);
    }

    /**
     * Create a document rule.
     */
    public function store(StoreDocumentRuleRequest $request, Team $team): JsonResponse
    {
        $rule = DocumentRule::create([
            'is_active' => true,
            'sequence' => 10,
            ...$request->validated(),
            'team_id' => $team->id,
        ]);

        return (new DocumentRuleResource($rule))->response()->setStatusCode(201);
    }

    /**
     * Update the given document rule, changing only the provided fields.
     */
    public function update(UpdateDocumentRuleRequest $request, Team $team, DocumentRule $documentRule): DocumentRuleResource
    {
        $documentRule->update($request->validated());

        return new DocumentRuleResource($documentRule);
    }

    /**
     * Delete the given document rule.
     */
    public function destroy(Team $team, DocumentRule $documentRule): Response
    {
        $documentRule->delete();

        return response()->noContent();
    }
}
