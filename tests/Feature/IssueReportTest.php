<?php

namespace Tests\Feature;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use App\Models\SlaLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IssueReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_volume_durations_sla_performance_and_project_breakdowns(): void
    {
        Carbon::setTestNow('2026-09-09 12:00:00');

        try {
            $user = User::factory()->create();
            $slaLevel = SlaLevel::query()->create(['name' => 'Gold']);
            $slaLevel->targets()->create([
                'priority' => IssuePriority::P1,
                'response_minutes' => 30,
                'resolution_minutes' => 120,
            ]);
            $project = Project::factory()->create(['name' => 'Klantportaal', 'sla_level_id' => $slaLevel->id]);
            $otherProject = Project::factory()->create(['name' => 'Website']);

            Issue::factory()->for($project)->create([
                'priority' => IssuePriority::P1,
                'status' => IssueStatus::Completed,
                'reported_at' => now()->subHours(4),
                'first_responded_at' => now()->subHours(3)->subMinutes(40),
                'resolved_at' => now()->subHours(2),
                'completed_at' => now()->subHour(),
                'cause' => 'code_defect',
            ]);
            Issue::factory()->for($project)->create([
                'priority' => IssuePriority::P1,
                'status' => IssueStatus::Completed,
                'reported_at' => now()->subHours(3),
                'first_responded_at' => now()->subHours(2)->subMinutes(20),
                'resolved_at' => now()->subMinutes(30),
                'completed_at' => now()->subMinutes(20),
                'cause' => 'code_defect',
            ]);
            Issue::factory()->for($otherProject)->create([
                'reported_at' => now()->subDay(),
                'status' => IssueStatus::Open,
            ]);
            Issue::factory()->for($project)->create(['reported_at' => now()->subMonths(2)]);

            $this->actingAs($user)
                ->asInertiaRequest()
                ->get(route('reports.index', ['from' => '2026-09-09', 'until' => '2026-09-09']))
                ->assertOk()
                ->assertJsonPath('props.report.summary.reported', 2)
                ->assertJsonPath('props.report.summary.completed', 2)
                ->assertJsonPath('props.report.summary.average_first_response_minutes', 30)
                ->assertJsonPath('props.report.summary.average_resolution_minutes', 135)
                ->assertJsonPath('props.report.sla.response.percentage', 50)
                ->assertJsonPath('props.report.sla.resolution.percentage', 50)
                ->assertJsonPath('props.report.causes.0.cause', 'code_defect')
                ->assertJsonPath('props.report.causes.0.count', 2)
                ->assertJsonPath('props.report.projects.0.name', 'Klantportaal')
                ->assertJsonPath('props.report.projects.0.sla_percentage', 50);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_it_filters_a_report_to_one_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        Issue::factory()->for($project)->create();
        Issue::factory()->create();

        $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('reports.index', ['project' => $project->id]))
            ->assertOk()
            ->assertJsonPath('props.report.summary.reported', 1)
            ->assertJsonPath('props.filters.project', $project->id);
    }
}
