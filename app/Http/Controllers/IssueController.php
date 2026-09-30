<?php

namespace App\Http\Controllers;

use App\Actions\CalculateIssueSla;
use App\Actions\CreateIssue;
use App\Actions\MarkIssueFirstResponse;
use App\Actions\UpdateIssueChecklistItemCompletion;
use App\Actions\UpdateIssueDetails;
use App\Enums\IssueCause;
use App\Http\Requests\IssueIndexFilterRequest;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\StoreIssueTimelineEntryRequest;
use App\Http\Requests\UpdateIssueChecklistItemRequest;
use App\Http\Requests\UpdateIssuePostmortemRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Models\Customer;
use App\Models\Issue;
use App\Models\IssueActivity;
use App\Models\IssueChecklistItem;
use App\Models\IssueChecklistTemplate;
use App\Models\Project;
use App\Models\TeamMember;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IssueController extends Controller
{
    public function index(IssueIndexFilterRequest $request, CalculateIssueSla $calculateIssueSla): Response
    {
        $validated = $request->validated();

        $issuesQuery = $this->filteredIssues($validated)
            ->with(['project.slaLevel.targets', 'project.customer:id,name', 'teamMember:id,name'])
            ->withMax('activities', 'created_at')
            ->orderByRaw("case status when 'open' then 1 when 'handling' then 2 else 3 end");

        $direction = $validated['direction'] ?? 'asc';
        match ($validated['sort'] ?? 'priority') {
            'reported_at' => $issuesQuery->orderBy('reported_at', $direction),
            'last_activity' => $issuesQuery->orderBy('activities_max_created_at', $direction),
            default => $issuesQuery
                ->orderByRaw("case priority when 'p1' then 1 when 'p2' then 2 when 'p3' then 3 else 4 end {$direction}")
                ->orderBy('reported_at'),
        };

        $issues = $issuesQuery
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Issue $issue): array => [
                'id' => $issue->id,
                'project' => $issue->project->name,
                'customer' => $issue->project->customer?->name ?? $issue->project->customer_name,
                'title' => $issue->title,
                'priority' => $issue->priority->value,
                'status' => $issue->status->value,
                'reported_at' => $issue->reported_at->toDateTimeString(),
                'first_responded_at' => $issue->first_responded_at?->toDateTimeString(),
                'resolved_at' => $issue->resolved_at?->toDateTimeString(),
                'assigned_to' => $issue->teamMember?->name,
                'completed_at' => $issue->completed_at?->toDateTimeString(),
                'last_activity_at' => $issue->activities_max_created_at,
                'sla' => $calculateIssueSla->handle($issue),
            ]);

        return Inertia::render('Issues/Index', [
            'issues' => $issues,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'project' => isset($validated['project']) ? (int) $validated['project'] : '',
                'customer' => isset($validated['customer']) ? (int) $validated['customer'] : '',
                'priority' => $validated['priority'] ?? '',
                'status' => $validated['status'] ?? '',
                'assigned_to' => isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : '',
                'from' => $validated['from'] ?? '',
                'until' => $validated['until'] ?? '',
                'sort' => $validated['sort'] ?? 'priority',
                'direction' => $direction,
            ],
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'teamMembers' => TeamMember::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Download every issue matching the current filters as a Google Sheets-compatible CSV.
     */
    public function export(IssueIndexFilterRequest $request): StreamedResponse
    {
        $issues = $this->filteredIssues($request->validated())
            ->with([
                'project:id,customer_name,contact_name,first_responder_id,second_responder_id,third_responder_id',
                'project.firstResponder:id,name',
                'project.secondResponder:id,name',
                'project.thirdResponder:id,name',
                'teamMember:id,name',
                'checklistItems:id,issue_id,name,is_completed,is_not_applicable',
            ])
            ->latest('reported_at')
            ->get();

        return response()->streamDownload(function () use ($issues): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'Datum Incident', 'Klant', 'Contactpersoon', 'Betrokkenen vanuit Rapide',
                'Incident', 'Oplossing', 'Post Mortem verzonden',
                'Uitkomst opgenomen in Kennisbank', 'Trend?', 'Type storing',
            ]);

            foreach ($issues as $issue) {
                fputcsv($stream, [
                    $issue->reported_at->format('d-m-Y H:i'),
                    $issue->project->customer_name,
                    $issue->project->contact_name,
                    $this->rapideContacts($issue),
                    $issue->title,
                    $issue->resolution_summary,
                    $this->postMortemStatus($issue),
                    $issue->knowledge_base_recorded ? 'Ja' : 'Nee',
                    $issue->is_trend ? 'Ja' : 'Nee',
                    $issue->cause?->label(),
                ]);
            }

            fclose($stream);
        }, 'incidenten-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredIssues(array $filters): Builder
    {
        return Issue::query()
            ->when(
                $filters['search'] ?? null,
                fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('project', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                }),
            )
            ->when(
                $filters['project'] ?? null,
                fn ($query, int $projectId) => $query->where('project_id', $projectId),
            )
            ->when(
                $filters['customer'] ?? null,
                fn ($query, int $customerId) => $query->whereHas(
                    'project',
                    fn ($projectQuery) => $projectQuery->where('customer_id', $customerId),
                ),
            )
            ->when(
                $filters['priority'] ?? null,
                fn ($query, string $priority) => $query->where('priority', $priority),
            )
            ->when(
                $filters['status'] ?? null,
                fn ($query, string $status) => $query->where('status', $status),
            )
            ->when(
                $filters['assigned_to'] ?? null,
                fn ($query, int $teamMemberId) => $query->where('team_member_id', $teamMemberId),
            )
            ->when(
                $filters['from'] ?? null,
                fn ($query, string $from) => $query->where('reported_at', '>=', $from),
            )
            ->when(
                $filters['until'] ?? null,
                fn ($query, string $until) => $query->where('reported_at', '<', Carbon::parse($until)->addDay()),
            );
    }

    private function rapideContacts(Issue $issue): string
    {
        return collect([
            $issue->teamMember?->name,
            $issue->project->firstResponder?->name,
            $issue->project->secondResponder?->name,
            $issue->project->thirdResponder?->name,
        ])->filter()->unique()->join(', ');
    }

    private function postMortemStatus(Issue $issue): string
    {
        $postMortem = $issue->checklistItems->first(
            fn (IssueChecklistItem $item): bool => str_contains(mb_strtolower($item->name), 'postmortem'),
        );

        if ($postMortem === null || $postMortem->is_not_applicable) {
            return 'Niet van toepassing';
        }

        return $postMortem->is_completed ? 'Ja' : 'Nee';
    }

    public function create(): Response
    {
        $this->authorize('create', Issue::class);

        return Inertia::render('Issues/Report', [
            'projects' => Project::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'customer_name']),
            'checklistTemplates' => IssueChecklistTemplate::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'is_required', 'marks_issue_resolved']),
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

    public function show(Issue $issue, CalculateIssueSla $calculateIssueSla): Response
    {
        $this->authorize('view', $issue);

        $issue->load([
            'project.slaLevel.targets',
            'project.firstResponder',
            'project.secondResponder',
            'project.thirdResponder',
            'teamMember',
            'checklistItems.completedBy',
            'activities.user',
            'postmortem.actionItems.owner',
        ]);
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
                'first_responded_at' => $issue->first_responded_at?->format('d-m-Y H:i'),
                'elapsed_duration' => $this->elapsedDuration($issue),
                'resolved_at' => $issue->resolved_at?->format('d-m-Y H:i'),
                'resolution_summary' => $issue->resolution_summary,
                'cause' => $issue->cause?->value,
                'postmortem_required' => $issue->postmortem_required,
                'knowledge_base_recorded' => $issue->knowledge_base_recorded,
                'is_trend' => $issue->is_trend,
                'completed_at' => $issue->completed_at?->format('d-m-Y H:i'),
                'project' => [
                    'id' => $issue->project->id,
                    'name' => $issue->project->name,
                    'customer_name' => $issue->project->customer_name,
                    'first_responder' => $this->teamMemberData($issue->project->firstResponder),
                    'second_responder' => $this->teamMemberData($issue->project->secondResponder),
                    'third_responder' => $this->teamMemberData($issue->project->thirdResponder),
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
                'sla' => $calculateIssueSla->handle($issue),
                'postmortem' => $issue->postmortem === null ? null : [
                    'root_cause' => $issue->postmortem->root_cause,
                    'impact' => $issue->postmortem->impact,
                    'action_items' => $issue->postmortem->actionItems->map(fn ($item): array => [
                        'id' => $item->id,
                        'title' => $item->title,
                        'owner_team_member_id' => $item->owner_team_member_id,
                        'owner_name' => $item->owner?->name,
                        'due_date' => $item->due_date?->toDateString(),
                        'is_completed' => $item->completed_at !== null,
                    ]),
                ],
                'activities' => $issue->activities->map(fn ($activity): array => [
                    'id' => $activity->id,
                    'action' => $activity->action,
                    'description' => $activity->description,
                    'created_at' => $activity->created_at->toDateTimeString(),
                    'user' => $activity->user?->name,
                    'mentions' => ($activity->metadata ?? [])['mentions'] ?? [],
                    'attachment' => isset(($activity->metadata ?? [])['attachment'])
                        ? [
                            'name' => $activity->metadata['attachment']['name'],
                            'download_url' => route('issues.timeline.attachment.download', [$issue, $activity]),
                        ]
                        : null,
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

    public function storeTimelineEntry(StoreIssueTimelineEntryRequest $request, Issue $issue): RedirectResponse
    {
        $this->authorize('update', $issue);

        $validated = $request->validated();
        $mentions = TeamMember::query()
            ->whereIn('id', $validated['mention_ids'] ?? [])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (TeamMember $teamMember): array => [
                'id' => $teamMember->id,
                'name' => $teamMember->name,
            ])
            ->values()
            ->all();

        $metadata = ['mentions' => $mentions];

        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $path = $attachment->store("issue-attachments/{$issue->id}", 'local');

            $metadata['attachment'] = [
                'path' => $path,
                'name' => $attachment->getClientOriginalName(),
                'mime_type' => $attachment->getMimeType(),
            ];
        }

        $issue->activities()->create([
            'user_id' => $request->user()->id,
            'action' => $validated['type'],
            'description' => $validated['body'],
            'metadata' => $metadata,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $validated['type'] === 'decision' ? 'Besluit toegevoegd.' : 'Interne opmerking toegevoegd.',
        ]);

        return back();
    }

    public function updatePostmortem(UpdateIssuePostmortemRequest $request, Issue $issue): RedirectResponse
    {
        $this->authorize('update', $issue);
        abort_unless($issue->postmortem_required, 404);
        $validated = $request->validated();

        DB::transaction(function () use ($issue, $request, $validated): void {
            $issue = Issue::query()->lockForUpdate()->findOrFail($issue->id);
            $postmortem = $issue->postmortem()->firstOrNew();
            $postmortem->fill([
                'root_cause' => $validated['root_cause'],
                'impact' => $validated['impact'],
            ]);

            if (! $postmortem->exists) {
                $postmortem->created_by = $request->user()->id;
            }

            $postmortem->save();
            $existingItems = $postmortem->actionItems()->lockForUpdate()->get()->keyBy('id');
            $keptItemIds = [];

            foreach ($validated['action_items'] ?? [] as $sortOrder => $attributes) {
                $actionItem = isset($attributes['id'])
                    ? $existingItems->get($attributes['id'])
                    : null;

                abort_unless($actionItem !== null || ! isset($attributes['id']), 404);
                $actionItem ??= $postmortem->actionItems()->make();
                $actionItem->fill([
                    'title' => $attributes['title'],
                    'owner_team_member_id' => $attributes['owner_team_member_id'] ?? null,
                    'due_date' => $attributes['due_date'] ?? null,
                    'completed_at' => $attributes['is_completed']
                        ? ($actionItem->completed_at ?? now())
                        : null,
                    'sort_order' => $sortOrder,
                ]);
                $actionItem->save();
                $keptItemIds[] = $actionItem->id;
            }

            $postmortem->actionItems()->whereNotIn('id', $keptItemIds)->delete();
            $issue->activities()->create([
                'user_id' => $request->user()->id,
                'action' => 'postmortem_updated',
                'description' => 'Postmortem bijgewerkt.',
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Postmortem opgeslagen.',
        ]);

        return back();
    }

    public function downloadTimelineAttachment(Issue $issue, IssueActivity $activity): StreamedResponse
    {
        $this->authorize('view', $issue);
        abort_unless($activity->issue_id === $issue->id, 404);

        $attachment = $activity->metadata['attachment'] ?? null;
        abort_unless(is_array($attachment) && isset($attachment['path'], $attachment['name']), 404);
        abort_unless(Storage::disk('local')->exists($attachment['path']), 404);

        return Storage::disk('local')->download($attachment['path'], $attachment['name']);
    }

    public function markFirstResponse(Issue $issue, MarkIssueFirstResponse $markFirstResponse): RedirectResponse
    {
        $this->authorize('update', $issue);

        $markFirstResponse->handle($issue, request()->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Eerste reactie vastgelegd.',
        ]);

        return to_route('issues.show', $issue);
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
            $request->validated('resolution_summary'),
            $request->has('postmortem_required') ? $request->boolean('postmortem_required') : null,
            isset($request->validated()['cause']) ? IssueCause::from($request->validated('cause')) : null,
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
