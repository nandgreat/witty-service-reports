<?php

namespace App\Services;

use App\Models\Report;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Language;

class ReportDocxBuilder
{
    private string $brand;
    private string $tint;

    public function __construct(private ReportService $service)
    {
        $this->brand = config('report.brand_color');
        $this->tint = config('report.tint_color');
    }

    public function filename(Report $r): string
    {
        $short = preg_replace('/[^A-Za-z0-9]+/', '', $r->serviceType->short_name);

        return 'Witty_Inventions_Service_Report_-_'.$short.'_'.$r->service_date->format('M_d_Y').'.docx';
    }

    /** Writes the report to $path as .docx. */
    public function build(Report $report, string $path): void
    {
        $d = $this->service->toArray($report);
        $dept = config('report.department');

        $word = new PhpWord();
        $word->getSettings()->setThemeFontLang(new Language(Language::EN_GB));
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(10.5);
        $word->setDefaultParagraphStyle(['spaceAfter' => 120, 'spacing' => 276]);
        $word->addFontStyle('h1', ['bold' => true, 'size' => 14, 'color' => $this->brand]);

        $section = $word->addSection([
            'pageSizeW' => 11906, 'pageSizeH' => 16838,
            'marginTop' => 1134, 'marginBottom' => 1134, 'marginLeft' => 1134, 'marginRight' => 1134,
        ]);

        // Header / footer
        $header = $section->addHeader();
        $header->addText(
            sprintf('%s · SERVICE REPORT · %s · %s', $dept, mb_strtoupper($d['service_short']), mb_strtoupper($report->service_date->format('M j, Y'))),
            ['size' => 8, 'color' => '666666'],
            ['alignment' => Jc::CENTER]
        );
        $footer = $section->addFooter();
        $fp = $footer->addTextRun(['alignment' => Jc::CENTER]);
        $fp->addText(config('report.organisation').' · '.config('report.footer_label').' · Page ', ['size' => 8, 'color' => '666666']);
        $fp->addField('PAGE', [], [], null)->setFontStyle(['size' => 8, 'color' => '666666']);

        // Title block
        $logo = resource_path('logo.png');
        if (is_file($logo)) {
            $section->addImage($logo, ['width' => 54, 'height' => 53, 'alignment' => Jc::CENTER]);
        }
        $section->addText($dept, ['bold' => true, 'size' => 20, 'color' => $this->brand], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $section->addText('SERVICE REPORT', ['bold' => true, 'size' => 14, 'color' => '555555'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $section->addText(mb_strtoupper($d['service_type']), ['size' => 11, 'color' => '555555'], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);

        // Info table
        $tbl = $section->addTable(['borderSize' => 4, 'borderColor' => 'BBBBBB', 'cellMargin' => 80]);
        $info = [
            'Date' => $d['service_date_label'],
            'Service' => $d['service_type'],
            'Service start time' => $this->time($d['start_time']),
            'Service end time' => $this->time($d['end_time']),
            'Total duration' => $d['duration'],
            'Broadcast status' => $d['broadcast_status'],
            'Report prepared by' => $d['prepared_by'],
        ];
        foreach ($info as $label => $value) {
            $tbl->addRow();
            $tbl->addCell(2800, ['bgColor' => $this->tint])->addText($label, ['bold' => true, 'color' => $this->brand], ['spaceAfter' => 0]);
            $tbl->addCell(6840)->addText((string) $value, [], ['spaceAfter' => 0]);
        }

        // 1. Summary
        $this->heading($section, '1. Service Report Summary');
        foreach (preg_split('/\R{2,}|\R/', trim($d['summary'])) ?: [] as $para) {
            if (trim($para) !== '') {
                $section->addText(trim($para));
            }
        }

        // 2. Tasks
        $this->heading($section, '2. Service Tasks & Assignments');
        $this->table($section, ['#', 'Service task', 'Handled by', 'Output / remarks'], [700, 3600, 2000, 3340],
            array_map(fn ($t) => [$t['number'], $t['title'], $t['handled_by'], $t['remarks']], $d['tasks']));

        // 3. Viewership
        $this->heading($section, '3. Online Viewership');
        $rows = array_map(fn ($v) => [$v['platform'], $v['viewers'] === null ? '' : number_format($v['viewers'])], $d['viewership']);
        $rows[] = ['Total online viewership', number_format($d['total_viewers'])];
        $this->table($section, ['Platform', 'Viewers'], [6640, 3000], $rows, boldLast: true);

        // 4. Attendance
        $this->heading($section, '4. Workers’ Attendance');
        $section->addText(sprintf('Present: %d workers. Duties are listed under Service Tasks & Assignments.', count($d['attendees'])));
        foreach ($d['attendees'] as $a) {
            $section->addListItem($a['name'].($a['role'] ? ' ('.$a['role'].')' : ''), 0, null, null, ['spaceAfter' => 40]);
        }

        // 5. Observations
        $this->heading($section, '5. Operational Observations');
        foreach ($d['observations'] as $o) {
            $run = $section->addListItemRun(0, null, ['spaceAfter' => 60]);
            if (! empty($o['label'])) {
                $run->addText(rtrim($o['label'], '. ').'. ', ['bold' => true]);
            }
            $run->addText($o['text']);
        }

        // 6. Workspace
        $this->heading($section, '6. Workspace Status');
        $run = $section->addTextRun();
        $run->addText('Workspace left clean: ');
        $run->addText(match ($d['workspace_clean']) {
            true => 'Yes – the workspace is clean',
            false => 'No – the workspace is not clean',
            default => 'Not recorded',
        }, ['bold' => true, 'color' => $d['workspace_clean'] === false ? 'B42318' : $this->brand]);
        if (trim($d['workspace_notes']) !== '') {
            $section->addText('Remarks: '.trim($d['workspace_notes']));
        }

        // 7. Incidents
        $this->heading($section, '7. Incident Report');
        $section->addText('Incidents encountered during the service, the response taken and the current status.');
        $inc = array_map(fn ($i) => [$i['time'], $i['incident'], $i['severity'], $i['action_taken'], $i['status']], $d['incidents']);
        if (! $inc) {
            $inc = [['', 'No incidents recorded', '', '', '']];
        }
        $this->table($section, ['Time', 'Incident', 'Severity', 'Action taken', 'Status'], [900, 2500, 1200, 3240, 1800], $inc);

        // 8. Conclusion
        $this->heading($section, '8. Conclusion');
        foreach (preg_split('/\R+/', trim($d['conclusion'])) ?: [] as $para) {
            if (trim($para) !== '') {
                $section->addText(trim($para));
            }
        }

        \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save($path);
    }

    private function heading(Section $s, string $text): void
    {
        $s->addText($text, 'h1', ['spaceBefore' => 280, 'spaceAfter' => 100, 'keepNext' => true]);
    }

    private function table(Section $s, array $head, array $widths, array $rows, bool $boldLast = false): void
    {
        $t = $s->addTable(['borderSize' => 4, 'borderColor' => 'BBBBBB', 'cellMargin' => 70]);
        $t->addRow(null, ['tblHeader' => true]);
        foreach ($head as $i => $h) {
            $t->addCell($widths[$i], ['bgColor' => $this->brand])->addText($h, ['bold' => true, 'color' => 'FFFFFF', 'size' => 10], ['spaceAfter' => 0]);
        }
        $last = count($rows) - 1;
        foreach ($rows as $ri => $row) {
            $t->addRow();
            foreach ($row as $i => $cell) {
                $shade = ($boldLast && $ri === $last) ? ['bgColor' => $this->tint] : [];
                $t->addCell($widths[$i], $shade)->addText(
                    (string) ($cell ?? ''),
                    ['size' => 10, 'bold' => $boldLast && $ri === $last],
                    ['spaceAfter' => 0]
                );
            }
        }
    }

    private function time(?string $t): string
    {
        return $t ? date('g:i A', strtotime($t)) : '';
    }
}
