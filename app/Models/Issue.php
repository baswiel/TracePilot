<?php

namespace App\Models;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $project_id
 * @property string $title
 * @property string|null $description
 * @property IssuePriority $priority
 * @property IssueStatus $status
 * @property Carbon $reported_at
 * @property Carbon|null $resolved_at
 * @property Carbon|null $completed_at
 * @property int|null $team_member_id
 * @property int $created_by
 * @property int $checklist_total
 * @property int $checklist_completed_count
 * @property int $required_checklist_total
 * @property int $required_checklist_completed_count
 */
#[Fillable([
    'project_id',
    'title',
    'description',
    'priority',
    'status',
    'reported_at',
    'resolved_at',
    'completed_at',
    'team_member_id',
    'created_by',
])]
class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<TeamMember, $this>
     */
    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<IssueChecklistItem, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(IssueChecklistItem::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<IssueActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(IssueActivity::class)->latest('created_at');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => IssuePriority::class,
            'status' => IssueStatus::class,
            'reported_at' => 'datetime',
            'resolved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
