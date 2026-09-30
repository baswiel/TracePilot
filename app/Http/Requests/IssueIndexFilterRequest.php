<?php

namespace App\Http\Requests;

use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;

class IssueIndexFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Issue::class) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'project' => ['nullable', 'integer', 'exists:projects,id'],
            'customer' => ['nullable', 'integer', 'exists:customers,id'],
            'priority' => ['nullable', 'in:p1,p2,p3,p4'],
            'status' => ['nullable', 'in:open,handling,completed'],
            'assigned_to' => ['nullable', 'integer', 'exists:team_members,id'],
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date', 'after_or_equal:from'],
            'sort' => ['nullable', 'in:priority,reported_at,last_activity'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
