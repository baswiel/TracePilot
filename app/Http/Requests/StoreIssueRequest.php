<?php

namespace App\Http\Requests;

use App\Enums\IssuePriority;
use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Issue::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where('is_active', true),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::enum(IssuePriority::class)],
            'reported_at' => ['required', 'date'],
            'team_member_id' => ['nullable', 'integer', Rule::exists('team_members', 'id')],
        ];
    }

    /**
     * @return array{title: string, description: string|null, priority: IssuePriority, reported_at: Carbon, team_member_id: int|null}
     */
    public function issueAttributes(): array
    {
        $validated = $this->validated();

        return [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => IssuePriority::from($validated['priority']),
            'reported_at' => Carbon::parse($validated['reported_at']),
            'team_member_id' => $validated['team_member_id'] ?? null,
        ];
    }
}
