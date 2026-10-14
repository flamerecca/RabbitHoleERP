<?php

namespace App\Http\Controllers;

use App\Models\InventoryMonthSnapshot;
use App\Models\Team;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryClosingController extends Controller
{
    /**
     * Close the inventory of the team for the month by calling the close_inventory_month procedure.
     *
     * The closing rules live in the database on purpose, see docs/architecture.md section 4.11.
     */
    public function store(Request $request, Team $team): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        try {
            DB::transaction(fn () => DB::statement('CALL close_inventory_month(?, ?, ?)', [$team->id, $validated['period'], $request->user()->id]));
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'RH001')) {
                throw ValidationException::withMessages(['period' => $this->procedureMessage($exception)]);
            }

            if (str_contains($exception->getMessage(), 'RH002')) {
                abort(409, $this->procedureMessage($exception));
            }

            throw $exception;
        }

        return $this->show($team, $validated['period'])->setStatusCode(201);
    }

    /**
     * Show the snapshot of a closed month, one row per warehouse and product, empty when the month had no stock.
     */
    public function show(Team $team, string $period): JsonResponse
    {
        abort_unless((bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period), 422, __('The period must be in the YYYY-MM format.'));

        $closing = DB::table('inventory_month_closings')->where('team_id', $team->id)->where('period', $period)->first();

        abort_if($closing === null, 404);

        $snapshots = InventoryMonthSnapshot::query()
            ->join('warehouses', 'warehouses.id', '=', 'inventory_month_snapshots.warehouse_id')
            ->join('products', 'products.id', '=', 'inventory_month_snapshots.product_id')
            ->where('inventory_month_snapshots.team_id', $team->id)
            ->where('inventory_month_snapshots.period', $period)
            ->orderBy('warehouses.code')
            ->orderBy('products.sku')
            ->get(['inventory_month_snapshots.*', 'warehouses.code as warehouse_code', 'products.sku as product_sku', 'products.name as product_name']);

        $columns = ['opening_quantity', 'opening_amount', 'in_quantity', 'in_amount', 'out_quantity', 'out_amount', 'closing_quantity', 'closing_amount'];

        return response()->json([
            'data' => $snapshots->map(fn (InventoryMonthSnapshot $snapshot) => [
                'warehouse_id' => $snapshot->warehouse_id,
                'warehouse_code' => $snapshot->getAttribute('warehouse_code'),
                'product_id' => $snapshot->product_id,
                'product_sku' => $snapshot->getAttribute('product_sku'),
                'product_name' => $snapshot->getAttribute('product_name'),
                ...collect($columns)->mapWithKeys(fn (string $column) => [$column => (float) $snapshot->getAttribute($column)])->all(),
                'average_cost' => (float) $snapshot->average_cost,
            ]),
            'meta' => [
                'period' => $period,
                'closed_at' => Carbon::parse($closing->closed_at)->toJSON(),
                'totals' => collect($columns)->mapWithKeys(fn (string $column) => [$column => round((float) $snapshots->sum($column), 4)])->all(),
            ],
        ]);
    }

    /**
     * Get the message the procedure raised after its error code.
     */
    protected function procedureMessage(QueryException $exception): string
    {
        preg_match('/RH\d{3}: (.+?)(?:\n|\s+CONTEXT:|\s+\(Connection:|$)/', $exception->getMessage(), $matches);

        return $matches[1] ?? $exception->getMessage();
    }
}
