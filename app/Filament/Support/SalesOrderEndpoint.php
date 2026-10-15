<?php

namespace App\Filament\Support;

use App\Data\DocumentResult;
use App\Enums\TeamPermission;
use App\Http\Controllers\SalesOrderController;
use App\Models\SalesOrder;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

/**
 * Runs the sales order API endpoints for the admin panel, so both entries share the code of SalesOrderController.
 *
 * Sales orders deliberately have no domain actions, see docs/architecture/document-management.md section 4.
 */
class SalesOrderEndpoint
{
    /**
     * Call a SalesOrderController method with the given input and return the resulting order and its warnings.
     *
     * @param  array<string, mixed>  $input
     * @return DocumentResult<SalesOrder>
     */
    public static function call(string $method, Team $team, ?SalesOrder $salesOrder = null, array $input = []): DocumentResult
    {
        $permission = match ($method) {
            'store' => TeamPermission::CreateSalesOrder,
            'update' => TeamPermission::UpdateSalesOrder,
            'confirm' => TeamPermission::ConfirmSalesOrder,
            'cancel' => TeamPermission::CancelSalesOrder,
            'reserve', 'unreserve' => TeamPermission::ReserveSalesOrder,
            default => throw new LogicException("No team permission is mapped for the {$method} sales order endpoint."),
        };

        abort_unless(
            auth()->user()?->hasTeamPermission($team, $permission) ?? false,
            403,
            __('Your team role does not allow this action.'),
        );

        $request = Request::create('/', 'POST', $input);
        $request->setUserResolver(fn () => auth()->user());

        /** @var JsonResponse $response */
        $response = app()->call(SalesOrderController::class.'@'.$method, array_filter([
            'request' => $request,
            'team' => $team,
            'salesOrder' => $salesOrder,
        ]));

        /** @var array{data: array{id: int}, meta?: array{warnings?: list<string>}} $payload */
        $payload = $response->getData(true);

        return new DocumentResult(SalesOrder::query()->findOrFail($payload['data']['id']), $payload['meta']['warnings'] ?? []);
    }
}
