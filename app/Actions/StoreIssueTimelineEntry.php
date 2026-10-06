<?php

namespace App\Actions;

use App\Models\Issue;
use App\Models\IssueActivity;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class StoreIssueTimelineEntry
{
    /** @param array{type: string, body: string, mention_ids?: array<int>} $attributes */
    public function handle(Issue $issue, User $actor, array $attributes, ?UploadedFile $attachment = null): IssueActivity
    {
        $metadata = ['mentions' => TeamMember::query()
            ->whereIn('id', $attributes['mention_ids'] ?? [])
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn (TeamMember $member): array => ['id' => $member->id, 'name' => $member->name])->all()];
        $path = null;

        try {
            if ($attachment !== null) {
                $path = $attachment->store("issue-attachments/{$issue->id}", 'local');
                if ($path === false) {
                    throw new RuntimeException('Bijlage kan niet worden opgeslagen.');
                }
                $metadata['attachment'] = [
                    'path' => $path,
                    'name' => $attachment->getClientOriginalName(),
                    'mime_type' => $attachment->getMimeType(),
                ];
            }

            return DB::transaction(fn (): IssueActivity => $issue->activities()->create([
                'user_id' => $actor->id,
                'action' => $attributes['type'],
                'description' => $attributes['body'],
                'metadata' => $metadata,
            ]));
        } catch (Throwable $exception) {
            if (is_string($path)) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
    }
}
