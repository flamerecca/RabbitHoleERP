<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgingReportController extends Controller
{
    /**
     * Age the open receivables or payables of the team by days past due, one row per partner.
     *
     * The query is hand-written SQL on purpose, see docs/architecture.md section 4.10.
     */
    public function __invoke(Request $request, Team $team): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:receivable,payable'],
            'as_of' => ['nullable', 'date_format:Y-m-d'],
            'partner_id' => ['nullable', 'integer'],
        ]);

        $asOf = $validated['as_of'] ?? today()->toDateString();

        $rows = DB::select(<<<'SQL'
            SELECT
                l.partner_type,
                l.partner_id,
                CASE WHEN l.partner_type = 'customer' THEN c.code ELSE s.code END AS partner_code,
                CASE WHEN l.partner_type = 'customer' THEN c.name ELSE s.name END AS partner_name,
                ROUND(SUM(CASE WHEN (CAST(:as_of AS date) - l.due_date) <= 0
                    THEN CASE WHEN (a.account_type = 'receivable' AND l.debit > 0) OR (a.account_type = 'payable' AND l.credit > 0) THEN l.amount_residual ELSE -l.amount_residual END
                    ELSE 0 END), 4) AS current_amount,
                ROUND(SUM(CASE WHEN (CAST(:as_of AS date) - l.due_date) BETWEEN 1 AND 30
                    THEN CASE WHEN (a.account_type = 'receivable' AND l.debit > 0) OR (a.account_type = 'payable' AND l.credit > 0) THEN l.amount_residual ELSE -l.amount_residual END
                    ELSE 0 END), 4) AS days_1_30,
                ROUND(SUM(CASE WHEN (CAST(:as_of AS date) - l.due_date) BETWEEN 31 AND 60
                    THEN CASE WHEN (a.account_type = 'receivable' AND l.debit > 0) OR (a.account_type = 'payable' AND l.credit > 0) THEN l.amount_residual ELSE -l.amount_residual END
                    ELSE 0 END), 4) AS days_31_60,
                ROUND(SUM(CASE WHEN (CAST(:as_of AS date) - l.due_date) BETWEEN 61 AND 90
                    THEN CASE WHEN (a.account_type = 'receivable' AND l.debit > 0) OR (a.account_type = 'payable' AND l.credit > 0) THEN l.amount_residual ELSE -l.amount_residual END
                    ELSE 0 END), 4) AS days_61_90,
                ROUND(SUM(CASE WHEN (CAST(:as_of AS date) - l.due_date) > 90
                    THEN CASE WHEN (a.account_type = 'receivable' AND l.debit > 0) OR (a.account_type = 'payable' AND l.credit > 0) THEN l.amount_residual ELSE -l.amount_residual END
                    ELSE 0 END), 4) AS days_over_90,
                ROUND(SUM(CASE WHEN (a.account_type = 'receivable' AND l.debit > 0) OR (a.account_type = 'payable' AND l.credit > 0) THEN l.amount_residual ELSE -l.amount_residual END), 4) AS total
            FROM journal_entry_lines l
            INNER JOIN journal_entries je ON je.id = l.journal_entry_id
            INNER JOIN chart_of_accounts a ON a.id = l.account_id
            LEFT JOIN customers c ON l.partner_type = 'customer' AND c.id = l.partner_id
            LEFT JOIN suppliers s ON l.partner_type = 'supplier' AND s.id = l.partner_id
            WHERE je.team_id = :team_id
                AND je.status = 'posted'
                AND je.entry_date <= CAST(:as_of AS date)
                AND a.account_type = :type
                AND l.amount_residual > 0
                AND l.due_date IS NOT NULL
                AND (CAST(:partner_id AS bigint) IS NULL OR l.partner_id = CAST(:partner_id AS bigint))
            GROUP BY l.partner_type, l.partner_id, partner_code, partner_name
            ORDER BY partner_code
            SQL, [
            'as_of' => $asOf,
            'team_id' => $team->id,
            'type' => $validated['type'],
            'partner_id' => $validated['partner_id'] ?? null,
        ]);

        $data = [];
        $totals = ['current' => 0.0, 'days_1_30' => 0.0, 'days_31_60' => 0.0, 'days_61_90' => 0.0, 'days_over_90' => 0.0, 'total' => 0.0];

        foreach ($rows as $row) {
            $data[] = [
                'partner_type' => $row->partner_type,
                'partner_id' => (int) $row->partner_id,
                'partner_code' => $row->partner_code,
                'partner_name' => $row->partner_name,
                'current' => (float) $row->current_amount,
                'days_1_30' => (float) $row->days_1_30,
                'days_31_60' => (float) $row->days_31_60,
                'days_61_90' => (float) $row->days_61_90,
                'days_over_90' => (float) $row->days_over_90,
                'total' => (float) $row->total,
            ];

            $totals['current'] = round($totals['current'] + (float) $row->current_amount, 4);
            $totals['days_1_30'] = round($totals['days_1_30'] + (float) $row->days_1_30, 4);
            $totals['days_31_60'] = round($totals['days_31_60'] + (float) $row->days_31_60, 4);
            $totals['days_61_90'] = round($totals['days_61_90'] + (float) $row->days_61_90, 4);
            $totals['days_over_90'] = round($totals['days_over_90'] + (float) $row->days_over_90, 4);
            $totals['total'] = round($totals['total'] + (float) $row->total, 4);
        }

        return response()->json(['data' => $data, 'meta' => ['type' => $validated['type'], 'as_of' => $asOf, 'totals' => $totals]]);
    }
}
