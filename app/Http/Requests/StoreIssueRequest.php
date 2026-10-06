<?php

namespace App\Http\Requests;

use App\Actions\SyncIssueStatus;
use App\Enums\IssueCause;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\IssueChecklistTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Issue::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where('is_active', true),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::enum(IssuePriority::class)],
            'reported_at' => ['required', 'date'],
            'is_historical' => ['sometimes', 'boolean'],
            'first_responded_at' => ['nullable', 'date', 'after_or_equal:reported_at'],
            'resolved_at' => [
                'nullable',
                'date',
                'after_or_equal:reported_at',
                Rule::when($this->filled('first_responded_at'), ['after_or_equal:first_responded_at']),
            ],
            'status' => ['nullable', Rule::enum(IssueStatus::class)],
            'resolution_summary' => ['nullable', 'string'],
            'cause' => ['nullable', Rule::enum(IssueCause::class)],
            'internal_note' => ['nullable', 'string'],
            'checklist_completed' => ['nullable', 'array'],
            'checklist_completed.*' => ['integer', Rule::exists('issue_checklist_templates', 'id')],
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function ($validator): void {
            if (! $this->boolean('is_historical')) {
                return;
            }

            $status = $this->input('status');
            $completedTemplateIds = array_map('intval', $this->input('checklist_completed', []));
            $templates = IssueChecklistTemplate::query()
                ->where('is_active', true)
                ->get(['id', 'is_required', 'marks_issue_resolved']);
            $resolutionTemplateId = $templates->firstWhere('marks_issue_resolved', true)?->id;

            $requestedStatus = is_string($status) ? IssueStatus::tryFrom($status) : null;

            if ($requestedStatus === null) {
                $validator->errors()->add('status', 'Kies een eindstatus voor de historische storing.');

                return;
            }

            if ($requestedStatus !== IssueStatus::Open && ! $this->filled('resolved_at')) {
                $validator->errors()->add('resolved_at', 'Vul in wanneer de storing technisch was opgelost.');
            }

            if ($requestedStatus !== IssueStatus::Open && ! in_array($resolutionTemplateId, $completedTemplateIds, true)) {
                $validator->errors()->add('checklist_completed', 'Vink het oplossingsitem aan om deze status vast te leggen.');
            }

            $requiredTemplateIds = $templates->where('is_required', true)->pluck('id');
            $derivedStatus = app(SyncIssueStatus::class)->determineStatus($templates->map(fn ($template): array => [
                'is_required' => $template->is_required,
                'marks_issue_resolved' => $template->marks_issue_resolved,
                'is_completed' => in_array($template->id, $completedTemplateIds, true),
            ]));

            if ($requestedStatus === IssueStatus::Completed) {
                $missingRequiredItems = $requiredTemplateIds->diff($completedTemplateIds);

                if ($missingRequiredItems->isNotEmpty()) {
                    $validator->errors()->add('checklist_completed', 'Vink alle verplichte checklistitems aan om de storing af te ronden.');
                }
            }

            if ($requestedStatus !== $derivedStatus) {
                $validator->errors()->add('status', 'De gekozen status moet aansluiten op de ingevulde checklist.');
            }
        }];
    }

    /**
     * @return array{title: string, description: string|null, priority: IssuePriority, reported_at: Carbon, is_historical: bool, first_responded_at: Carbon|null, resolved_at: Carbon|null, status: IssueStatus, resolution_summary: string|null, cause: IssueCause|null, internal_note: string|null, checklist_completed: array<int>}
     */
    public function issueAttributes(): array
    {
        $validated = $this->validated();

        return [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => IssuePriority::from($validated['priority']),
            'reported_at' => Carbon::parse($validated['reported_at']),
            'is_historical' => $validated['is_historical'] ?? false,
            'first_responded_at' => isset($validated['first_responded_at']) ? Carbon::parse($validated['first_responded_at']) : null,
            'resolved_at' => isset($validated['resolved_at']) ? Carbon::parse($validated['resolved_at']) : null,
            'status' => isset($validated['status']) ? IssueStatus::from($validated['status']) : IssueStatus::Open,
            'resolution_summary' => $validated['resolution_summary'] ?? null,
            'cause' => isset($validated['cause']) ? IssueCause::from($validated['cause']) : null,
            'internal_note' => $validated['internal_note'] ?? null,
            'checklist_completed' => array_map('intval', $validated['checklist_completed'] ?? []),
        ];
    }
}
