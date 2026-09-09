<?php

namespace App\Models;

use Database\Factories\IssueChecklistItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $issue_id
 * @property string $name
 * @property bool $is_required
 * @property bool $marks_issue_resolved
 * @property bool $is_completed
 * @property bool $is_not_applicable
 * @property Carbon|null $completed_at
 * @property int|null $completed_by
 * @property int $sort_order
 */
#[Fillable([
    'issue_id',
    'name',
    'is_required',
    'marks_issue_resolved',
    'is_completed',
    'is_not_applicable',
    'completed_at',
    'completed_by',
    'sort_order',
])]
class IssueChecklistItem extends Model
{
    /** @use HasFactory<IssueChecklistItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Issue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'marks_issue_resolved' => 'boolean',
            'is_completed' => 'boolean',
            'is_not_applicable' => 'boolean',
            'completed_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }
}
