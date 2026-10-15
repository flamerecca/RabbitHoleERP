<?php

namespace App\Actions\Sales;

use App\Models\Team;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\File;

class RenderSalesMarginReportPdf
{
    /**
     * Render the report as a PDF document.
     *
     * @param  array{from: CarbonInterface, to: CarbonInterface, group_by: string}  $criteria
     * @param  array{rows: list<array<string, string|null>>, totals: array<string, string|null>}  $report
     */
    public function handle(Team $team, array $criteria, array $report): string
    {
        File::ensureDirectoryExists((string) config('dompdf.options.font_cache'));

        return Pdf::loadView('reports.sales-margin', [
            'team' => $team,
            'criteria' => $criteria,
            'report' => $report,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->output();
    }

    /**
     * Get the file name of the PDF for the period.
     *
     * @param  array{from: CarbonInterface, to: CarbonInterface}  $criteria
     */
    public function filename(array $criteria): string
    {
        return "sales-margin-{$criteria['from']->toDateString()}-{$criteria['to']->toDateString()}.pdf";
    }
}
