<?php

namespace App\Http\Controllers;

use App\Enums\IssueStatus;
use App\Http\Requests\ProjectIndexFilterRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectActiveRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Customer;
use App\Models\Issue;
use App\Models\Project;
use App\Models\SlaLevel;
use App\Models\SlaTarget;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(ProjectIndexFilterRequest $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $validated = $request->validated();

        $projects = Project::query()
            ->when(
                $validated['search'] ?? null,
                fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
                }),
            )
            ->when(
                ($validated['status'] ?? null) === 'active',
                fn ($query) => $query->where('is_active', true),
            )
            ->when(
                ($validated['status'] ?? null) === 'inactive',
                fn ($query) => $query->where('is_active', false),
            )
            ->withCount([
                'issues as active_issues_count' => fn ($query) => $query
                    ->where('status', '!=', IssueStatus::Completed->value),
            ])
            ->with('customer:id,name')
            ->withMax('issues', 'reported_at')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'customer_name' => $project->customerDisplayName(),
                'is_active' => $project->is_active,
                'active_issues_count' => $project->active_issues_count,
                'latest_issue_at' => $project->issues_max_reported_at,
            ]);

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'status' => $validated['status'] ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Project::class);

        return Inertia::render('Projects/Create', [
            'teamMembers' => $this->teamMembers(),
            'slaLevels' => $this->slaLevels(),
            'customers' => $this->customers(),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Project toegevoegd.',
        ]);

        return to_route('projects.show', $project);
    }

    public function show(Project $project): Response
    {
        $this->authorize('view', $project);
        $project->load(['slaLevel.targets', 'customer', 'firstResponder', 'secondResponder', 'thirdResponder']);

        $currentIssues = $project->issues()
            ->where('status', '!=', IssueStatus::Completed->value)
            ->latest('reported_at')
            ->limit(10)
            ->get();
        $completedIssues = $project->issues()
            ->where('status', IssueStatus::Completed->value)
            ->latest('completed_at')
            ->limit(5)
            ->get();

        return Inertia::render('Projects/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'customer_name' => $project->customerDisplayName(),
                'description' => $project->description,
                'sla_level' => $project->slaLevel === null ? null : [
                    'id' => $project->slaLevel->id,
                    'name' => $project->slaLevel->name,
                    'targets' => $project->slaLevel->targets->map(fn (SlaTarget $target): array => [
                        'priority' => $target->priority->value,
                        'response_minutes' => $target->response_minutes,
                        'resolution_minutes' => $target->resolution_minutes,
                    ]),
                ],
                'sla_first_response_minutes' => $project->sla_first_response_minutes,
                'sla_resolution_minutes' => $project->sla_resolution_minutes,
                'contact_name' => $project->contact_name,
                'contact_email' => $project->contact_email,
                'contact_phone' => $project->contact_phone,
                'first_responder' => $this->teamMemberData($project->firstResponder),
                'second_responder' => $this->teamMemberData($project->secondResponder),
                'third_responder' => $this->teamMemberData($project->thirdResponder),
                'is_active' => $project->is_active,
                'created_at' => $project->created_at->toDateTimeString(),
            ],
            'currentIssues' => $currentIssues->map(fn ($issue): array => $this->issueData($issue)),
            'completedIssues' => $completedIssues->map(fn ($issue): array => $this->issueData($issue)),
        ]);
    }

    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);

        return Inertia::render('Projects/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'customer_id' => $project->customer_id,
                'description' => $project->description,
                'sla_level_id' => $project->sla_level_id,
                'sla_first_response_minutes' => $project->sla_first_response_minutes,
                'sla_resolution_minutes' => $project->sla_resolution_minutes,
                'contact_name' => $project->contact_name,
                'contact_email' => $project->contact_email,
                'contact_phone' => $project->contact_phone,
                'first_responder_id' => $project->first_responder_id,
                'second_responder_id' => $project->second_responder_id,
                'third_responder_id' => $project->third_responder_id,
                'is_active' => $project->is_active,
            ],
            'teamMembers' => $this->teamMembers(),
            'slaLevels' => $this->slaLevels(),
            'customers' => $this->customers(),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Project bijgewerkt.',
        ]);

        return to_route('projects.show', $project);
    }

    public function updateActive(UpdateProjectActiveRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $project->is_active
                ? 'Project geactiveerd.'
                : 'Project gearchiveerd.',
        ]);

        return back();
    }

    /**
     * @return array<string, int|string|null>
     */
    private function issueData(Issue $issue): array
    {
        return [
            'id' => $issue->id,
            'title' => $issue->title,
            'priority' => $issue->priority->value,
            'status' => $issue->status->value,
            'reported_at' => $issue->reported_at->toDateTimeString(),
            'completed_at' => $issue->completed_at?->toDateTimeString(),
            'duration_minutes' => $issue->completed_at === null
                ? null
                : (int) abs($issue->reported_at->diffInMinutes($issue->completed_at)),
        ];
    }

    /**
     * @return Collection<int, TeamMember>
     */
    private function teamMembers(): Collection
    {
        return TeamMember::query()->orderBy('name')->get(['id', 'name', 'email']);
    }

    /**
     * @return Collection<int, SlaLevel>
     */
    private function slaLevels(): Collection
    {
        return SlaLevel::query()->orderBy('name')->get(['id', 'name']);
    }

    /** @return Collection<int, Customer> */
    private function customers(): Collection
    {
        return Customer::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return array{id: int, name: string, email: string|null}|null
     */
    private function teamMemberData(?TeamMember $teamMember): ?array
    {
        if ($teamMember === null) {
            return null;
        }

        return [
            'id' => $teamMember->id,
            'name' => $teamMember->name,
            'email' => $teamMember->email,
        ];
    }
}
