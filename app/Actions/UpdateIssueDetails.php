<?php

namespace App\Actions;

use App\Enums\IssuePriority;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateIssueDetails
{
    /**
     * Update editable issue details and record the change atomically.
     *
     * @param  array{title: string, description: string|null, priority: IssuePriority, team_member_id: int|null}  $attributes
     */
    public function handle(Issue $issue, User $actor, array $attributes): Issue
    {
        return DB::transaction(function () use ($actor, $attributes, $issue): Issue {
            $issue = Issue::query()->lockForUpdate()->findOrFail($issue->id);
            $issue->fill($attributes);
            $changes = array_keys($issue->getDirty());

            if ($changes === []) {
                return $issue;
            }

            $issue->save();
            $issue->activities()->create([
                'user_id' => $actor->id,
                'action' => 'issue_updated',
                'description' => 'Issuegegevens bijgewerkt.',
                'metadata' => ['changed_fields' => $changes],
            ]);

            return $issue->refresh();
        });
    }
}
