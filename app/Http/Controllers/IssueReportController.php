<?php

namespace App\Http\Controllers;

use App\Actions\BuildIssueReport;
use App\Http\Requests\IssueReportRequest;
use App\Models\Project;
use App\Support\IssueReportPeriod;
use Inertia\Inertia;
use Inertia\Response;

class IssueReportController extends Controller
{
    public function __invoke(IssueReportRequest $request, BuildIssueReport $buildIssueReport): Response
    {
        $validated = $request->validated();
        $period = IssueReportPeriod::fromRequest(
            $validated['period'] ?? null,
            $validated['from'] ?? null,
            $validated['to'] ?? $validated['until'] ?? null,
        );
        $projectId = isset($validated['project']) ? (int) $validated['project'] : null;

        return Inertia::render('Reports/Index', [
            'report' => $buildIssueReport->handle($period, $projectId),
            'filters' => [
                'period' => $period->key,
                'from' => $period->from->toDateString(),
                'to' => $period->until->toDateString(),
                'project' => $projectId ?? '',
            ],
            'period' => [
                'label' => $period->from->isoFormat('D MMMM YYYY').' t/m '.$period->until->isoFormat('D MMMM YYYY'),
                'from' => $period->from->toDateString(),
                'to' => $period->until->toDateString(),
            ],
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
