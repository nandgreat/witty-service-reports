<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\ReportDocxBuilder;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function __construct(private ReportService $service) {}

    public function dashboard()
    {
        return Inertia::render('Admin/Dashboard', [
            'counts' => [
                'awaiting' => Report::where('status', Report::SUBMITTED)->count(),
                'approved' => Report::where('status', Report::APPROVED)->count(),
                'returned' => Report::where('status', Report::RETURNED)->count(),
                'drafts' => Report::where('status', Report::DRAFT)->count(),
                'coordinators' => User::where('role', User::ROLE_COORDINATOR)->where('is_active', true)->count(),
            ],
            'recent' => $this->rows(Report::with(['user', 'serviceType'])->latest('updated_at')->limit(8)->get()),
        ]);
    }

    public function index(Request $request)
    {
        $q = Report::with(['user', 'serviceType'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->service_type_id, fn ($q, $v) => $q->where('service_type_id', $v))
            ->when($request->user_id, fn ($q, $v) => $q->where('user_id', $v))
            ->latest('service_date')->latest('id');

        return Inertia::render('Admin/Reports/Index', [
            'reports' => $this->rows($q->get()),
            'filters' => $request->only('status', 'service_type_id', 'user_id'),
            'serviceTypes' => ServiceType::orderBy('name')->get(['id', 'name']),
            'coordinators' => User::where('role', User::ROLE_COORDINATOR)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Report $report)
    {
        return Inertia::render('Admin/Reports/Show', ['report' => $this->service->toArray($report)]);
    }

    public function review(Request $request, Report $report)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,return'],
            'admin_note' => ['nullable', 'string', 'max:2000', 'required_if:decision,return'],
        ]);
        $report->update([
            'status' => $data['decision'] === 'approve' ? Report::APPROVED : Report::RETURNED,
            'admin_note' => $data['admin_note'] ?? null,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', $data['decision'] === 'approve' ? 'Report approved.' : 'Report returned to the coordinator.');
    }

    public function download(Report $report, ReportDocxBuilder $builder)
    {
        $this->service->load($report);
        $path = tempnam(sys_get_temp_dir(), 'rpt').'.docx';
        $builder->build($report, $path);

        return response()->download($path, $builder->filename($report))->deleteFileAfterSend();
    }

    public function destroy(Report $report)
    {
        $report->delete();

        return redirect()->route('admin.reports.index')->with('success', 'Report deleted.');
    }

    private function rows($reports)
    {
        return $reports->map(fn (Report $r) => [
            'id' => $r->id,
            'service_type' => $r->serviceType->name,
            'service_date' => $r->service_date->format('D, M j, Y'),
            'prepared_by' => $r->prepared_by,
            'coordinator' => $r->user->name,
            'status' => $r->status,
            'total_viewers' => $r->totalViewers(),
        ])->values();
    }
}
