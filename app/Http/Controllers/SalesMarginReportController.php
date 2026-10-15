<?php

namespace App\Http\Controllers;

use App\Actions\Sales\BuildSalesMarginReport;
use App\Actions\Sales\RenderSalesMarginReportPdf;
use App\Http\Requests\SalesMarginReportRequest;
use App\Http\Resources\SalesMarginReportResource;
use App\Models\Team;
use Illuminate\Http\Response;

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

    /**
     * Download the same report as a PDF document.
     */
    public function pdf(SalesMarginReportRequest $request, Team $team, BuildSalesMarginReport $report, RenderSalesMarginReportPdf $render): Response
    {
        $criteria = $request->criteria();
        $result = $report->handle($team, $criteria['from'], $criteria['to'], $criteria['group_by'], $criteria['filters']);

        return response($render->handle($team, $criteria, $result), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$render->filename($criteria).'"',
        ]);
    }
}
