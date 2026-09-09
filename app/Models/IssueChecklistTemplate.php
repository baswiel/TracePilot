<?php

namespace App\Models;

use Database\Factories\IssueChecklistTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'name',
    'is_required',
    'marks_issue_resolved',
    'is_active',
    'sort_order',
])]
class IssueChecklistTemplate extends Model
{
    /** @use HasFactory<IssueChecklistTemplateFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $template): void {
            if (! $template->is_active || ! $template->marks_issue_resolved) {
                return;
            }

            $hasActiveResolutionTemplate = static::query()
                ->where('is_active', true)
                ->where('marks_issue_resolved', true)
                ->when(
                    $template->exists,
                    fn ($query) => $query->whereKeyNot($template->getKey()),
                )
                ->exists();

            if ($hasActiveResolutionTemplate) {
                throw ValidationException::withMessages([
                    'marks_issue_resolved' => 'Er mag slechts één actief oplossingsitem zijn.',
                ]);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'marks_issue_resolved' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
