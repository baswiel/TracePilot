<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Project::class) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sla_level_id' => ['nullable', 'integer', 'exists:sla_levels,id'],
            'sla_first_response_minutes' => ['nullable', 'integer', 'min:1', 'max:525600'],
            'sla_resolution_minutes' => ['nullable', 'integer', 'min:1', 'max:525600'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'first_responder_id' => ['required', 'integer', 'exists:team_members,id'],
            'second_responder_id' => [
                'required',
                'integer',
                'different:first_responder_id',
                'exists:team_members,id',
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
