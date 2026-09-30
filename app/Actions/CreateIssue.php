<?php

namespace App\Actions;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\IssueChecklistTemplate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateIssue
{
    /** @var array<int> */
    private array $completedTemplateIds = [];

    private ?Carbon $checklistCompletionAt = null;

    private ?User $checklistCompletionActor = null;

    /**
     * Create an issue and snapshot the currently active checklist templates.
     *
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     priority: IssuePriority,
     *     reported_at?: Carbon,
     *     first_responded_at?: Carbon|null,
     *     resolved_at?: Carbon|null,
     *     status?: IssueStatus,
     *     resolution_summary?: string|null,
     *     cause?: \App\Enums\IssueCause|null,
     *     checklist_completed?: array<int>,
     *     internal_note?: string|null,
     *     is_historical?: bool,
     *     team_member_id?: int|null
     * }  $attributes
     */
    public function handle(Project $project, User $creator, array $attributes): Issue
    {
        $this->completedTemplateIds = $attributes['checklist_completed'] ?? [];
        $this->checklistCompletionAt = $attributes['resolved_at'] ?? null;
        $this->checklistCompletionActor = $creator;

        try {
            return DB::transaction(function () use ($attributes, $creator, $project): Issue {
                $issue = Issue::query()->create([
                    'project_id' => $project->id,
                    'title' => $attributes['title'],
                    'description' => $attributes['description'] ?? null,
                    'priority' => $attributes['priority'],
                    'status' => IssueStatus::Open,
                    'reported_at' => $attributes['reported_at'] ?? now(),
                    'first_responded_at' => $attributes['first_responded_at'] ?? null,
                    'resolved_at' => $attributes['resolved_at'] ?? null,
                    'completed_at' => ($attributes['status'] ?? IssueStatus::Open) === IssueStatus::Completed
                        ? $attributes['resolved_at'] ?? null
                        : null,
                    'resolution_summary' => $attributes['resolution_summary'] ?? null,
                    'cause' => $attributes['cause'] ?? null,
                    'team_member_id' => $attributes['team_member_id'] ?? null,
                    'created_by' => $creator->id,
                ]);

                $templates = IssueChecklistTemplate::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();

                $completedTemplateIds = $this->completedTemplateIds;

                $this->createChecklistItems($issue, $templates);

                $status = $this->statusFor($templates, $completedTemplateIds);
                $issue->update([
                    'status' => $status,
                    'resolved_at' => $status === IssueStatus::Open ? null : $issue->resolved_at,
                    'completed_at' => $status === IssueStatus::Completed ? $issue->completed_at : null,
                ]);

                $issue->activities()->create([
                    'user_id' => $creator->id,
                    'action' => 'issue_created',
                    'description' => ($attributes['is_historical'] ?? false)
                        ? 'Storing achteraf geregistreerd.'
                        : 'Issue aangemaakt.',
                    'metadata' => [
                        'checklist_item_count' => $templates->count(),
                        'is_historical' => $attributes['is_historical'] ?? false,
                    ],
                ]);

                if ($attributes['is_historical'] ?? false) {
                    $issue->activities()->create([
                        'user_id' => $creator->id,
                        'action' => 'issue_reported',
                        'description' => 'Storing gemeld.',
                        'created_at' => $issue->reported_at,
                        'metadata' => ['recorded_retrospectively' => true],
                    ]);

                    if ($issue->first_responded_at !== null) {
                        $issue->activities()->create([
                            'user_id' => $creator->id,
                            'action' => 'first_response_recorded',
                            'description' => 'Eerste reactie vastgelegd.',
                            'created_at' => $issue->first_responded_at,
                            'metadata' => ['recorded_retrospectively' => true],
                        ]);
                    }

                    if ($issue->resolved_at !== null) {
                        $issue->activities()->create([
                            'user_id' => $creator->id,
                            'action' => 'issue_resolved',
                            'description' => 'Storing technisch opgelost.',
                            'created_at' => $issue->resolved_at,
                            'metadata' => ['recorded_retrospectively' => true],
                        ]);
                    }

                    if (filled($attributes['internal_note'] ?? null)) {
                        $issue->activities()->create([
                            'user_id' => $creator->id,
                            'action' => 'note',
                            'description' => trim($attributes['internal_note']),
                            'metadata' => ['recorded_retrospectively' => true],
                        ]);
                    }
                }

                return $issue->load('checklistItems', 'activities');
            });
        } finally {
            $this->completedTemplateIds = [];
            $this->checklistCompletionAt = null;
            $this->checklistCompletionActor = null;
        }
    }

    /** @param  Collection<int, IssueChecklistTemplate>  $templates */
    protected function createChecklistItems(Issue $issue, Collection $templates): void
    {
        $issue->checklistItems()->createMany(
            $templates->map(function (IssueChecklistTemplate $template): array {
                $isCompleted = in_array($template->id, $this->completedTemplateIds, true);

                return [
                    'name' => $template->name,
                    'is_required' => $template->is_required,
                    'marks_issue_resolved' => $template->marks_issue_resolved,
                    'is_completed' => $isCompleted,
                    'completed_at' => $isCompleted ? $this->checklistCompletionAt : null,
                    'completed_by' => $isCompleted ? $this->checklistCompletionActor?->id : null,
                    'sort_order' => $template->sort_order,
                ];
            })->all(),
        );
    }

    /**
     * @param  Collection<int, IssueChecklistTemplate>  $templates
     * @param  array<int>  $completedTemplateIds
     */
    private function statusFor(Collection $templates, array $completedTemplateIds): IssueStatus
    {
        $resolutionItemCompleted = $templates->contains(
            fn (IssueChecklistTemplate $template): bool => $template->marks_issue_resolved
                && in_array($template->id, $completedTemplateIds, true),
        );
        $requiredTemplates = $templates->where('is_required', true);
        $allRequiredItemsCompleted = $requiredTemplates->isNotEmpty()
            && $requiredTemplates->every(
                fn (IssueChecklistTemplate $template): bool => in_array($template->id, $completedTemplateIds, true),
            );

        return match (true) {
            $allRequiredItemsCompleted => IssueStatus::Completed,
            $resolutionItemCompleted => IssueStatus::Handling,
            default => IssueStatus::Open,
        };
    }
}
