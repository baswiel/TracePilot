<?php

namespace App\Http\Requests\Settings;

use App\Enums\IssuePriority;
use App\Models\SlaLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSlaLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SlaLevel::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:sla_levels,name'],
            'targets' => ['required', 'array', 'size:4'],
            'targets.*.priority' => ['required', 'distinct', Rule::enum(IssuePriority::class)],
            'targets.*.response_minutes' => ['required', 'integer', 'min:1', 'max:525600'],
            'targets.*.resolution_minutes' => ['required', 'integer', 'min:1', 'max:525600'],
        ];
    }
}
