<?php

namespace App\Actions;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\IssueChecklistItem;

class SyncIssueStatus
{
    /**
     * Recalculate an issue status from its checklist snapshot.
     */
    public function handle(Issue $issue): Issue
    {
        $items = $issue->checklistItems()->get();

        $resolutionItemCompleted = $items->contains(
            fn (IssueChecklistItem $item): bool => $item->marks_issue_resolved && $item->is_completed,
        );
        $allRequiredItemsCompleted = $items
            ->where('is_required', true)
            ->every(fn (IssueChecklistItem $item): bool => $item->is_completed);

        $status = match (true) {
            $allRequiredItemsCompleted => IssueStatus::Completed,
            $resolutionItemCompleted => IssueStatus::Handling,
            default => IssueStatus::Open,
        };

        $issue->fill([
            'status' => $status,
            'resolved_at' => $resolutionItemCompleted
                ? $issue->resolved_at ?? now()
                : null,
            'completed_at' => $status === IssueStatus::Completed
                ? $issue->completed_at ?? now()
                : null,
        ]);
        $issue->save();

        return $issue;
    }
}
