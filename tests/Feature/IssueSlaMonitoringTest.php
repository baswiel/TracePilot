<?php

namespace Tests\Feature;

use App\Actions\CalculateIssueSla;
use App\Enums\IssuePriority;
use App\Models\BusinessHours;
use App\Models\Issue;
use App\Models\Project;
use App\Models\SlaLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IssueSlaMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_loaded_sla_targets_are_reused_without_per_issue_queries(): void
    {
        BusinessHours::query()->create(['working_days' => [1, 2, 3, 4, 5], 'starts_at' => '09:00', 'ends_at' => '17:00']);
        $level = SlaLevel::query()->create(['name' => 'Gold']);
        $level->targets()->create(['priority' => 'p1', 'response_minutes' => 30, 'resolution_minutes' => 120]);
        $project = Project::factory()->create(['sla_level_id' => $level->id]);
        Issue::factory()->count(5)->for($project)->create(['priority' => 'p1']);
        $issues = Issue::query()->with('project.slaLevel.targets')->get();
        $calculate = app(CalculateIssueSla::class);
        $calculate->handle($issues->first());

        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            foreach ($issues as $issue) {
                $this->assertSame(30, $calculate->handle($issue)['response']['target_minutes']);
            }
            $this->assertCount(0, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }
    }

    public function test_it_calculates_sla_deadlines_from_the_project_sla_level(): void
    {
        Carbon::setTestNow('2026-09-09 10:00:00');

        try {
            $user = User::factory()->create();
            $slaLevel = SlaLevel::query()->create(['name' => 'Gold']);
            $slaLevel->targets()->create([
                'priority' => IssuePriority::P1,
                'response_minutes' => 30,
                'resolution_minutes' => 120,
            ]);
            $project = Project::factory()->create(['sla_level_id' => $slaLevel->id]);
            $issue = Issue::factory()->for($project)->create([
                'priority' => IssuePriority::P1,
                'reported_at' => now()->subMinutes(25),
            ]);

            $this->actingAs($user)
                ->asInertiaRequest()
                ->get(route('issues.show', $issue))
                ->assertOk()
                ->assertJsonPath('props.issue.sla.response.deadline_at', '2026-09-09 10:05:00')
                ->assertJsonPath('props.issue.sla.response.state', 'at_risk')
                ->assertJsonPath('props.issue.sla.resolution.deadline_at', '2026-09-09 11:35:00')
                ->assertJsonPath('props.issue.sla.resolution.state', 'on_track');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_it_marks_an_unanswered_response_as_overdue_on_the_dashboard(): void
    {
        Carbon::setTestNow('2026-09-09 10:00:00');

        try {
            $user = User::factory()->create();
            $project = Project::factory()->create(['sla_first_response_minutes' => 30]);
            Issue::factory()->for($project)->create([
                'reported_at' => now()->subMinutes(31),
            ]);

            $this->actingAs($user)
                ->asInertiaRequest()
                ->get(route('dashboard'))
                ->assertOk()
                ->assertJsonPath('props.statistics.sla_attention', 1)
                ->assertJsonPath('props.issues.data.0.sla.response.state', 'overdue');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_it_records_the_first_response_once_and_reports_a_met_sla(): void
    {
        Carbon::setTestNow('2026-09-09 10:00:00');

        try {
            $user = User::factory()->create();
            $project = Project::factory()->create(['sla_first_response_minutes' => 30]);
            $issue = Issue::factory()->for($project)->create([
                'reported_at' => now()->subMinutes(10),
            ]);

            $this->actingAs($user)
                ->patch(route('issues.first-response.update', $issue))
                ->assertRedirect(route('issues.show', $issue));

            $issue->refresh();

            $this->assertTrue($issue->first_responded_at->equalTo(now()));
            $this->assertDatabaseHas('issue_activities', [
                'issue_id' => $issue->id,
                'action' => 'first_response_recorded',
                'user_id' => $user->id,
            ]);

            $this->actingAs($user)
                ->asInertiaRequest()
                ->get(route('issues.show', $issue))
                ->assertJsonPath('props.issue.sla.response.state', 'met');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_it_only_counts_business_minutes_towards_the_response_sla(): void
    {
        Carbon::setTestNow('2026-09-14 09:20:00');

        try {
            $user = User::factory()->create();
            $project = Project::factory()->create(['sla_first_response_minutes' => 30]);
            $issue = Issue::factory()->for($project)->create([
                'reported_at' => '2026-09-11 16:55:00',
            ]);

            $this->actingAs($user)
                ->asInertiaRequest()
                ->get(route('issues.show', $issue))
                ->assertOk()
                ->assertJsonPath('props.issue.sla.response.deadline_at', '2026-09-14 09:25:00')
                ->assertJsonPath('props.issue.sla.response.remaining_minutes', 5)
                ->assertJsonPath('props.issue.sla.response.state', 'at_risk');

            $this->actingAs($user)
                ->patch(route('issues.first-response.update', $issue))
                ->assertRedirect(route('issues.show', $issue));

            $this->actingAs($user)
                ->asInertiaRequest()
                ->get(route('issues.show', $issue))
                ->assertJsonPath('props.issue.sla.response.state', 'met');
        } finally {
            Carbon::setTestNow();
        }
    }
}
