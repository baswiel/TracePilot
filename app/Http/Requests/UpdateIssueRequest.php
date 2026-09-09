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
        ];
    }

    /**
     * @return array{title: string, description: string|null, priority: IssuePriority, team_member_id: int|null}
     */
    public function issueAttributes(): array
    {
        $validated = $this->validated();

        return [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => IssuePriority::from($validated['priority']),
            'team_member_id' => $validated['team_member_id'] ?? null,
        ];
    }
}
