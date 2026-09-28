<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['issue_id', 'root_cause', 'impact', 'created_by'])]
class IssuePostmortem extends Model
{
    /** @return BelongsTo<Issue, $this> */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<PostmortemActionItem, $this> */
    public function actionItems(): HasMany
    {
        return $this->hasMany(PostmortemActionItem::class)->orderBy('sort_order');
    }
}
