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

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'root_cause' => ['required', 'string', 'max:5000'],
            'impact' => ['required', 'string', 'max:5000'],
            'action_items' => ['nullable', 'array', 'max:20'],
            'action_items.*.id' => ['nullable', 'integer', Rule::exists('postmortem_action_items', 'id')],
            'action_items.*.title' => ['required', 'string', 'max:255'],
            'action_items.*.owner_team_member_id' => ['nullable', 'integer', Rule::exists('team_members', 'id')],
            'action_items.*.due_date' => ['nullable', 'date'],
            'action_items.*.is_completed' => ['required', 'boolean'],
        ];
    }
}
