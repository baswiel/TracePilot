<?php

namespace Tests\Feature;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_the_expected_status_statistics(): void
    {
        Carbon::setTestNow('2026-09-04 12:00:00');

        try {
            $user = User::factory()->create();
            Issue::factory()->count(2)->create(['status' => IssueStatus::Open]);
            Issue::factory()->create(['status' => IssueStatus::Handling]);
            Issue::factory()->create([
                'status' => IssueStatus::Completed,
                'completed_at' => now()->subDay(),
            ]);
            Issue::factory()->create([
                'status' => IssueStatus::Completed,
                'completed_at' => now()->subMonth(),
            ]);
            Issue::factory()->create([
                'status' => IssueStatus::Completed,
                'completed_at' => null,
            ]);

            $this->actingAs($user)
                ->asInertiaRequest()
                ->get(route('dashboard'))
                ->assertOk()
                ->assertJsonPath('props.statistics.open', 2)
                ->assertJsonPath('props.statistics.handling', 1)
                ->assertJsonPath('props.statistics.completed_this_month', 1)
                ->assertJsonPath('props.activeIssuesCount', 3);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_lists_open_and_handling_issues_in_priority_and_reported_order(): void
    {
        $user = User::factory()->create();
        $p1Old = Issue::factory()->create([
            'priority' => IssuePriority::P1,
            'status' => IssueStatus::Open,
            'reported_at' => now()->subHours(4),
        ]);
        $p1New = Issue::factory()->create([
            'priority' => IssuePriority::P1,
            'status' => IssueStatus::Open,
            'reported_at' => now()->subHour(),
        ]);
        $p2 = Issue::factory()->create([
            'priority' => IssuePriority::P2,
            'status' => IssueStatus::Handling,
            'reported_at' => now()->subDays(2),
        ]);
        $p3 = Issue::factory()->create([
            'priority' => IssuePriority::P3,
            'status' => IssueStatus::Open,
            'reported_at' => now()->subDays(3),
        ]);
        Issue::factory()->create(['status' => IssueStatus::Completed]);

        $response = $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertJsonPath('props.issues.total', 4);
        $response->assertJsonPath('props.issues.data.0.id', $p1Old->id);
        $response->assertJsonPath('props.issues.data.1.id', $p1New->id);
        $response->assertJsonPath('props.issues.data.2.id', $p2->id);
        $response->assertJsonPath('props.issues.data.3.id', $p3->id);
    }

    public function test_dashboard_filters_are_applied_and_preserved_in_the_response(): void
    {
        $user = User::factory()->create();
        $assignee = TeamMember::factory()->create();
        $otherAssignee = TeamMember::factory()->create();
        $project = Project::factory()->create(['name' => 'Klantportaal']);
        $otherProject = Project::factory()->create(['name' => 'Website']);
        $matchingIssue = Issue::factory()->for($project)->create([
            'priority' => IssuePriority::P1,
            'status' => IssueStatus::Handling,
            'team_member_id' => $assignee->id,
        ]);
        Issue::factory()->for($project)->create([
            'priority' => IssuePriority::P2,
            'status' => IssueStatus::Open,
            'team_member_id' => $otherAssignee->id,
        ]);
        Issue::factory()->for($otherProject)->create([
            'priority' => IssuePriority::P1,
            'status' => IssueStatus::Handling,
            'team_member_id' => $assignee->id,
        ]);

        $response = $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('dashboard', [
                'project' => $project->id,
                'priority' => 'p1',
                'status' => 'handling',
                'assigned_to' => $assignee->id,
            ]));

        $response->assertOk();
        $response->assertJsonPath('props.issues.total', 1);
        $response->assertJsonPath('props.issues.data.0.id', $matchingIssue->id);
        $response->assertJsonPath('props.filters.project', $project->id);
        $response->assertJsonPath('props.filters.priority', 'p1');
        $response->assertJsonPath('props.filters.status', 'handling');
        $response->assertJsonPath('props.filters.assigned_to', $assignee->id);
    }

    public function test_dashboard_paginates_current_issues(): void
    {
        $user = User::factory()->create();
        Issue::factory()->count(16)->create(['status' => IssueStatus::Open]);

        $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('dashboard'))
            ->assertJsonPath('props.issues.per_page', 15)
            ->assertJsonPath('props.issues.last_page', 2);
    }

    public function test_dashboard_filters_are_validated_on_the_server(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->get(route('dashboard', [
                'project' => 'niet-geldig',
                'priority' => 'p5',
                'status' => 'completed',
                'assigned_to' => 999999,
            ]))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors(['project', 'priority', 'status', 'assigned_to']);
    }
}
