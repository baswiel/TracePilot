<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $name
 * @property string|null $customer_name
 * @property string|null $description
 * @property bool $is_active
 * @property int|null $first_responder_id
 * @property int|null $second_responder_id
 * @property int|null $sla_level_id
 * @property Carbon $created_at
 * @property int $active_issues_count
 * @property string|null $issues_max_reported_at
 */
#[Fillable([
    'name',
    'customer_id',
    'customer_name',
    'description',
    'sla_level_id',
    'sla_first_response_minutes',
    'sla_resolution_minutes',
    'contact_name',
    'contact_email',
    'contact_phone',
    'first_responder_id',
    'second_responder_id',
    'is_active',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * @return HasMany<Issue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<SlaLevel, $this>
     */
    public function slaLevel(): BelongsTo
    {
        return $this->belongsTo(SlaLevel::class);
    }

    /**
     * @return BelongsTo<TeamMember, $this>
     */
    public function firstResponder(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'first_responder_id');
    }

    /**
     * @return BelongsTo<TeamMember, $this>
     */
    public function secondResponder(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'second_responder_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
