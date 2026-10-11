<?php

namespace App\Services;

use App\Models\Platform;
use App\Models\Report;
use App\Models\ServiceType;
use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportService
{
    private const RELATIONS = ['tasks', 'viewership', 'attendees', 'incidents', 'user', 'serviceType'];

    /** Task template rows for one service type (global rows + rows scoped to it). */
    public function tasksFor(?int $serviceTypeId): array
    {
        return TaskTemplate::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('service_type_id')->when($serviceTypeId, fn ($q) => $q->orWhere('service_type_id', $serviceTypeId)))
            ->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn ($t) => ['number' => $t->number, 'title' => $t->title, 'handled_by' => '', 'remarks' => $t->default_remarks ?? ''])
            ->all();
    }

    /** Blank form payload for a new report. */
    public function blank(User $user, ?ServiceType $type = null): array
    {
        $type ??= ServiceType::where('is_active', true)->orderBy('id')->first();

        return [
            'id' => null,
            'service_type_id' => $type?->id,
            'service_date' => now()->toDateString(),
            'start_time' => '',
            'end_time' => '',
            'broadcast_status' => config('report.broadcast_statuses')[0],
            'prepared_by' => \App\Models\Worker::where('name', $user->name)->value('name') ?? '',
            'summary' => $type?->default_summary ?? '',
            'observations' => [],
            'workspace_clean' => null,
            'workspace_notes' => '',
            'conclusion' => $type?->default_conclusion ?? '',
            'tasks' => $this->tasksFor($type?->id),
            'viewership' => Platform::where('is_active', true)->orderBy('sort_order')->get()
                ->map(fn ($p) => ['platform' => $p->name, 'viewers' => ''])->all(),
            'attendees' => [],
            'incidents' => [],
            'status' => Report::DRAFT,
            'admin_note' => null,
        ];
    }

    public function load(Report $report): Report
    {
        return $report->load(self::RELATIONS);
    }

    /** Report -> array consumed by the React form and preview. */
    public function toArray(Report $r): array
    {
        $this->load($r);

        return [
            'id' => $r->id,
            'service_type_id' => $r->service_type_id,
            'service_type' => $r->serviceType?->name,
            'service_short' => $r->serviceType?->short_name,
            'service_date' => $r->service_date->toDateString(),
            'service_date_label' => $r->service_date->format('l, F jS, Y'),
            'start_time' => $r->start_time ?? '',
            'end_time' => $r->end_time ?? '',
            'duration' => $r->durationLabel(),
            'broadcast_status' => $r->broadcast_status ?? '',
            'prepared_by' => $r->prepared_by ?? '',
            'summary' => $r->summary ?? '',
            'observations' => $r->observations ?? [],
            'workspace_clean' => $r->workspace_clean,
            'workspace_notes' => $r->workspace_notes ?? '',
            'conclusion' => $r->conclusion ?? '',
            'tasks' => $r->tasks->map->only(['number', 'title', 'handled_by', 'remarks'])->values()->all(),
            'viewership' => $r->viewership->map(fn ($v) => ['platform' => $v->platform, 'viewers' => $v->viewers])->values()->all(),
            'total_viewers' => $r->totalViewers(),
            'attendees' => $r->attendees->map->only(['name', 'role'])->values()->all(),
            'incidents' => $r->incidents->map->only(['time', 'incident', 'severity', 'action_taken', 'status'])->values()->all(),
            'status' => $r->status,
            'admin_note' => $r->admin_note,
            'coordinator' => $r->user?->name,
            'updated_at' => $r->updated_at?->toDateTimeString(),
        ];
    }

    /** Persist validated form data (scalar fields + all child rows). */
    public function save(Report $report, array $data): Report
    {
        return DB::transaction(function () use ($report, $data) {
            $report->fill([
                'service_type_id' => $data['service_type_id'],
                'service_date' => $data['service_date'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'broadcast_status' => $data['broadcast_status'] ?? null,
                'prepared_by' => $data['prepared_by'] ?? null,
                'summary' => $data['summary'] ?? null,
                'observations' => array_values($data['observations'] ?? []),
                'workspace_clean' => $data['workspace_clean'],
                'workspace_notes' => $data['workspace_notes'] ?? null,
                'conclusion' => $data['conclusion'] ?? null,
            ])->save();

            $this->replace($report->tasks(), $data['tasks'] ?? [], fn ($r) => [
                'number' => $r['number'], 'title' => $r['title'],
                'handled_by' => $r['handled_by'] ?? null, 'remarks' => $r['remarks'] ?? null,
            ]);
            $this->replace($report->viewership(), $data['viewership'] ?? [], fn ($r) => [
                'platform' => $r['platform'], 'viewers' => $r['viewers'] === '' ? null : $r['viewers'],
            ]);
            $this->replace($report->attendees(), $data['attendees'] ?? [], fn ($r) => [
                'name' => $r['name'], 'role' => $r['role'] ?? null,
            ]);
            $this->replace($report->incidents(), $data['incidents'] ?? [], fn ($r) => [
                'time' => $r['time'] ?? null, 'incident' => $r['incident'] ?? null, 'severity' => $r['severity'] ?? null,
                'action_taken' => $r['action_taken'] ?? null, 'status' => $r['status'] ?? null,
            ]);

            return $report->unsetRelations();
        });
    }

    /** Copy structure + people from a previous report; clears day-specific figures. */
    public function duplicate(Report $source, User $user): Report
    {
        $this->load($source);
        $data = $this->toArray($source);
        $data['service_date'] = now()->toDateString();
        $data['start_time'] = $data['end_time'] = '';
        $data['viewership'] = array_map(fn ($v) => ['platform' => $v['platform'], 'viewers' => ''], $data['viewership']);
        $data['incidents'] = [];
        $data['observations'] = [];
        $data['workspace_clean'] = null;
        $data['workspace_notes'] = '';
        $data['tasks'] = array_map(fn ($t) => $t + [], $data['tasks']);

        $copy = new Report(['user_id' => $user->id, 'status' => Report::DRAFT]);

        return $this->save($copy, $data);
    }

    private function replace($relation, array $rows, \Closure $map): void
    {
        $relation->delete();
        foreach (array_values($rows) as $i => $row) {
            $relation->create($map($row) + ['position' => $i]);
        }
    }
}
