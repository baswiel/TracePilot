<?php

namespace App\Http\Requests;

use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class IssueReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Issue::class) ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'period' => ['nullable', 'in:week,month,year,custom'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            // Keep existing bookmarked report links working while the UI uses `to`.
            'until' => ['nullable', 'date', 'after_or_equal:from'],
            'project' => ['nullable', 'integer', 'exists:projects,id'],
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function ($validator): void {
            if ($this->input('period') === 'custom' && (! $this->filled('from') || ! $this->filled('to'))) {
                $validator->errors()->add('from', 'Kies een begin- en einddatum voor een aangepaste periode.');
            }
        }];
    }
}
