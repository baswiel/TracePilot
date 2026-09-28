<?php

namespace Tests\Feature;

use App\Actions\CreateIssue;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Actions\CalculateIssueSla;
use App\Models\Issue;
use App\Models\IssueChecklistTemplate;
use App\Models\Project;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class IssueReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_report_an_issue(): void
    {
        $reporter = User::factory()->create();
        $assignee = TeamMember::factory()->create();
        $project = Project::factory()->create(['name' => 'Portaal']);
        IssueChecklistTemplate::factory()->resolutionMarker()->create([
            'name' => 'Technisch opgelost',
            'sort_order' => 1,
        ]);
        IssueChecklistTemplate::factory()->create([
            'name' => 'Storing log bijgewerkt',
            'sort_order' => 2,
        ]);
        IssueChecklistTemplate::factory()->inactive()->create([
            'name' => 'Niet kopiëren',
            'sort_order' => 3,
        ]);

        $response = $this->actingAs($reporter)->post(route('issues.store'), [
            'project_id' => $project->id,
            'title' => 'Aanmelden werkt niet',
            'description' => 'Gebruikers krijgen een foutmelding.',
            'priority' => 'p1',
            'reported_at' => '2026-09-03 09:30:00',
            'team_member_id' => $assignee->id,
        ]);

        $issue = Issue::query()->sole();

        $response->assertRedirect(route('issues.show', $issue));
        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'project_id' => $project->id,
            'title' => 'Aanmelden werkt niet',
            'priority' => IssuePriority::P1->value,
            'status' => 'open',
            'team_member_id' => null,
            'created_by' => $reporter->id,
        ]);
        $this->assertDatabaseHas('issue_checklist_items', [
            'issue_id' => $issue->id,
            'name' => 'Technisch opgelost',
            'is_required' => true,
            'marks_issue_resolved' => true,
            'sort_order' => 1,
        ]);
        $this->assertDatabaseHas('issue_checklist_items', [
            'issue_id' => $issue->id,
            'name' => 'Storing log bijgewerkt',
            'is_required' => true,
            'marks_issue_resolved' => false,
            'sort_order' => 2,
        ]);
        $this->assertDatabaseMissing('issue_checklist_items', [
            'issue_id' => $issue->id,
            'name' => 'Niet kopiëren',
        ]);
    }

    public function test_reporting_an_issue_validates_the_required_fields_and_enum_values(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('issues.report'))
            ->post(route('issues.store'), [
                'project_id' => null,
                'title' => '',
                'priority' => 'urgent',
                'reported_at' => 'geen datum',
            ])
            ->assertRedirect(route('issues.report'))
            ->assertSessionHasErrors(['project_id', 'title', 'priority', 'reported_at']);
    }

    public function test_an_inactive_project_cannot_be_selected_for_a_new_issue(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->inactive()->create();

        $this->actingAs($user)
            ->from(route('issues.report'))
            ->post(route('issues.store'), $this->validAttributes($project))
            ->assertRedirect(route('issues.report'))
            ->assertSessionHasErrors('project_id');

        $this->assertDatabaseCount('issues', 0);
    }

    public function test_checklist_templates_are_snapshotted_when_reporting_an_issue(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $template = IssueChecklistTemplate::factory()->resolutionMarker()->create([
            'name' => 'Originele naam',
            'is_required' => true,
            'sort_order' => 4,
        ]);

        $this->actingAs($user)->post(route('issues.store'), $this->validAttributes($project));

        $issue = Issue::query()->sole();
        $template->update(['name' => 'Gewijzigde template naam']);

        $this->assertDatabaseHas('issue_checklist_items', [
            'issue_id' => $issue->id,
            'name' => 'Originele naam',
            'is_required' => true,
            'marks_issue_resolved' => true,
            'sort_order' => 4,
        ]);
    }

    public function test_reporting_an_issue_registers_its_first_activity(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)->post(route('issues.store'), $this->validAttributes($project));

        $issue = Issue::query()->sole();

        $this->assertDatabaseHas('issue_activities', [
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'action' => 'issue_created',
            'description' => 'Issue aangemaakt.',
        ]);
    }

    public function test_an_issue_can_be_registered_retrospectively_with_its_actual_timestamps(): void
    {
        $reporter = User::factory()->create();
        $project = Project::factory()->create(['sla_first_response_minutes' => 30, 'sla_resolution_minutes' => 120]);
        $resolutionTemplate = IssueChecklistTemplate::factory()->resolutionMarker()->create(['sort_order' => 1]);
        $followUpTemplate = IssueChecklistTemplate::factory()->create(['sort_order' => 2]);

        $this->actingAs($reporter)->post(route('issues.store'), [
            'project_id' => $project->id,
            'title' => 'Portaal was niet bereikbaar',
            'priority' => 'p1',
            'reported_at' => '2026-09-28 10:15:00',
            'is_historical' => true,
            'first_responded_at' => '2026-09-28 10:24:00',
            'resolved_at' => '2026-09-28 11:48:00',
            'status' => 'completed',
            'resolution_summary' => 'De vastgelopen worker is opnieuw gestart.',
            'checklist_completed' => [$resolutionTemplate->id, $followUpTemplate->id],
        ])->assertRedirect();

        $issue = Issue::query()->sole();

        $this->assertSame(IssueStatus::Completed, $issue->status);
        $this->assertTrue($issue->reported_at->equalTo('2026-09-28 10:15:00'));
        $this->assertTrue($issue->first_responded_at->equalTo('2026-09-28 10:24:00'));
        $this->assertTrue($issue->resolved_at->equalTo('2026-09-28 11:48:00'));
        $this->assertTrue($issue->completed_at->equalTo('2026-09-28 11:48:00'));
        $this->assertSame('De vastgelopen worker is opnieuw gestart.', $issue->resolution_summary);
        $this->assertDatabaseHas('issue_activities', [
            'issue_id' => $issue->id,
            'action' => 'issue_created',
            'description' => 'Storing achteraf geregistreerd.',
        ]);
        $this->assertDatabaseHas('issue_activities', [
            'issue_id' => $issue->id,
            'action' => 'first_response_recorded',
            'created_at' => '2026-09-28 10:24:00',
        ]);
    }

    public function test_historical_timestamps_must_follow_the_incident_lifecycle(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->from(route('issues.report'))
            ->post(route('issues.store'), [
                ...$this->validAttributes($project),
                'is_historical' => true,
                'status' => 'open',
                'first_responded_at' => '2026-09-03 09:59:00',
                'resolved_at' => '2026-09-03 09:58:00',
            ])
            ->assertSessionHasErrors(['first_responded_at', 'resolved_at']);

        $this->actingAs($user)
            ->from(route('issues.report'))
            ->post(route('issues.store'), [
                ...$this->validAttributes($project),
                'is_historical' => true,
                'status' => 'open',
                'first_responded_at' => '2026-09-03 10:24:00',
                'resolved_at' => '2026-09-03 10:23:00',
            ])
            ->assertSessionHasErrors('resolved_at');
    }

    public function test_historical_sla_is_calculated_from_the_incident_timestamps_not_its_creation_time(): void
    {
        $project = Project::factory()->create(['sla_first_response_minutes' => 30, 'sla_resolution_minutes' => 120]);
        $issue = Issue::factory()->for($project)->create([
            'reported_at' => '2026-09-28 10:15:00',
            'first_responded_at' => '2026-09-28 10:24:00',
            'resolved_at' => '2026-09-28 11:48:00',
        ]);

        $sla = app(CalculateIssueSla::class)->handle($issue, now()->addDay());

        $this->assertSame(21, $sla['response']['remaining_minutes']);
        $this->assertSame(27, $sla['resolution']['remaining_minutes']);
    }

    public function test_issue_creation_rolls_back_when_checklist_storage_fails(): void
    {
        $project = Project::factory()->create();
        $creator = User::factory()->create();
        IssueChecklistTemplate::factory()->create();
        $createIssue = new class extends CreateIssue
        {
            protected function createChecklistItems(Issue $issue, Collection $templates): void
            {
                throw new RuntimeException('Checklist kan niet worden opgeslagen.');
            }
        };

        try {
            $createIssue->handle($project, $creator, [
                'title' => 'Mislukte storing',
                'priority' => IssuePriority::P2,
            ]);
            $this->fail('Er had een uitzondering moeten worden opgegooid.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Checklist kan niet worden opgeslagen.', $exception->getMessage());
        }

        $this->assertDatabaseCount('issues', 0);
        $this->assertDatabaseCount('issue_checklist_items', 0);
        $this->assertDatabaseCount('issue_activities', 0);
    }

    /**
     * @return array<string, int|string|null>
     */
    private function validAttributes(Project $project): array
    {
        return [
            'project_id' => $project->id,
            'title' => 'Applicatie reageert traag',
            'description' => null,
            'priority' => 'p2',
            'reported_at' => '2026-09-03 10:00:00',
        ];
    }
}
