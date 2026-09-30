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
     * @param  array{title: string, description: string|null, priority: IssuePriority, team_member_id: int|null, knowledge_base_recorded: bool, is_trend: bool}  $attributes
     */
    public function handle(Issue $issue, User $actor, array $attributes): Issue
    {
        return DB::transaction(function () use ($actor, $attributes, $issue): Issue {
            $issue = Issue::query()->lockForUpdate()->findOrFail($issue->id);
            $issue->fill($attributes);
            $changes = array_keys($issue->getDirty());
            $timestampChanges = collect(['reported_at', 'first_responded_at', 'resolved_at'])
                ->filter(fn (string $field): bool => in_array($field, $changes, true))
                ->mapWithKeys(fn (string $field): array => [$field => [
                    'from' => $issue->getOriginal($field),
                    'to' => $issue->getAttribute($field)?->toDateTimeString(),
                ]])
                ->all();

            if ($changes === []) {
                return $issue;
            }

            $issue->save();
            $issue->activities()->create([
                'user_id' => $actor->id,
                'action' => 'issue_updated',
                'description' => 'Issuegegevens bijgewerkt.',
                'metadata' => [
                    'changed_fields' => $changes,
                    'timestamp_changes' => $timestampChanges,
                ],
            ]);

            return $issue->refresh();
        });
    }
}
