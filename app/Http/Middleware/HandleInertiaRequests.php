<?php

namespace App\Http\Middleware;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'activeIssuesCount' => fn (): int => $request->user()
                ? Issue::query()
                    ->whereIn('status', [IssueStatus::Open->value, IssueStatus::Handling->value])
                    ->count()
                : 0,
            'reportIssueOptions' => fn (): array => $request->user()
                ? [
                    'projects' => Project::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get(['id', 'name', 'customer_name']),
                    'teamMembers' => TeamMember::query()
                        ->orderBy('name')
                        ->get(['id', 'name', 'email']),
                ]
                : ['projects' => [], 'teamMembers' => []],
            'notifications' => fn (): array => $request->user()
                ? Issue::query()
                    ->whereIn('status', [IssueStatus::Open->value, IssueStatus::Handling->value])
                    ->with('project:id,name')
                    ->latest('reported_at')
                    ->limit(5)
                    ->get()
                    ->map(fn (Issue $issue): array => [
                        'id' => $issue->id,
                        'title' => $issue->title,
                        'project' => $issue->project->name,
                        'status' => $issue->status->value,
                    ])
                    ->all()
                : [],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
