<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Services\ReportService;

class TemplateController extends Controller
{
    /** Task rows for a service type, used when the coordinator switches type in the form. */
    public function tasks(int $serviceType, ReportService $service)
    {
        return response()->json($service->tasksFor($serviceType));
    }
}
