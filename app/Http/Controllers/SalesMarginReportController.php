<?php

namespace App\Http\Controllers;

use App\Actions\Sales\BuildSalesMarginReport;
use App\Http\Requests\SalesMarginReportRequest;
use App\Http\Resources\SalesMarginReportResource;
use App\Models\Team;

class SalesMarginReportController extends Controller
{
    /**
     * Net the shipments of the period against their returns, grouped by one dimension.
     */
    public function show(SalesMarginReportRequest $request, Team $team, BuildSalesMarginReport $report): SalesMarginReportResource
    {
        $criteria = $request->criteria();
        $result = $report->handle($team, $criteria['from'], $criteria['to'], $criteria['group_by'], $criteria['filters']);

        return new SalesMarginReportResource([
            'criteria' => [
                'date_from' => $criteria['from']->toDateString(),
                'date_to' => $criteria['to']->toDateString(),
                'group_by' => $criteria['group_by'],
                ...$criteria['filters'],
            ],
            ...$result,
        ]);
    }
}
