<?php

namespace App\Actions\Inventory;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Team;
use App\Models\User;
use App\Models\WarehouseTransfer;
use App\Services\DocumentSequence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateWarehouseTransfer
{
    public function __construct(protected DocumentSequence $documentSequence) {}

    /**
     * Create a draft transfer between two different warehouses of the team.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(Team $team, User $user, array $attributes): WarehouseTransfer
    {
        if ((int) $attributes['from_warehouse_id'] === (int) $attributes['to_warehouse_id']) {
            throw ValidationException::withMessages(['to_warehouse_id' => __('The destination warehouse must differ from the source warehouse.')]);
        }

        return DB::transaction(function () use ($team, $user, $attributes) {
            $document = WarehouseTransfer::create([
                'team_id' => $team->id,
                'from_warehouse_id' => $attributes['from_warehouse_id'],
                'to_warehouse_id' => $attributes['to_warehouse_id'],
                'transfer_no' => $this->documentSequence->next($team, DocumentType::WarehouseTransfer, CarbonImmutable::parse($attributes['transfer_date'])),
                'status' => DocumentStatus::Draft,
                'transfer_date' => $attributes['transfer_date'],
                'created_by' => $user->id,
            ]);

            $document->items()->createMany($attributes['items']);

            return $document->fresh('items');
        });
    }
}
