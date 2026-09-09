<?php

namespace Tests\Feature;

use App\Enums\IssueStatus;
use App\Models\Customer;
use App\Models\Issue;
use App\Models\Project;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_database_seeder_creates_team_members_and_project_responders(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('team_members', 7);
        $this->assertDatabaseCount('customers', 9);
        $this->assertDatabaseCount('projects', 14);

        Project::query()->each(function (Project $project): void {
            $this->assertNotNull($project->customer_id);
            $this->assertNotNull($project->first_responder_id);
            $this->assertNotNull($project->second_responder_id);
            $this->assertNotSame($project->first_responder_id, $project->second_responder_id);
        });

        $this->assertSame(2, Customer::query()->where('name', 'Folkersma')->sole()->projects()->count());
    }

    public function test_guests_cannot_access_project_management(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.index'))->assertRedirect(route('login'));
        $this->get(route('projects.create'))->assertRedirect(route('login'));
        $this->get(route('projects.show', $project))->assertRedirect(route('login'));
        $this->post(route('projects.store'), [])->assertRedirect(route('login'));
        $this->put(route('projects.update', $project), [])->assertRedirect(route('login'));
        $this->patch(route('projects.active.update', $project), [])->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_search_filter_and_paginate_projects(): void
    {
        $user = User::factory()->create();
        $matchingProject = Project::factory()->create([
            'name' => 'Acme platform',
            'customer_name' => 'Acme B.V.',
        ]);
        Project::factory()->inactive()->create(['name' => 'Gearchiveerd project']);
        Issue::factory()->for($matchingProject)->create([
            'status' => IssueStatus::Open,
            'reported_at' => now()->subDay(),
        ]);
        $latestIssueAt = now();
        Issue::factory()->for($matchingProject)->create([
            'status' => IssueStatus::Completed,
            'reported_at' => $latestIssueAt,
        ]);
        Project::factory()->count(10)->create();

        $response = $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('projects.index', [
                'search' => 'Acme',
                'status' => 'active',
            ]));

        $response->assertOk();
        $response->assertJsonPath('props.projects.total', 1);
        $response->assertJsonPath('props.projects.data.0.name', 'Acme platform');
        $response->assertJsonPath('props.projects.data.0.active_issues_count', 1);
        $response->assertJsonPath('props.projects.data.0.latest_issue_at', $latestIssueAt->toDateTimeString());

        $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('projects.index'))
            ->assertJsonPath('props.projects.last_page', 2);

        $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('projects.index', ['status' => 'inactive']))
            ->assertJsonPath('props.projects.data.0.name', 'Gearchiveerd project');
    }

    public function test_authenticated_users_can_create_update_and_archive_projects(): void
    {
        $user = User::factory()->create();
        $firstResponder = TeamMember::factory()->create();
        $secondResponder = TeamMember::factory()->create();

        $createResponse = $this->actingAs($user)->post(route('projects.store'), [
            'name' => 'Klantportaal',
            'customer_name' => 'Voorbeeld B.V.',
            'description' => 'Portaal voor klanten.',
            'sla_first_response_minutes' => 60,
            'sla_resolution_minutes' => 240,
            'contact_name' => 'Jamie de Vries',
            'contact_email' => 'jamie@example.test',
            'contact_phone' => '+31 6 12345678',
            'first_responder_id' => $firstResponder->id,
            'second_responder_id' => $secondResponder->id,
            'is_active' => true,
        ]);

        $project = Project::query()->sole();

        $createResponse->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Klantportaal',
            'sla_first_response_minutes' => 60,
            'sla_resolution_minutes' => 240,
            'contact_name' => 'Jamie de Vries',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'first_responder_id' => $firstResponder->id,
            'second_responder_id' => $secondResponder->id,
        ]);

        $this->actingAs($user)
            ->put(route('projects.update', $project), [
                'name' => 'Nieuw klantportaal',
                'customer_name' => null,
                'description' => null,
                'sla_first_response_minutes' => 30,
                'sla_resolution_minutes' => 120,
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'first_responder_id' => $secondResponder->id,
                'second_responder_id' => $firstResponder->id,
                'is_active' => true,
            ])
            ->assertRedirect(route('projects.show', $project));

        $this->actingAs($user)
            ->patch(route('projects.active.update', $project), ['is_active' => false])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Nieuw klantportaal',
            'sla_first_response_minutes' => 30,
            'sla_resolution_minutes' => 120,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'first_responder_id' => $secondResponder->id,
            'second_responder_id' => $firstResponder->id,
        ]);
    }

    public function test_project_validation_and_policy_protect_project_data(): void
    {
        $user = User::factory()->create();
        $projectWithoutIssues = Project::factory()->create();
        $projectWithIssues = Project::factory()->create();
        Issue::factory()->for($projectWithIssues)->create();

        $this->actingAs($user)
            ->from(route('projects.create'))
            ->post(route('projects.store'), [
                'name' => '',
                'is_active' => 'not-a-boolean',
            ])
            ->assertSessionHasErrors(['name', 'is_active']);

        $this->assertTrue($user->can('delete', $projectWithoutIssues));
        $this->assertFalse($user->can('delete', $projectWithIssues));
    }

    public function test_project_detail_separates_current_and_completed_issues(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $firstResponder = TeamMember::factory()->create();
        $secondResponder = TeamMember::factory()->create();
        $project->update([
            'sla_first_response_minutes' => 60,
            'sla_resolution_minutes' => 240,
            'contact_name' => 'Jamie de Vries',
            'contact_email' => 'jamie@example.test',
            'contact_phone' => '+31 6 12345678',
        ]);
        $project->update([
            'first_responder_id' => $firstResponder->id,
            'second_responder_id' => $secondResponder->id,
        ]);
        $currentIssue = Issue::factory()->for($project)->create([
            'title' => 'Actuele storing',
            'status' => IssueStatus::Handling,
        ]);
        $completedIssue = Issue::factory()->for($project)->create([
            'title' => 'Afgeronde storing',
            'status' => IssueStatus::Completed,
            'completed_at' => now(),
            'reported_at' => now()->subMinutes(90),
        ]);

        $response = $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('projects.show', $project));

        $response->assertOk();
        $response->assertJsonPath('props.project.name', $project->name);
        $response->assertJsonPath('props.currentIssues.0.id', $currentIssue->id);
        $response->assertJsonPath('props.completedIssues.0.id', $completedIssue->id);
        $response->assertJsonPath('props.completedIssues.0.duration_minutes', 90);
        $response->assertJsonPath('props.project.sla_first_response_minutes', 60);
        $response->assertJsonPath('props.project.sla_resolution_minutes', 240);
        $response->assertJsonPath('props.project.contact_name', 'Jamie de Vries');
        $response->assertJsonPath('props.project.first_responder.id', $firstResponder->id);
        $response->assertJsonPath('props.project.second_responder.id', $secondResponder->id);
    }
}
