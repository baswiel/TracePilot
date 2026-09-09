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
    /**
     * Create an issue and snapshot the currently active checklist templates.
     *
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     priority: IssuePriority,
     *     reported_at?: Carbon,
     *     team_member_id?: int|null
     * }  $attributes
     */
    public function handle(Project $project, User $creator, array $attributes): Issue
    {
        return DB::transaction(function () use ($attributes, $creator, $project): Issue {
            $issue = Issue::query()->create([
                'project_id' => $project->id,
                'title' => $attributes['title'],
                'description' => $attributes['description'] ?? null,
                'priority' => $attributes['priority'],
                'status' => IssueStatus::Open,
                'reported_at' => $attributes['reported_at'] ?? now(),
                'team_member_id' => $attributes['team_member_id'] ?? null,
                'created_by' => $creator->id,
            ]);

            $templates = IssueChecklistTemplate::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            $this->createChecklistItems($issue, $templates);

            $issue->activities()->create([
                'user_id' => $creator->id,
                'action' => 'issue_created',
                'description' => 'Issue aangemaakt.',
                'metadata' => ['checklist_item_count' => $templates->count()],
            ]);

            return $issue->load('checklistItems', 'activities');
        });
    }

    /**
     * @param  Collection<int, IssueChecklistTemplate>  $templates
     */
    protected function createChecklistItems(Issue $issue, Collection $templates): void
    {
        $issue->checklistItems()->createMany(
            $templates->map(fn (IssueChecklistTemplate $template): array => [
                'name' => $template->name,
                'is_required' => $template->is_required,
                'marks_issue_resolved' => $template->marks_issue_resolved,
                'is_completed' => false,
                'sort_order' => $template->sort_order,
            ])->all(),
        );
    }
}
