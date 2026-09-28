<?php

namespace App\Http\Controllers;

use App\Actions\BuildIssueReport;
use App\Http\Requests\IssueReportRequest;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class IssueReportController extends Controller
{
    public function __invoke(IssueReportRequest $request, BuildIssueReport $buildIssueReport): Response
    {
        $validated = $request->validated();
        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : now()->startOfMonth();
        $until = isset($validated['until']) ? Carbon::parse($validated['until'])->endOfDay() : now()->endOfDay();
        $projectId = isset($validated['project']) ? (int) $validated['project'] : null;

        return Inertia::render('Reports/Index', [
            'report' => $buildIssueReport->handle($from, $until, $projectId),
            'filters' => [
                'from' => $from->toDateString(),
                'until' => $until->toDateString(),
                'project' => $projectId ?? '',
            ],
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
