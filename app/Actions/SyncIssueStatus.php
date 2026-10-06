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
        $status = $this->determineStatus($items->map(fn (IssueChecklistItem $item): array => [
            'is_required' => $item->is_required,
            'marks_issue_resolved' => $item->marks_issue_resolved,
            'is_completed' => $item->is_completed,
        ]), completeWithoutRequiredItems: true);

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

    /** @param iterable<array{is_required: bool, marks_issue_resolved: bool, is_completed: bool}> $items */
    public function determineStatus(iterable $items, bool $completeWithoutRequiredItems = false): IssueStatus
    {
        $hasRequiredItems = false;
        $allRequiredItemsCompleted = true;
        $resolutionItemCompleted = false;
        foreach ($items as $item) {
            if ($item['is_required']) {
                $hasRequiredItems = true;
                $allRequiredItemsCompleted = $allRequiredItemsCompleted && $item['is_completed'];
            }
            $resolutionItemCompleted = $resolutionItemCompleted || ($item['marks_issue_resolved'] && $item['is_completed']);
        }

        return match (true) {
            ($hasRequiredItems || $completeWithoutRequiredItems) && $allRequiredItemsCompleted => IssueStatus::Completed,
            $resolutionItemCompleted => IssueStatus::Handling,
            default => IssueStatus::Open,
        };
    }
}
