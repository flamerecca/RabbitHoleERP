<?php

namespace App\Filament\Resources\ManufacturingOrders\Pages;

use App\Actions\Mrp\CancelManufacturingOrder;
use App\Actions\Mrp\CompleteManufacturingOrder;
use App\Actions\Mrp\ConfirmManufacturingOrder;
use App\Enums\ManufacturingOrderStatus;
use App\Filament\Resources\ManufacturingOrders\ManufacturingOrderResource;
use App\Filament\Support\ActivityLogAction;
use App\Filament\Support\DocumentActions;
use App\Filament\Support\TeamOptions;
use App\Models\ManufacturingOrder;
use Filament\Resources\Pages\ViewRecord;

class ViewManufacturingOrder extends ViewRecord
{
    protected static string $resource = ManufacturingOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActivityLogAction::make(),
            DocumentActions::transition('confirm', 'Confirm', ManufacturingOrder::class, [ManufacturingOrderStatus::Draft], fn (ManufacturingOrder $record) => app(ConfirmManufacturingOrder::class)->handle($record), page: 'view'),
            DocumentActions::transition('complete', 'Complete', ManufacturingOrder::class, [ManufacturingOrderStatus::Confirmed], fn (ManufacturingOrder $record) => app(CompleteManufacturingOrder::class)->handle($record, TeamOptions::user()), page: 'view'),
            DocumentActions::transition('cancel', 'Cancel document', ManufacturingOrder::class, [ManufacturingOrderStatus::Draft, ManufacturingOrderStatus::Confirmed], fn (ManufacturingOrder $record) => app(CancelManufacturingOrder::class)->handle($record), 'danger', page: 'view'),
        ];
    }
}
