<?php

namespace App\Http\Requests\Settings;

use App\Enums\IssuePriority;
use App\Models\SlaLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSlaLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $slaLevel = $this->route('slaLevel');

        return $slaLevel instanceof SlaLevel && $this->user()?->can('update', $slaLevel) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $slaLevel = $this->route('slaLevel');

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('sla_levels', 'name')->ignore($slaLevel),
            ],
            'targets' => ['required', 'array', 'size:4'],
            'targets.*.priority' => ['required', 'distinct', Rule::enum(IssuePriority::class)],
            'targets.*.response_minutes' => ['required', 'integer', 'min:1', 'max:525600'],
            'targets.*.resolution_minutes' => ['required', 'integer', 'min:1', 'max:525600'],
        ];
    }
}
