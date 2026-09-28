<?php

namespace App\Http\Requests;

use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssueTimelineEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $issue = $this->route('issue');

        return $issue instanceof Issue && $this->user()?->can('update', $issue) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['comment', 'decision'])],
            'body' => ['required', 'string', 'max:5000'],
            'mention_ids' => ['nullable', 'array'],
            'mention_ids.*' => ['integer', 'distinct', Rule::exists('team_members', 'id')],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,txt,log,csv,json,zip,png,jpg,jpeg,webp'],
        ];
    }
}
