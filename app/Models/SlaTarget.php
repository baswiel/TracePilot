<?php

namespace App\Models;

use App\Enums\IssuePriority;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property IssuePriority $priority
 * @property int $response_minutes
 * @property int $resolution_minutes
 */
#[Fillable(['priority', 'response_minutes', 'resolution_minutes'])]
class SlaTarget extends Model
{
    /**
     * @return BelongsTo<SlaLevel, $this>
     */
    public function slaLevel(): BelongsTo
    {
        return $this->belongsTo(SlaLevel::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['priority' => IssuePriority::class];
    }
}
