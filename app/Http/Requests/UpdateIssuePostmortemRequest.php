<?php

namespace App\Http\Requests;

use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssuePostmortemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $issue = $this->route('issue');

        return $issue instanceof Issue && $this->user()?->can('update', $issue) === true;
    }

    /** @return array{root_cause: string, impact: string, action_items: array<int, array{id?: int|null, title: string, owner_team_member_id?: int|null, due_date?: string|null, is_completed: bool}>} */
    public function postmortemAttributes(): array
    {
        return ['root_cause' => $this->validated('root_cause'), 'impact' => $this->validated('impact'), 'action_items' => $this->validated('action_items') ?? []];
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $issue = $this->route('issue');
        $postmortemId = $issue instanceof Issue ? $issue->postmortem()->value('id') : null;

        return [
            'root_cause' => ['required', 'string', 'max:5000'],
            'impact' => ['required', 'string', 'max:5000'],
            'action_items' => ['nullable', 'array', 'max:20'],
            'action_items.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('postmortem_action_items', 'id')->where('issue_postmortem_id', $postmortemId ?? 0)],
            'action_items.*.title' => ['required', 'string', 'max:255'],
            'action_items.*.owner_team_member_id' => ['nullable', 'integer', Rule::exists('team_members', 'id')],
            'action_items.*.due_date' => ['nullable', 'date'],
            'action_items.*.is_completed' => ['required', 'boolean'],
        ];
    }
}
