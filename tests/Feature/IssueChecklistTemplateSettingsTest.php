<?php

namespace Tests\Feature;

use App\Actions\CreateIssue;
use App\Enums\IssuePriority;
use App\Models\IssueChecklistTemplate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueChecklistTemplateSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_issue_checklist_settings(): void
    {
        $template = IssueChecklistTemplate::factory()->create();

        $this->get(route('issue-checklist.index'))->assertRedirect(route('login'));
        $this->post(route('issue-checklist.store'), [])->assertRedirect(route('login'));
        $this->patch(route('issue-checklist.update', $template), [])->assertRedirect(route('login'));
        $this->patch(route('issue-checklist.move', $template), [])->assertRedirect(route('login'));
        $this->delete(route('issue-checklist.destroy', $template))->assertRedirect(route('login'));
    }

    public function test_templates_are_validated_and_normalized_to_a_unique_order(): void
    {
        $user = User::factory()->create();
        IssueChecklistTemplate::factory()->create(['sort_order' => 1]);

        $this->actingAs($user)
            ->from(route('issue-checklist.index'))
            ->post(route('issue-checklist.store'), [
                'name' => '',
                'is_required' => true,
                'marks_issue_resolved' => false,
                'is_active' => true,
                'sort_order' => 0,
            ])
            ->assertSessionHasErrors(['name', 'sort_order']);

        $this->actingAs($user)
            ->post(route('issue-checklist.store'), [
                'name' => 'Nieuw item',
                'is_required' => true,
                'marks_issue_resolved' => false,
                'is_active' => true,
                'sort_order' => 99,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [1, 2],
            IssueChecklistTemplate::query()->orderBy('sort_order')->pluck('sort_order')->all(),
        );
    }

    public function test_only_one_active_resolution_item_and_one_active_required_item_are_enforced(): void
    {
        $user = User::factory()->create();
        $resolutionItem = IssueChecklistTemplate::factory()->resolutionMarker()->create([
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->from(route('issue-checklist.index'))
            ->post(route('issue-checklist.store'), [
                'name' => 'Tweede oplossing',
                'is_required' => true,
                'marks_issue_resolved' => true,
                'is_active' => true,
                'sort_order' => 2,
            ])
            ->assertSessionHasErrors('marks_issue_resolved');

        $this->actingAs($user)
            ->from(route('issue-checklist.index'))
            ->patch(route('issue-checklist.update', $resolutionItem), [
                'name' => $resolutionItem->name,
                'is_required' => true,
                'marks_issue_resolved' => true,
                'is_active' => false,
                'sort_order' => 1,
            ])
            ->assertSessionHasErrors('is_required');

        $this->assertTrue($resolutionItem->fresh()->is_active);
    }

    public function test_templates_can_be_moved_and_inactive_templates_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $first = IssueChecklistTemplate::factory()->create(['sort_order' => 1]);
        $second = IssueChecklistTemplate::factory()->create(['sort_order' => 2]);
        $third = IssueChecklistTemplate::factory()->create([
            'is_required' => false,
            'sort_order' => 3,
        ]);

        $this->actingAs($user)
            ->patch(route('issue-checklist.move', $third), ['direction' => 'up'])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [$first->id, $third->id, $second->id],
            IssueChecklistTemplate::query()->orderBy('sort_order')->pluck('id')->all(),
        );
        $this->assertSame(
            [1, 2, 3],
            IssueChecklistTemplate::query()->orderBy('sort_order')->pluck('sort_order')->all(),
        );

        $this->actingAs($user)
            ->delete(route('issue-checklist.destroy', $first))
            ->assertForbidden();

        $third->update(['is_active' => false]);

        $this->actingAs($user)
            ->delete(route('issue-checklist.destroy', $third))
            ->assertSessionHasNoErrors();

        $this->assertModelMissing($third);
    }

    public function test_existing_issue_checklists_remain_a_snapshot_after_template_changes(): void
    {
        $creator = User::factory()->create();
        $template = IssueChecklistTemplate::factory()->resolutionMarker()->create([
            'name' => 'Oorspronkelijke oplossing',
            'sort_order' => 1,
        ]);
        $issue = app(CreateIssue::class)->handle(
            Project::factory()->create(),
            $creator,
            ['title' => 'Storing', 'priority' => IssuePriority::P2],
        );

        $this->actingAs($creator)
            ->patch(route('issue-checklist.update', $template), [
                'name' => 'Aangepaste oplossing',
                'is_required' => true,
                'marks_issue_resolved' => true,
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            'Oorspronkelijke oplossing',
            $issue->checklistItems()->sole()->name,
        );
    }
}
