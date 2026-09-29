<?php

namespace Tests\Feature;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\IssueChecklistItem;
use App\Models\Project;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prioritizes_active_issues_before_completed_issues(): void
    {
        $user = User::factory()->create();
        $older = Issue::factory()->create(['reported_at' => now()->subHour()]);
        $newer = Issue::factory()->create([
            'status' => IssueStatus::Completed,
            'reported_at' => now(),
        ]);

        $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('issues.index'))
            ->assertOk()
            ->assertJsonPath('component', 'Issues/Index')
            ->assertJsonPath('props.issues.total', 2)
            ->assertJsonPath('props.issues.data.0.id', $older->id)
            ->assertJsonPath('props.issues.data.1.id', $newer->id);
    }

    public function test_it_filters_issues_and_preserves_filters(): void
    {
        $user = User::factory()->create();
        $teamMember = TeamMember::factory()->create();
        $project = Project::factory()->create(['name' => 'Klantportaal']);
        $matching = Issue::factory()->for($project)->create([
            'title' => 'Inloggen werkt niet',
            'priority' => IssuePriority::P1,
            'status' => IssueStatus::Completed,
            'team_member_id' => $teamMember->id,
        ]);
        Issue::factory()->create(['title' => 'Andere storing']);

        $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('issues.index', [
                'search' => 'inloggen',
                'project' => $project->id,
                'priority' => 'p1',
                'status' => 'completed',
                'assigned_to' => $teamMember->id,
            ]))
            ->assertOk()
            ->assertJsonPath('props.issues.total', 1)
            ->assertJsonPath('props.issues.data.0.id', $matching->id)
            ->assertJsonPath('props.filters.status', 'completed');
    }

    public function test_it_exports_filtered_issues_for_google_sheets(): void
    {
        $user = User::factory()->create();
        $assignee = TeamMember::factory()->create(['name' => 'Mila Jansen']);
        $responder = TeamMember::factory()->create(['name' => 'Noah de Boer']);
        $project = Project::factory()->create([
            'customer_name' => 'Acme B.V.',
            'contact_name' => 'Sam de Wit',
            'first_responder_id' => $responder->id,
        ]);
        $issue = Issue::factory()->for($project)->create([
            'title' => 'Portaal niet bereikbaar',
            'resolution_summary' => 'Certificaat vernieuwd.',
            'team_member_id' => $assignee->id,
            'knowledge_base_recorded' => true,
            'is_trend' => true,
        ]);
        IssueChecklistItem::factory()->for($issue)->create([
            'name' => 'Postmortem verstuurd',
            'is_completed' => true,
        ]);
        Issue::factory()->create(['title' => 'Niet exporteren']);

        $response = $this->actingAs($user)->get(route('issues.export', [
            'project' => $project->id,
        ]));

        $response->assertOk()
            ->assertDownload('incidenten-'.now()->format('Y-m-d').'.csv');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Datum Incident', $content);
        $this->assertStringContainsString('Uitkomst opgenomen in Kennisbank', $content);
        $this->assertStringContainsString('Acme B.V.', $content);
        $this->assertStringContainsString('Sam de Wit', $content);
        $this->assertStringContainsString('Mila Jansen, Noah de Boer', $content);
        $this->assertStringContainsString('Certificaat vernieuwd.', $content);
        $this->assertStringNotContainsString('Niet exporteren', $content);
    }
}
