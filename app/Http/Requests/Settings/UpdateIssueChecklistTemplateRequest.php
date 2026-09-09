<?php

namespace App\Http\Requests\Settings;

use App\Models\IssueChecklistTemplate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIssueChecklistTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('template');

        return $template instanceof IssueChecklistTemplate && $this->user()?->can('update', $template) === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_required' => ['required', 'boolean'],
            'marks_issue_resolved' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }

    /**
     * @return array{name: string, is_required: bool, marks_issue_resolved: bool, is_active: bool, sort_order: int}
     */
    public function templateAttributes(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'is_required' => $this->boolean('is_required'),
            'marks_issue_resolved' => $this->boolean('marks_issue_resolved'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->integer('sort_order'),
        ];
    }
}
