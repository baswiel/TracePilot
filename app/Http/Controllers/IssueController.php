<?php

namespace App\Http\Controllers;

use App\Actions\CreateIssue;
use App\Actions\UpdateIssueChecklistItemCompletion;
use App\Actions\UpdateIssueDetails;
use App\Http\Requests\IssueIndexFilterRequest;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueChecklistItemRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Models\Issue;
use App\Models\IssueChecklistItem;
use App\Models\Project;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IssueController extends Controller
{
    public function index(IssueIndexFilterRequest $request): Response
    {
        $validated = $request->validated();

        $issues = Issue::query()
            ->when(
                $validated['search'] ?? null,
                fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('project', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                }),
            )
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
            ->latest('reported_at')
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
                'completed_at' => $issue->completed_at?->toDateTimeString(),
            ]);

        return Inertia::render('Issues/Index', [
            'issues' => $issues,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'project' => isset($validated['project']) ? (int) $validated['project'] : '',
                'priority' => $validated['priority'] ?? '',
                'status' => $validated['status'] ?? '',
                'assigned_to' => isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : '',
            ],
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'teamMembers' => TeamMember::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Issue::class);

        return Inertia::render('Issues/Report', [
            'projects' => Project::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'customer_name']),
            'teamMembers' => TeamMember::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function store(StoreIssueRequest $request, CreateIssue $createIssue): RedirectResponse
    {
        $project = Project::query()->findOrFail($request->integer('project_id'));
        $issue = $createIssue->handle($project, $request->user(), $request->issueAttributes());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Storing gemeld.',
        ]);

        return to_route('issues.show', $issue);
    }

    public function show(Issue $issue): Response
    {
        $this->authorize('view', $issue);

        $issue->load(['project', 'teamMember', 'checklistItems.completedBy', 'activities.user']);
        $checklistItems = $issue->checklistItems;
        $requiredItems = $checklistItems->where('is_required', true);

        return Inertia::render('Issues/Show', [
            'issue' => [
                'id' => $issue->id,
                'title' => $issue->title,
                'description' => $issue->description,
                'priority' => $issue->priority->value,
                'status' => $issue->status->value,
                'reported_at' => $issue->reported_at->toDateTimeString(),
                'reported_at_label' => $issue->reported_at->format('d-m-Y H:i'),
                'elapsed_duration' => $this->elapsedDuration($issue),
                'resolved_at' => $issue->resolved_at?->format('d-m-Y H:i'),
                'completed_at' => $issue->completed_at?->format('d-m-Y H:i'),
                'project' => [
                    'id' => $issue->project->id,
                    'name' => $issue->project->name,
                    'customer_name' => $issue->project->customer_name,
                ],
                'team_member_id' => $issue->team_member_id,
                'assigned_to_name' => $issue->teamMember?->name,
                'checklist_items' => $checklistItems->map(fn (IssueChecklistItem $item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'is_required' => $item->is_required,
                    'marks_issue_resolved' => $item->marks_issue_resolved,
                    'is_completed' => $item->is_completed,
                    'is_not_applicable' => $item->is_not_applicable,
                    'completed_at' => $item->completed_at?->format('d-m-Y H:i'),
                    'completed_by' => $item->completedBy?->name,
                ]),
                'checklist_progress' => [
                    'completed' => $checklistItems->where('is_completed', true)->count(),
                    'total' => $checklistItems->count(),
                    'required_completed' => $requiredItems->where('is_completed', true)->count(),
                    'required_total' => $requiredItems->count(),
                    'all_required_completed' => $requiredItems->every(
                        fn ($item): bool => $item->is_completed,
                    ),
                ],
                'activities' => $issue->activities->map(fn ($activity): array => [
                    'id' => $activity->id,
                    'action' => $activity->action,
                    'description' => $activity->description,
                    'created_at' => $activity->created_at->toDateTimeString(),
                    'user' => $activity->user?->name,
                ]),
            ],
            'teamMembers' => TeamMember::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function update(UpdateIssueRequest $request, Issue $issue, UpdateIssueDetails $updateIssue): RedirectResponse
    {
        $this->authorize('update', $issue);

        $updateIssue->handle($issue, $request->user(), $request->issueAttributes());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Issuegegevens bijgewerkt.',
        ]);

        return back();
    }

    public function updateChecklistItem(
        UpdateIssueChecklistItemRequest $request,
        Issue $issue,
        IssueChecklistItem $item,
        UpdateIssueChecklistItemCompletion $updateChecklistItem,
    ): RedirectResponse {
        $this->authorize('update', $issue);
        abort_unless($item->issue_id === $issue->id, 404);

        $updateChecklistItem->handle(
            $item,
            $request->boolean('is_completed'),
            $request->user(),
            $request->boolean('is_not_applicable'),
        );

        return back();
    }

    private function elapsedDuration(Issue $issue): string
    {
        $minutes = (int) abs($issue->reported_at->diffInMinutes(now()));
        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $remainingMinutes = $minutes % 60;

        return collect([
            $days > 0 ? "{$days} d" : null,
            $hours > 0 ? "{$hours} u" : null,
            "{$remainingMinutes} min",
        ])->filter()->join(' ');
    }
}
