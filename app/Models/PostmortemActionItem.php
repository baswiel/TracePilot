<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** @property Carbon|null $due_date */
#[Fillable(['title', 'owner_team_member_id', 'due_date', 'completed_at', 'sort_order'])]
class PostmortemActionItem extends Model
{
    /** @return BelongsTo<IssuePostmortem, $this> */
    public function postmortem(): BelongsTo
    {
        return $this->belongsTo(IssuePostmortem::class, 'issue_postmortem_id');
    }

    /** @return BelongsTo<TeamMember, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'owner_team_member_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }
}
