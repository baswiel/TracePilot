<?php

namespace App\Actions;

use App\Models\IssueChecklistTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageIssueChecklistTemplates
{
    /**
     * @param  array{name: string, is_required: bool, marks_issue_resolved: bool, is_active: bool, sort_order: int}  $attributes
     */
    public function create(array $attributes): IssueChecklistTemplate
    {
        return DB::transaction(function () use ($attributes): IssueChecklistTemplate {
            $templates = $this->lockedTemplates();
            $template = IssueChecklistTemplate::query()->create($attributes);
            $templates->push($template);

            $this->ensureValidState($templates);
            $this->normalizeOrder($templates);

            return $template->refresh();
        });
    }

    /**
     * @param  array{name: string, is_required: bool, marks_issue_resolved: bool, is_active: bool, sort_order: int}  $attributes
     */
    public function update(IssueChecklistTemplate $template, array $attributes): IssueChecklistTemplate
    {
        return DB::transaction(function () use ($attributes, $template): IssueChecklistTemplate {
            $templates = $this->lockedTemplates();
            $template = $templates->first(
                fn (IssueChecklistTemplate $item): bool => $item->is($template),
            );

            if (! $template instanceof IssueChecklistTemplate) {
                abort(404);
            }
            $template->fill($attributes)->save();

            $this->ensureValidState($templates);
            $this->normalizeOrder($templates);

            return $template->refresh();
        });
    }

    public function move(IssueChecklistTemplate $template, string $direction): void
    {
        DB::transaction(function () use ($direction, $template): void {
            $templates = $this->lockedTemplates();
            $position = $templates->search(fn (IssueChecklistTemplate $item): bool => $item->is($template));

            if ($position === false) {
                abort(404);
            }

            $targetPosition = $direction === 'up' ? $position - 1 : $position + 1;

            if (! isset($templates[$targetPosition])) {
                return;
            }

            $orderedTemplates = $templates->values()->all();
            [$orderedTemplates[$position], $orderedTemplates[$targetPosition]] = [
                $orderedTemplates[$targetPosition],
                $orderedTemplates[$position],
            ];

            $this->normalizeOrder(collect($orderedTemplates), preserveOrder: true);
        });
    }

    public function delete(IssueChecklistTemplate $template): void
    {
        DB::transaction(function () use ($template): void {
            $templates = $this->lockedTemplates();
            $template = $templates->first(
                fn (IssueChecklistTemplate $item): bool => $item->is($template),
            );

            if (! $template instanceof IssueChecklistTemplate) {
                abort(404);
            }

            if ($template->is_active) {
                throw ValidationException::withMessages([
                    'template' => 'Activeer het item niet; zet het eerst inactief voordat je het verwijdert.',
                ]);
            }

            $template->delete();
            $templates = $templates->reject(fn (IssueChecklistTemplate $item): bool => $item->is($template))->values();

            $this->ensureValidState($templates);
            $this->normalizeOrder($templates);
        });
    }

    /**
     * @return Collection<int, IssueChecklistTemplate>
     */
    private function lockedTemplates(): Collection
    {
        return IssueChecklistTemplate::query()
            ->lockForUpdate()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, IssueChecklistTemplate>  $templates
     */
    private function ensureValidState(Collection $templates): void
    {
        $activeTemplates = $templates->where('is_active', true);

        if ($activeTemplates->where('is_required', true)->isEmpty()) {
            throw ValidationException::withMessages([
                'is_required' => 'Er moet minimaal één actief verplicht checklist-item blijven bestaan.',
            ]);
        }

        if ($activeTemplates->where('marks_issue_resolved', true)->count() > 1) {
            throw ValidationException::withMessages([
                'marks_issue_resolved' => 'Er mag slechts één actief oplossingsitem zijn.',
            ]);
        }
    }

    /**
     * @param  Collection<int, IssueChecklistTemplate>  $templates
     */
    private function normalizeOrder(Collection $templates, bool $preserveOrder = false): void
    {
        $orderedTemplates = $preserveOrder
            ? $templates
            : $templates->sortBy(fn (IssueChecklistTemplate $template): array => [$template->sort_order, $template->id]);

        $orderedTemplates
            ->values()
            ->each(function (IssueChecklistTemplate $template, int $index): void {
                $sortOrder = $index + 1;

                if ($template->sort_order !== $sortOrder) {
                    $template->update(['sort_order' => $sortOrder]);
                }
            });
    }
}
