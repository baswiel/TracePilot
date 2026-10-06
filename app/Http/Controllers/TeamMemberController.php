<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamMemberRequest;
use App\Http\Requests\UpdateTeamMemberRequest;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TeamMemberController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', TeamMember::class);

        return Inertia::render('Team/Index', [
            'teamMembers' => TeamMember::query()
                ->withCount('assignedIssues')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString()
                ->through(fn (TeamMember $teamMember): array => [
                    'id' => $teamMember->id,
                    'name' => $teamMember->name,
                    'email' => $teamMember->email,
                    'assigned_issues_count' => $teamMember->assigned_issues_count,
                ]),
        ]);
    }

    public function store(StoreTeamMemberRequest $request): RedirectResponse
    {
        TeamMember::query()->create($request->validated());

        return to_route('team.index');
    }

    public function update(UpdateTeamMemberRequest $request, TeamMember $teamMember): RedirectResponse
    {
        $teamMember->update($request->validated());

        return to_route('team.index');
    }

    public function destroy(TeamMember $teamMember): RedirectResponse
    {
        $this->authorize('delete', $teamMember);
        $teamMember->delete();

        return to_route('team.index');
    }
}
