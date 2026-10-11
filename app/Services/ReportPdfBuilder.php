<?php

namespace App\Services;

use App\Models\Report;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReportPdfBuilder
{
    public function __construct(private ReportService $service)
    {
    }

    public function filename(Report $r): string
    {
        $short = preg_replace('/[^A-Za-z0-9]+/', '', $r->serviceType->short_name);

        return 'Witty_Inventions_Service_Report_-_'.$short.'_'.$r->service_date->format('M_d_Y').'.pdf';
    }

    /** Writes the report to $path as a PDF. */
    public function build(Report $report, string $path): void
    {
        $logo = resource_path('logo.png');

        $html = view('reports.pdf', [
            'd' => $this->service->toArray($report),
            'dept' => config('report.department'),
            'brand' => config('report.brand_color'),
            'tint' => config('report.tint_color'),
            'date' => $report->service_date->format('M j, Y'),
            'logo' => is_file($logo) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logo)) : null,
            'time' => fn (?string $t) => $t ? date('g:i A', strtotime($t)) : '',
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', false);

        $pdf = new Dompdf($options);
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->render();

        file_put_contents($path, $pdf->output());
    }
}
