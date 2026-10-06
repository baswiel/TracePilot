<?php

namespace App\Actions;

use App\Models\Issue;
use App\Models\IssuePostmortem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateIssuePostmortem
{
    /** @param array{root_cause: string, impact: string, action_items?: array<int, array{id?: int|null, title: string, owner_team_member_id?: int|null, due_date?: string|null, is_completed: bool}>|null} $attributes */
    public function handle(Issue $issue, User $actor, array $attributes): IssuePostmortem
    {
        return DB::transaction(function () use ($issue, $actor, $attributes): IssuePostmortem {
            $issue = Issue::query()->lockForUpdate()->findOrFail($issue->id);
            abort_unless($issue->postmortem_required, 404);
            $postmortem = $issue->postmortem()->firstOrNew();
            $postmortem->fill(['root_cause' => $attributes['root_cause'], 'impact' => $attributes['impact']]);
            if (! $postmortem->exists) {
                $postmortem->created_by = $actor->getKey();
            }
            $postmortem->save();
            $existingItems = $postmortem->actionItems()->lockForUpdate()->get()->keyBy('id');
            $keptItemIds = [];

            foreach ($attributes['action_items'] ?? [] as $sortOrder => $itemAttributes) {
                $item = isset($itemAttributes['id']) ? $existingItems->get($itemAttributes['id']) : null;
                abort_unless($item !== null || ! isset($itemAttributes['id']), 404);
                $item ??= $postmortem->actionItems()->make();
                $item->fill([
                    'title' => $itemAttributes['title'],
                    'owner_team_member_id' => $itemAttributes['owner_team_member_id'] ?? null,
                    'due_date' => $itemAttributes['due_date'] ?? null,
                    'completed_at' => $itemAttributes['is_completed'] ? ($item->completed_at ?? now()) : null,
                    'sort_order' => $sortOrder,
                ])->save();
                $keptItemIds[] = $item->id;
            }

            $postmortem->actionItems()->whereNotIn('id', $keptItemIds)->delete();
            $issue->activities()->create([
                'user_id' => $actor->id,
                'action' => 'postmortem_updated',
                'description' => 'Postmortem bijgewerkt.',
            ]);

            return $postmortem->load('actionItems');
        });
    }
}
