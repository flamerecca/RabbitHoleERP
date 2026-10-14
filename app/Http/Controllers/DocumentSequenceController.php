<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Http\Requests\UpdateDocumentSequenceRequest;
use App\Http\Resources\DocumentSequenceResource;
use App\Models\DocumentSequence as DocumentSequenceRule;
use App\Models\Team;
use App\Services\DocumentSequence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentSequenceController extends Controller
{
    /**
     * List the effective numbering rule of every document type, filling in the defaults.
     */
    public function index(Team $team, DocumentSequence $documentSequence): AnonymousResourceCollection
    {
        $rules = DocumentSequenceRule::query()->where('team_id', $team->id)->get()->keyBy(fn (DocumentSequenceRule $rule) => $rule->document_type->value);

        $sequences = collect(DocumentType::cases())->map(function (DocumentType $documentType) use ($team, $rules, $documentSequence) {
            $rule = $rules->get($documentType->value)
                ?? new DocumentSequenceRule(['team_id' => $team->id, 'document_type' => $documentType, ...DocumentSequenceRule::defaultsFor($documentType)]);
            $rule->setAttribute('next_number_preview', $documentSequence->preview($team, $documentType, today()));

            return $rule;
        });

        return DocumentSequenceResource::collection($sequences);
    }

    /**
     * Create or replace the numbering rule of the document type.
     */
    public function update(UpdateDocumentSequenceRequest $request, Team $team, string $documentType, DocumentSequence $documentSequence): JsonResponse
    {
        $type = DocumentType::from($documentType);

        $rule = DocumentSequenceRule::query()->updateOrCreate(
            ['team_id' => $team->id, 'document_type' => $type],
            [
                'prefix' => (string) $request->validated('prefix'),
                'date_format' => $request->validated('date_format'),
                'separator' => (string) $request->validated('separator'),
                'padding' => $request->validated('padding'),
                'reset_period' => $request->validated('reset_period'),
                'is_customized' => true,
            ],
        );
        $rule->setAttribute('next_number_preview', $documentSequence->preview($team, $type, today()));

        return (new DocumentSequenceResource($rule))->response()->setStatusCode(200);
    }
}
