<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TeamMember;
use Inertia\Inertia;
use Inertia\Response;

class ResponderController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', TeamMember::class);

        $projects = Project::query()
            ->with('customer:id,name')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'customer_id',
                'is_active',
                'first_responder_id',
                'second_responder_id',
                'third_responder_id',
            ]);

        return Inertia::render('Responders/Index', [
            'responders' => TeamMember::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (TeamMember $teamMember): array => [
                    'id' => $teamMember->id,
                    'name' => $teamMember->name,
                    'email' => $teamMember->email,
                    'projects' => $projects
                        ->map(function (Project $project) use ($teamMember): ?array {
                            $roles = array_filter([
                                $project->first_responder_id === $teamMember->id ? 'Eerste responder' : null,
                                $project->second_responder_id === $teamMember->id ? 'Tweede responder' : null,
                                $project->third_responder_id === $teamMember->id ? 'Derde responder' : null,
                            ]);

                            if ($roles === []) {
                                return null;
                            }

                            return [
                                'id' => $project->id,
                                'name' => $project->name,
                                'customer_name' => $project->customer?->name,
                                'is_active' => $project->is_active,
                                'roles' => array_values($roles),
                            ];
                        })
                        ->filter()
                        ->values(),
                ]),
        ]);
    }
}
