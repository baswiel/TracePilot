<?php

namespace Tests\Feature;

use App\Actions\CreateIssue;
use App\Actions\UpdateIssueChecklistItemCompletion;
use App\Enums\IssuePriority;
use App\Models\Customer;
use App\Models\Issue;
use App\Models\IssueChecklistItem;
use App\Models\Project;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use TypeError;

class ArchitectureRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_lists_are_paginated_and_the_second_page_remains_accessible(): void
    {
        $user = User::factory()->create();
        Customer::factory()->count(21)->create();
        TeamMember::factory()->count(21)->create();
        $this->actingAs($user)->asInertiaRequest();
        foreach (['customers' => 'customers.index', 'teamMembers' => 'team.index', 'responders' => 'responders.index'] as $prop => $route) {
            $this->get(route($route))->assertJsonPath("props.{$prop}.total", 21)->assertJsonCount(20, "props.{$prop}.data");
            $this->get(route($route, ['page' => 2]))->assertJsonCount(1, "props.{$prop}.data");
        }
    }

    public function test_report_and_export_include_issues_across_multiple_batches(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        Issue::factory()->count(251)->create(['project_id' => $project->id, 'created_by' => $user->id, 'reported_at' => '2026-09-28 10:00:00']);
        $this->actingAs($user)->asInertiaRequest()->get(route('reports.index', ['period' => 'custom', 'from' => '2026-09-28', 'to' => '2026-09-28']))
            ->assertJsonPath('props.report.summary.reported', 251)
            ->assertJsonPath('props.report.trend.points.0.reported', 251)
            ->assertJsonPath('props.report.projects.0.reported', 251);
        $this->flushHeaders();
        $csv = $this->get(route('issues.export', ['project' => $project->id]))->streamedContent();
        $this->assertSame(252, count(explode("\n", trim($csv))));
    }

    public function test_direct_issue_creation_rejects_an_inactive_project(): void
    {
        $project = Project::factory()->inactive()->create();
        $user = User::factory()->create();
        try {
            app(CreateIssue::class)->handle($project, $user, ['title' => 'Storing', 'priority' => IssuePriority::P1]);
            $this->fail('Een inactief project had afgewezen moeten worden.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('project_id', $exception->errors());
        }
        $this->assertDatabaseCount('issues', 0);
    }

    public function test_direct_checklist_mutation_requires_an_actor(): void
    {
        $item = IssueChecklistItem::factory()->create();
        try {
            app(UpdateIssueChecklistItemCompletion::class)->handle($item, true, null);
            $this->fail('Een ontbrekende actor had afgewezen moeten worden.');
        } catch (TypeError) {
            $this->assertFalse($item->refresh()->is_completed);
        }
        $this->assertDatabaseCount('issue_activities', 0);
    }
}
