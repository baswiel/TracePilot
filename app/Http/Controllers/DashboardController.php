<?php

namespace App\Http\Controllers;

use App\Enums\IssueStatus;
use App\Http\Requests\DashboardFilterRequest;
use App\Models\Issue;
use App\Models\Project;
use App\Models\TeamMember;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(DashboardFilterRequest $request): Response
    {
        $this->authorize('viewAny', Issue::class);

        $validated = $request->validated();

        $currentIssues = Issue::query()
            ->whereIn('status', [IssueStatus::Open->value, IssueStatus::Handling->value])
            ->when(
                $validated['project'] ?? null,
                fn ($query, int $projectId) => $query->where('project_id', $projectId),
            )
            ->when(
                $validated['priority'] ?? null,
                fn ($query, string $priority) => $query->where('priority', $priority),
            )
            ->when(
                $validated['status'] ?? null,
                fn ($query, string $status) => $query->where('status', $status),
            )
            ->when(
                $validated['assigned_to'] ?? null,
                fn ($query, int $teamMemberId) => $query->where('team_member_id', $teamMemberId),
            )
            ->with(['project:id,name', 'teamMember:id,name'])
            ->withCount([
                'checklistItems as checklist_total',
                'checklistItems as checklist_completed_count' => fn ($query) => $query
                    ->where('is_completed', true),
                'checklistItems as required_checklist_total' => fn ($query) => $query
                    ->where('is_required', true),
                'checklistItems as required_checklist_completed_count' => fn ($query) => $query
                    ->where('is_required', true)
                    ->where('is_completed', true),
            ])
            ->orderByRaw("case priority when 'p1' then 1 when 'p2' then 2 else 3 end")
            ->orderBy('reported_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Issue $issue): array => [
                'id' => $issue->id,
                'project' => $issue->project->name,
                'title' => $issue->title,
                'priority' => $issue->priority->value,
                'status' => $issue->status->value,
                'reported_at' => $issue->reported_at->toDateTimeString(),
                'assigned_to' => $issue->teamMember?->name,
                'checklist_completed' => $issue->checklist_completed_count,
                'checklist_total' => $issue->checklist_total,
                'required_checklist_completed' => $issue->required_checklist_completed_count,
                'required_checklist_total' => $issue->required_checklist_total,
            ]);

        return Inertia::render('Dashboard', [
            'statistics' => [
                'open' => Issue::query()->where('status', IssueStatus::Open->value)->count(),
                'handling' => Issue::query()->where('status', IssueStatus::Handling->value)->count(),
                'completed_this_month' => Issue::query()
                    ->where('status', IssueStatus::Completed->value)
                    ->whereBetween('completed_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count(),
            ],
            'issues' => $currentIssues,
            'filters' => [
                'project' => isset($validated['project']) ? (int) $validated['project'] : '',
                'priority' => $validated['priority'] ?? '',
                'status' => $validated['status'] ?? '',
                'assigned_to' => isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : '',
            ],
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'teamMembers' => TeamMember::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
