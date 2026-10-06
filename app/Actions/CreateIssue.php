<?php

namespace App\Actions;

use App\Enums\IssueCause;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\IssueChecklistTemplate;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateIssue
{
    public function __construct(private readonly SyncIssueStatus $syncIssueStatus) {}

    /**
     * Create an issue and snapshot the currently active checklist templates.
     *
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     priority: IssuePriority,
     *     reported_at?: CarbonInterface,
     *     first_responded_at?: CarbonInterface|null,
     *     resolved_at?: CarbonInterface|null,
     *     status?: IssueStatus,
     *     resolution_summary?: string|null,
     *     cause?: IssueCause|null,
     *     checklist_completed?: array<int>,
     *     internal_note?: string|null,
     *     is_historical?: bool,
     *     team_member_id?: int|null
     * }  $attributes
     */
    public function handle(Project $project, User $creator, array $attributes): Issue
    {
        return DB::transaction(function () use ($attributes, $creator, $project): Issue {
            $project = Project::query()->lockForUpdate()->findOrFail($project->id);
            if (! $project->is_active) {
                throw ValidationException::withMessages(['project_id' => 'Een storing kan alleen voor een actief project worden gemeld.']);
            }
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

            $completedTemplateIds = $attributes['checklist_completed'] ?? [];

            $this->createChecklistItems($issue, $templates, $creator, $completedTemplateIds, $attributes['resolved_at'] ?? null);

            $status = $this->syncIssueStatus->determineStatus($templates->map(fn (IssueChecklistTemplate $template): array => [
                'is_required' => $template->is_required,
                'marks_issue_resolved' => $template->marks_issue_resolved,
                'is_completed' => in_array($template->id, $completedTemplateIds, true),
            ]));
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
    }

    /** @param Collection<int, IssueChecklistTemplate> $templates
     * @param  array<int>  $completedTemplateIds
     */
    protected function createChecklistItems(Issue $issue, Collection $templates, User $actor, array $completedTemplateIds, ?CarbonInterface $completedAt): void
    {
        $issue->checklistItems()->createMany(
            $templates->map(function (IssueChecklistTemplate $template) use ($actor, $completedTemplateIds, $completedAt): array {
                $isCompleted = in_array($template->id, $completedTemplateIds, true);

                return [
                    'name' => $template->name,
                    'is_required' => $template->is_required,
                    'marks_issue_resolved' => $template->marks_issue_resolved,
                    'is_completed' => $isCompleted,
                    'completed_at' => $isCompleted ? $completedAt : null,
                    'completed_by' => $isCompleted ? $actor->id : null,
                    'sort_order' => $template->sort_order,
                ];
            })->all(),
        );
    }
}
