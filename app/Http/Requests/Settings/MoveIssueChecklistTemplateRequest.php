<?php

namespace App\Http\Requests\Settings;

use App\Models\IssueChecklistTemplate;
use Illuminate\Foundation\Http\FormRequest;

class MoveIssueChecklistTemplateRequest extends FormRequest
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
        return ['direction' => ['required', 'in:up,down']];
    }

    public function direction(): string
    {
        return $this->string('direction')->toString();
    }
}
