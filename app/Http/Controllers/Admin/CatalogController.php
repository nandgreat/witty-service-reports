<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChurchBranch;
use App\Models\Platform;
use App\Models\ServiceType;
use App\Models\TaskTemplate;
use App\Models\Worker;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** One controller for the four admin-managed lists (service types, tasks, platforms, church branches, workers). */
class CatalogController extends Controller
{
    private function config(string $key): array
    {
        return match ($key) {
            'service-types' => [
                'model' => ServiceType::class, 'title' => 'Service types',
                'hint' => 'Types of service you report on. Add new ones any time; the default texts pre-fill new reports.',
                'order' => ['id'],
                'rules' => [
                    'name' => ['required', 'string', 'max:120'],
                    'short_name' => ['required', 'string', 'max:40'],
                    'default_summary' => ['nullable', 'string', 'max:5000'],
                    'default_conclusion' => ['nullable', 'string', 'max:5000'],
                    'is_active' => ['boolean'],
                ],
            ],
            'tasks' => [
                'model' => TaskTemplate::class, 'title' => 'Service tasks',
                'hint' => 'Rows pre-loaded in section 2 of every report. Leave service type empty to use a task for all services.',
                'order' => ['sort_order', 'id'],
                'rules' => [
                    'number' => ['required', 'string', 'max:10'],
                    'title' => ['required', 'string', 'max:200'],
                    'default_remarks' => ['nullable', 'string', 'max:500'],
                    'service_type_id' => ['nullable', 'exists:service_types,id'],
                    'sort_order' => ['nullable', 'integer', 'min:0'],
                    'is_active' => ['boolean'],
                ],
            ],
            'platforms' => [
                'model' => Platform::class, 'title' => 'Streaming platforms',
                'hint' => 'Rows pre-loaded in the online viewership table.',
                'order' => ['sort_order', 'id'],
                'rules' => [
                    'name' => ['required', 'string', 'max:120'],
                    'sort_order' => ['nullable', 'integer', 'min:0'],
                    'is_active' => ['boolean'],
                ],
            ],
            'church-branches' => [
                'model' => ChurchBranch::class, 'title' => 'Church branches',
                'hint' => 'Branches a worker can belong to. Pick one when adding or editing a worker.',
                'order' => ['sort_order', 'id'],
                'rules' => [
                    'name' => ['required', 'string', 'max:120'],
                    'sort_order' => ['nullable', 'integer', 'min:0'],
                    'is_active' => ['boolean'],
                ],
            ],
            'workers' => [
                'model' => Worker::class, 'title' => 'Workers',
                'hint' => 'Roster that coordinators pick from for attendance and task assignment.',
                'order' => ['name'],
                'rules' => [
                    'name' => ['required', 'string', 'max:120'],
                    'role' => ['nullable', 'string', 'max:60'],
                    'church_branch_id' => ['nullable', 'exists:church_branches,id'],
                    'is_active' => ['boolean'],
                ],
            ],
            default => abort(404),
        };
    }

    public function index(string $catalog)
    {
        $c = $this->config($catalog);
        $q = $c['model']::query();
        foreach ($c['order'] as $col) {
            $q->orderBy($col);
        }

        return Inertia::render('Admin/Catalog', [
            'catalog' => $catalog,
            'title' => $c['title'],
            'hint' => $c['hint'],
            'items' => $q->get(),
            'serviceTypes' => ServiceType::orderBy('id')->get(['id', 'name']),
            'branches' => $catalog === 'workers'
                ? ChurchBranch::orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'is_active'])
                : [],
        ]);
    }

    public function store(Request $request, string $catalog)
    {
        $c = $this->config($catalog);
        $c['model']::create($request->validate($c['rules']));

        return back()->with('success', 'Added.');
    }

    public function update(Request $request, string $catalog, int $id)
    {
        $c = $this->config($catalog);
        $c['model']::findOrFail($id)->update($request->validate($c['rules']));

        return back()->with('success', 'Saved.');
    }

    public function toggle(string $catalog, int $id)
    {
        $item = $this->config($catalog)['model']::findOrFail($id);
        $item->update(['is_active' => ! $item->is_active]);

        return back()->with('success', $item->is_active ? 'Enabled.' : 'Disabled. It no longer appears in new reports.');
    }

    public function destroy(string $catalog, int $id)
    {
        $c = $this->config($catalog);
        if ($catalog === 'service-types' && \App\Models\Report::where('service_type_id', $id)->exists()) {
            return back()->withErrors(['delete' => 'This service type has reports. Deactivate it instead of deleting.']);
        }
        if ($catalog === 'church-branches' && Worker::where('church_branch_id', $id)->exists()) {
            return back()->withErrors(['delete' => 'Workers belong to this branch. Disable it instead of deleting.']);
        }
        $c['model']::findOrFail($id)->delete();

        return back()->with('success', 'Removed. Existing reports are not affected.');
    }
}
