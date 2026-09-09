<?php

namespace App\Http\Controllers\Settings;

use App\Actions\ManageIssueChecklistTemplates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\MoveIssueChecklistTemplateRequest;
use App\Http\Requests\Settings\StoreIssueChecklistTemplateRequest;
use App\Http\Requests\Settings\UpdateIssueChecklistTemplateRequest;
use App\Models\IssueChecklistTemplate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IssueChecklistTemplateController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', IssueChecklistTemplate::class);

        return Inertia::render('settings/IssueChecklist', [
            'templates' => IssueChecklistTemplate::query()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (IssueChecklistTemplate $template): array => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'is_required' => $template->is_required,
                    'marks_issue_resolved' => $template->marks_issue_resolved,
                    'is_active' => $template->is_active,
                    'sort_order' => $template->sort_order,
                ]),
        ]);
    }

    public function store(StoreIssueChecklistTemplateRequest $request, ManageIssueChecklistTemplates $templates): RedirectResponse
    {
        $templates->create($request->templateAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Checklist-item toegevoegd.']);

        return back();
    }

    public function update(UpdateIssueChecklistTemplateRequest $request, IssueChecklistTemplate $template, ManageIssueChecklistTemplates $templates): RedirectResponse
    {
        $templates->update($template, $request->templateAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Checklist-item bijgewerkt.']);

        return back();
    }

    public function move(MoveIssueChecklistTemplateRequest $request, IssueChecklistTemplate $template, ManageIssueChecklistTemplates $templates): RedirectResponse
    {
        $templates->move($template, $request->direction());

        return back();
    }

    public function destroy(IssueChecklistTemplate $template, ManageIssueChecklistTemplates $templates): RedirectResponse
    {
        $this->authorize('delete', $template);
        $templates->delete($template);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Checklist-item verwijderd.']);

        return back();
    }
}
