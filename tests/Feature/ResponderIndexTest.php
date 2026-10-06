<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ResponderIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_each_responder_with_their_project_roles(): void
    {
        $user = User::factory()->create();
        $firstResponder = TeamMember::factory()->create(['name' => 'Sam Jansen']);
        $secondResponder = TeamMember::factory()->create(['name' => 'Noor Bakker']);
        $project = Project::factory()->create([
            'name' => 'Klantportaal',
            'first_responder_id' => $firstResponder->id,
            'second_responder_id' => $secondResponder->id,
            'third_responder_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('responders.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Responders/Index')
                ->has('responders.data', 2)
                ->where('responders.data.0.name', 'Noor Bakker')
                ->where('responders.data.0.projects.0.id', $project->id)
                ->where('responders.data.0.projects.0.roles.0', 'Tweede responder')
                ->where('responders.data.1.name', 'Sam Jansen')
                ->where('responders.data.1.projects.0.id', $project->id)
                ->where('responders.data.1.projects.0.roles.0', 'Eerste responder'));
    }
}
