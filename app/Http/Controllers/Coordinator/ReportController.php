<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Models\Report;
use App\Models\ServiceType;
use App\Models\Worker;
use App\Services\ReportPdfBuilder;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function __construct(private ReportService $service) {}

    public function index(Request $request)
    {
        $reports = $request->user()->reports()->with('serviceType')->latest('service_date')->latest('id')->get()
            ->map(fn (Report $r) => [
                'id' => $r->id,
                'service_type' => $r->serviceType->name,
                'service_date' => $r->service_date->format('D, M j, Y'),
                'prepared_by' => $r->prepared_by,
                'status' => $r->status,
                'admin_note' => $r->admin_note,
                'total_viewers' => $r->totalViewers(),
                'editable' => $r->isEditableByCoordinator(),
            ]);

        return Inertia::render('Coordinator/Reports/Index', ['reports' => $reports]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Coordinator/Reports/Form', [
            'report' => $this->service->blank($request->user()),
            ...$this->options(),
        ]);
    }

    public function store(ReportRequest $request)
    {
        $report = $this->service->save(new Report(['user_id' => $request->user()->id, 'status' => Report::DRAFT]), $request->validated());

        return redirect()->route('coordinator.reports.show', $report)->with('success', 'Report saved as draft.');
    }

    public function show(Request $request, Report $report)
    {
        $this->own($request, $report);

        return Inertia::render('Coordinator/Reports/Show', [
            'report' => $this->service->toArray($report),
            'editable' => $report->isEditableByCoordinator(),
        ]);
    }

    public function edit(Request $request, Report $report)
    {
        $this->own($request, $report, editable: true);

        return Inertia::render('Coordinator/Reports/Form', ['report' => $this->service->toArray($report), ...$this->options()]);
    }

    public function update(ReportRequest $request, Report $report)
    {
        $this->own($request, $report, editable: true);
        $this->service->save($report, $request->validated());

        return redirect()->route('coordinator.reports.show', $report)->with('success', 'Report updated.');
    }

    public function submit(Request $request, Report $report)
    {
        $this->own($request, $report, editable: true);
        $report->update(['status' => Report::SUBMITTED, 'submitted_at' => now()]);

        return redirect()->route('coordinator.reports.show', $report)->with('success', 'Report submitted to admin for review.');
    }

    public function duplicate(Request $request, Report $report)
    {
        $this->own($request, $report);
        $copy = $this->service->duplicate($report, $request->user());

        return redirect()->route('coordinator.reports.edit', $copy)->with('success', 'Report duplicated. Update the date, times and viewership.');
    }

    public function destroy(Request $request, Report $report)
    {
        $this->own($request, $report, editable: true);
        $report->delete();

        return redirect()->route('coordinator.reports.index')->with('success', 'Report deleted.');
    }

    public function download(Request $request, Report $report, ReportPdfBuilder $builder)
    {
        $this->own($request, $report);
        $this->service->load($report);
        $path = tempnam(sys_get_temp_dir(), 'rpt').'.pdf';
        $builder->build($report, $path);

        return response()->download($path, $builder->filename($report))->deleteFileAfterSend();
    }

    private function own(Request $request, Report $report, bool $editable = false): void
    {
        abort_unless($report->user_id === $request->user()->id, 403);
        abort_if($editable && ! $report->isEditableByCoordinator(), 403, 'This report has been submitted and is locked.');
    }

    private function options(): array
    {
        return [
            'serviceTypes' => ServiceType::where('is_active', true)->orderBy('id')
                ->get(['id', 'name', 'default_summary', 'default_conclusion']),
            'workers' => Worker::with('branch:id,name')->where('is_active', true)->orderBy('name')->get()
                ->map(fn ($w) => ['name' => $w->name, 'role' => $w->role, 'church_branch' => $w->branch?->name])->values(),
            'severities' => config('report.severities'),
            'incidentStatuses' => config('report.incident_statuses'),
            'broadcastStatuses' => config('report.broadcast_statuses'),
        ];
    }
}
