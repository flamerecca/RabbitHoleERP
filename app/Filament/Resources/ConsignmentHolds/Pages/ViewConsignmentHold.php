<?php

namespace App\Filament\Resources\ConsignmentHolds\Pages;

use App\Actions\Sales\MarkConsignmentHoldPickedUp;
use App\Enums\ConsignmentHoldStatus;
use App\Filament\Resources\ConsignmentHolds\ConsignmentHoldResource;
use App\Filament\Support\ActivityLogAction;
use App\Filament\Support\DocumentActions;
use App\Models\ConsignmentHold;
use Filament\Resources\Pages\ViewRecord;

class ViewConsignmentHold extends ViewRecord
{
    protected static string $resource = ConsignmentHoldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActivityLogAction::make(),
            DocumentActions::transition('pickup', 'Mark as picked up', ConsignmentHold::class, [ConsignmentHoldStatus::Holding, ConsignmentHoldStatus::Overdue], fn (ConsignmentHold $record) => app(MarkConsignmentHoldPickedUp::class)->handle($record), page: 'view'),
        ];
    }
}
