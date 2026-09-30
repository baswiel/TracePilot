<?php

namespace App\Http\Requests;

use App\Enums\IssueCause;
use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $issue = $this->route('issue');

        return $issue instanceof Issue && $this->user()?->can('update', $issue) === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'is_completed' => ['required', 'boolean'],
            'is_not_applicable' => ['sometimes', 'boolean'],
            'resolution_summary' => ['nullable', 'string', 'max:2000'],
            'cause' => ['nullable', Rule::enum(IssueCause::class)],
            'postmortem_required' => ['sometimes', 'boolean'],
        ];
    }
}
