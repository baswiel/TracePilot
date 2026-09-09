<?php

namespace App\Http\Requests;

use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;

class DashboardFilterRequest extends FormRequest
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
            'project' => ['nullable', 'integer', 'exists:projects,id'],
            'priority' => ['nullable', 'in:p1,p2,p3,p4'],
            'status' => ['nullable', 'in:open,handling'],
            'assigned_to' => ['nullable', 'integer', 'exists:team_members,id'],
        ];
    }
}
