<?php

namespace App\Actions;

use App\Enums\IssueCause;
use App\Models\Issue;
use App\Models\IssueChecklistItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateIssueChecklistItemCompletion
{
    public function __construct(private readonly SyncIssueStatus $syncIssueStatus) {}

    /**
     * Update a checklist item, its issue status, and its activity history atomically.
     */
    public function handle(
        IssueChecklistItem $item,
        bool $isCompleted,
        User $actor,
        bool $isNotApplicable = false,
        ?string $resolutionSummary = null,
        ?bool $postmortemRequired = null,
        ?IssueCause $cause = null,
    ): Issue {
        return DB::transaction(function () use ($actor, $cause, $isCompleted, $isNotApplicable, $item, $postmortemRequired, $resolutionSummary): Issue {
            $issue = Issue::query()
                ->lockForUpdate()
                ->findOrFail($item->issue_id);
            $item = IssueChecklistItem::query()
                ->where('issue_id', $issue->id)
                ->lockForUpdate()
                ->findOrFail($item->id);

            $wasCompleted = $item->is_completed;
            $wasNotApplicable = $item->is_not_applicable;
            $isNotApplicable = $isCompleted && $isNotApplicable;

            $updatesPostmortemRequirement = $item->marks_issue_resolved
                && $isCompleted
                && ! $isNotApplicable
                && $postmortemRequired !== null
                && $issue->postmortem_required !== $postmortemRequired;

            if ($wasCompleted === $isCompleted && $wasNotApplicable === $isNotApplicable && ! $updatesPostmortemRequirement) {
                return $issue->refresh();
            }

            $item->update([
                'is_completed' => $isCompleted,
                'is_not_applicable' => $isNotApplicable,
                'completed_at' => $isCompleted ? now() : null,
                'completed_by' => $isCompleted ? $actor->id : null,
            ]);

            if ($item->marks_issue_resolved) {
                $issue->update([
                    'resolution_summary' => $isCompleted && ! $isNotApplicable
                        ? (filled($resolutionSummary) ? trim($resolutionSummary) : null)
                        : null,
                    'cause' => $isCompleted && ! $isNotApplicable ? $cause : null,
                ]);

                if ($isCompleted && ! $isNotApplicable && $postmortemRequired !== null) {
                    $issue->update(['postmortem_required' => $postmortemRequired]);
                    $postmortemItems = IssueChecklistItem::query()
                        ->where('issue_id', $issue->id)
                        ->whereRaw('lower(name) like ?', ['%postmortem%'])
                        ->lockForUpdate()
                        ->get();

                    foreach ($postmortemItems as $postmortemItem) {
                        $postmortemItemWasCompleted = $postmortemItem->is_completed;
                        $postmortemItemWasNotApplicable = $postmortemItem->is_not_applicable;
                        $postmortemItem->update([
                            'is_completed' => ! $postmortemRequired,
                            'is_not_applicable' => ! $postmortemRequired,
                            'completed_at' => ! $postmortemRequired ? now() : null,
                            'completed_by' => ! $postmortemRequired ? $actor->id : null,
                        ]);

                        if ($postmortemItemWasCompleted !== ! $postmortemRequired || $postmortemItemWasNotApplicable !== ! $postmortemRequired) {
                            $issue->activities()->create([
                                'user_id' => $actor->id,
                                'action' => $postmortemRequired
                                    ? 'checklist_item_reopened'
                                    : 'checklist_item_not_applicable',
                                'description' => $postmortemRequired
                                    ? "Checklist-item '{$postmortemItem->name}' opnieuw geopend."
                                    : "Checklist-item '{$postmortemItem->name}' gemarkeerd als niet van toepassing.",
                                'metadata' => ['checklist_item_id' => $postmortemItem->id],
                            ]);
                        }
                    }
                }
            }

            $previousStatus = $issue->status;
            $issue = $this->syncIssueStatus->handle($issue);

            $issue->activities()->create([
                'user_id' => $actor->id,
                'action' => $isNotApplicable
                    ? 'checklist_item_not_applicable'
                    : ($isCompleted ? 'checklist_item_completed' : 'checklist_item_reopened'),
                'description' => $isNotApplicable
                    ? "Checklist-item '{$item->name}' gemarkeerd als niet van toepassing."
                    : ($isCompleted
                    ? "Checklist-item '{$item->name}' voltooid."
                    : "Checklist-item '{$item->name}' opnieuw geopend."),
                'metadata' => [
                    'checklist_item_id' => $item->id,
                    'was_completed' => $wasCompleted,
                    'was_not_applicable' => $wasNotApplicable,
                    'is_not_applicable' => $isNotApplicable,
                    'status_before' => $previousStatus->value,
                    'status_after' => $issue->status->value,
                ],
            ]);

            return $issue->refresh();
        });
    }
}
