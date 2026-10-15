<?php

namespace App\Http\Controllers;

use App\Actions\Sales\DeleteQuotationTemplate;
use App\Actions\Sales\SaveQuotationTemplate;
use App\Http\Requests\SaveQuotationTemplateRequest;
use App\Http\Resources\QuotationTemplateResource;
use App\Models\QuotationTemplate;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class QuotationTemplateController extends Controller
{
    /**
     * List the quotation templates of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $records = $team->quotationTemplates()
            ->with('items')
            ->when(isset($validated['is_active']), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return QuotationTemplateResource::collection($records);
    }

    /**
     * Create a quotation template.
     */
    public function store(SaveQuotationTemplateRequest $request, Team $team, SaveQuotationTemplate $save): JsonResponse
    {
        return new QuotationTemplateResource($save->handle($team, $request->validated()))->response()->setStatusCode(201);
    }

    /**
     * Show the quotation template.
     */
    public function show(Team $team, QuotationTemplate $quotationTemplate): QuotationTemplateResource
    {
        return new QuotationTemplateResource($quotationTemplate->load('items'));
    }

    /**
     * Update the given fields of the quotation template, replacing its lines when they are given.
     */
    public function update(SaveQuotationTemplateRequest $request, Team $team, QuotationTemplate $quotationTemplate, SaveQuotationTemplate $save): QuotationTemplateResource
    {
        return new QuotationTemplateResource($save->handle($team, $request->validated(), $quotationTemplate));
    }

    /**
     * Delete the quotation template.
     */
    public function destroy(Team $team, QuotationTemplate $quotationTemplate, DeleteQuotationTemplate $delete): Response
    {
        $delete->handle($quotationTemplate);

        return response()->noContent();
    }
}
