<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_type_id' => ['required', 'exists:service_types,id'],
            'service_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'broadcast_status' => ['nullable', 'string', 'max:120'],
            'prepared_by' => ['nullable', 'string', 'max:120'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'conclusion' => ['nullable', 'string', 'max:5000'],
            'workspace_clean' => ['required', 'boolean'],
            'workspace_notes' => ['nullable', 'string', 'max:1000'],

            'observations' => ['array', 'max:30'],
            'observations.*.label' => ['nullable', 'string', 'max:80'],
            'observations.*.text' => ['required', 'string', 'max:1000'],

            'tasks' => ['array', 'max:60'],
            'tasks.*.number' => ['required', 'string', 'max:10'],
            'tasks.*.title' => ['required', 'string', 'max:200'],
            'tasks.*.handled_by' => ['nullable', 'string', 'max:200'],
            'tasks.*.remarks' => ['nullable', 'string', 'max:1000'],

            'viewership' => ['array', 'max:20'],
            'viewership.*.platform' => ['required', 'string', 'max:120'],
            'viewership.*.viewers' => ['nullable', 'integer', 'min:0'],

            'attendees' => ['array', 'max:100'],
            'attendees.*.name' => ['required', 'string', 'max:120'],
            'attendees.*.role' => ['nullable', 'string', 'max:60'],

            'incidents' => ['array', 'max:30'],
            'incidents.*.time' => ['nullable', 'string', 'max:20'],
            'incidents.*.incident' => ['required', 'string', 'max:300'],
            'incidents.*.severity' => ['nullable', 'string', 'max:30'],
            'incidents.*.action_taken' => ['nullable', 'string', 'max:1000'],
            'incidents.*.status' => ['nullable', 'string', 'max:30'],
        ];
    }
}
