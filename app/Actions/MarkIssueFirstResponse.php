<?php

namespace App\Actions;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkIssueFirstResponse
{
    public function handle(Issue $issue, User $actor): Issue
    {
        return DB::transaction(function () use ($actor, $issue): Issue {
            $issue = Issue::query()->lockForUpdate()->findOrFail($issue->id);

            if ($issue->first_responded_at !== null) {
                return $issue;
            }

            $issue->update(['first_responded_at' => now()]);
            $issue->activities()->create([
                'user_id' => $actor->id,
                'action' => 'first_response_recorded',
                'description' => 'Eerste reactie vastgelegd.',
            ]);

            return $issue->refresh();
        });
    }
}
