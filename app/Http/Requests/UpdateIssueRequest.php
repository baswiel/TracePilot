<?php

namespace App\Http\Requests;

use App\Enums\IssuePriority;
use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        $issue = $this->route('issue');

        return $issue instanceof Issue && $this->user()?->can('update', $issue) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::enum(IssuePriority::class)],
            'team_member_id' => ['nullable', 'integer', Rule::exists('team_members', 'id')],
            'knowledge_base_recorded' => ['required', 'boolean'],
            'is_trend' => ['required', 'boolean'],
            'reported_at' => ['required', 'date'],
            'first_responded_at' => ['nullable', 'date', 'after_or_equal:reported_at'],
            'resolved_at' => [
                'nullable',
                'date',
                'after_or_equal:reported_at',
                Rule::when($this->filled('first_responded_at'), ['after_or_equal:first_responded_at']),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function issueAttributes(): array
    {
        $validated = $this->validated();

        return [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => IssuePriority::from($validated['priority']),
            'team_member_id' => $validated['team_member_id'] ?? null,
            'knowledge_base_recorded' => $validated['knowledge_base_recorded'],
            'is_trend' => $validated['is_trend'],
            'reported_at' => $validated['reported_at'],
            'first_responded_at' => $validated['first_responded_at'] ?? null,
            'resolved_at' => $validated['resolved_at'] ?? null,
        ];
    }
}
