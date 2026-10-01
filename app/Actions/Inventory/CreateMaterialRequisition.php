<?php

namespace App\Actions\Inventory;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\MaterialRequisition;
use App\Models\Team;
use App\Models\User;
use App\Services\DocumentSequence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CreateMaterialRequisition
{
    public function __construct(protected DocumentSequence $documentSequence) {}

    /**
     * Create a draft material requisition.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Team $team, User $user, array $attributes): MaterialRequisition
    {
        return DB::transaction(function () use ($team, $user, $attributes) {
            $document = MaterialRequisition::create([
                'team_id' => $team->id,
                'warehouse_id' => $attributes['warehouse_id'],
                'requisition_no' => $this->documentSequence->next($team, DocumentType::MaterialRequisition, CarbonImmutable::parse($attributes['requisition_date'])),
                'requisition_date' => $attributes['requisition_date'],
                'purpose' => $attributes['purpose'],
                'requested_by' => $attributes['requested_by'],
                'status' => DocumentStatus::Draft,
                'created_by' => $user->id,
            ]);

            $document->items()->createMany($attributes['items']);

            return $document->fresh('items');
        });
    }
}
