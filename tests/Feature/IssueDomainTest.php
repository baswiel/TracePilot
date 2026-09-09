<?php

namespace Tests\Feature;

use App\Actions\CreateIssue;
use App\Actions\UpdateIssueChecklistItemCompletion;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\IssueActivity;
use App\Models\IssueChecklistItem;
use App\Models\IssueChecklistTemplate;
use App\Models\Project;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\IssueChecklistTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class IssueDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_expose_the_expected_relations_and_casts(): void
    {
        $creator = User::factory()->create();
        $assignee = TeamMember::factory()->create();
        $project = Project::factory()->create();
        $issue = Issue::factory()->for($project)->create([
            'team_member_id' => $assignee->id,
            'created_by' => $creator->id,
            'priority' => IssuePriority::P2,
        ]);
        $item = IssueChecklistItem::factory()->for($issue)->create([
            'completed_by' => $creator->id,
        ]);
        $activity = IssueActivity::factory()->for($issue)->create([
            'user_id' => $creator->id,
            'metadata' => ['source' => 'test'],
        ]);

        $this->assertTrue($project->issues->contains($issue));
        $this->assertTrue($issue->is($item->issue));
        $this->assertTrue($issue->is($activity->issue));
        $this->assertTrue($assignee->is($issue->teamMember));
        $this->assertTrue($creator->is($issue->createdBy));
        $this->assertTrue($creator->is($item->completedBy));
        $this->assertTrue($creator->is($activity->user));
        $this->assertSame(IssuePriority::P2, $issue->priority);
        $this->assertSame(IssueStatus::Open, $issue->status);
        $this->assertSame(['source' => 'test'], $activity->metadata);
    }

    public function test_issue_creation_snapshots_only_active_checklist_templates(): void
    {
        $resolutionTemplate = IssueChecklistTemplate::factory()
            ->resolutionMarker()
            ->create(['name' => 'Oplossing verifiëren', 'sort_order' => 1]);
        IssueChecklistTemplate::factory()->create([
            'name' => 'Log bijwerken',
            'sort_order' => 2,
        ]);
        IssueChecklistTemplate::factory()->inactive()->create([
            'name' => 'Oud item',
            'sort_order' => 3,
        ]);

        $issue = $this->createIssue();

        $this->assertSame(IssueStatus::Open, $issue->status);
        $this->assertCount(2, $issue->checklistItems);
        $this->assertSame(
            ['Oplossing verifiëren', 'Log bijwerken'],
            $issue->checklistItems->pluck('name')->all(),
        );
        $this->assertTrue($issue->checklistItems->first()->marks_issue_resolved);
        $this->assertDatabaseHas('issue_activities', [
            'issue_id' => $issue->id,
            'action' => 'issue_created',
        ]);

        $resolutionTemplate->update([
            'name' => 'Aangepast template-item',
            'is_active' => false,
        ]);

        $this->assertSame(
            'Oplossing verifiëren',
            $issue->checklistItems()->orderBy('sort_order')->first()->name,
        );
    }

    public function test_only_one_active_resolution_template_is_allowed(): void
    {
        IssueChecklistTemplate::factory()->resolutionMarker()->create();

        $this->expectException(ValidationException::class);

        IssueChecklistTemplate::factory()->resolutionMarker()->create();
    }

    public function test_standard_checklist_templates_are_seeded(): void
    {
        $this->seed(IssueChecklistTemplateSeeder::class);

        $this->assertDatabaseHas('issue_checklist_templates', [
            'name' => 'Storing opgelost',
            'is_required' => true,
            'marks_issue_resolved' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $this->assertDatabaseHas('issue_checklist_templates', [
            'name' => 'Toegevoegd aan Storing Log',
            'is_required' => true,
            'marks_issue_resolved' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);
        $this->assertDatabaseHas('issue_checklist_templates', [
            'name' => 'Postmortem verstuurd',
            'is_required' => false,
            'marks_issue_resolved' => false,
            'is_active' => true,
            'sort_order' => 3,
        ]);
    }

    public function test_checklist_completion_recalculates_every_issue_status_transition(): void
    {
        IssueChecklistTemplate::factory()->resolutionMarker()->create(['sort_order' => 1]);
        IssueChecklistTemplate::factory()->create(['sort_order' => 2]);
        IssueChecklistTemplate::factory()->create([
            'is_required' => false,
            'sort_order' => 3,
        ]);

        $actor = User::factory()->create();
        $issue = $this->createIssue();
        $updateChecklistItem = app(UpdateIssueChecklistItemCompletion::class);
        $resolutionItem = $issue->checklistItems->firstWhere('marks_issue_resolved', true);
        $requiredItem = $issue->checklistItems
            ->where('is_required', true)
            ->firstWhere('marks_issue_resolved', false);
        $optionalItem = $issue->checklistItems->firstWhere('is_required', false);

        $issue = $updateChecklistItem->handle($resolutionItem, true, $actor);
        $this->assertSame(IssueStatus::Handling, $issue->status);
        $this->assertNotNull($issue->resolved_at);
        $this->assertNull($issue->completed_at);

        $issue = $updateChecklistItem->handle($requiredItem, true, $actor);
        $this->assertSame(IssueStatus::Completed, $issue->status);
        $this->assertNotNull($issue->completed_at);

        $issue = $updateChecklistItem->handle($optionalItem, true, $actor);
        $issue = $updateChecklistItem->handle($optionalItem, false, $actor);
        $this->assertSame(IssueStatus::Completed, $issue->status);

        $issue = $updateChecklistItem->handle($requiredItem, false, $actor);
        $this->assertSame(IssueStatus::Handling, $issue->status);
        $this->assertNotNull($issue->resolved_at);
        $this->assertNull($issue->completed_at);

        $issue = $updateChecklistItem->handle($resolutionItem, false, $actor);
        $this->assertSame(IssueStatus::Open, $issue->status);
        $this->assertNull($issue->resolved_at);
        $this->assertNull($issue->completed_at);
        $this->assertSame(7, $issue->activities()->count());
    }

    private function createIssue(): Issue
    {
        $creator = User::factory()->create();

        return app(CreateIssue::class)->handle(
            Project::factory()->create(),
            $creator,
            [
                'title' => 'Productieomgeving niet bereikbaar',
                'priority' => IssuePriority::P2,
            ],
        );
    }
}
