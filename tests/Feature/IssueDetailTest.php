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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IssueDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_view_an_issue_detail_page(): void
    {
        $user = User::factory()->create();
        $firstResponder = TeamMember::factory()->create(['name' => 'Daan de Vries']);
        $secondResponder = TeamMember::factory()->create(['name' => 'Eva Jansen']);
        $thirdResponder = TeamMember::factory()->create(['name' => 'Fleur Bakker']);
        $project = Project::factory()->create([
            'customer_name' => 'Acme B.V.',
            'first_responder_id' => $firstResponder->id,
            'second_responder_id' => $secondResponder->id,
            'third_responder_id' => $thirdResponder->id,
        ]);
        $issue = Issue::factory()->for($project)->create([
            'title' => 'E-mailverkeer vertraagd',
            'reported_at' => now()->subMinutes(90),
        ]);

        $this->actingAs($user)
            ->get(route('issues.show', $issue))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Issues/Show')
                ->where('issue.title', 'E-mailverkeer vertraagd')
                ->where('issue.project.customer_name', 'Acme B.V.')
                ->where('issue.project.first_responder.name', 'Daan de Vries')
                ->where('issue.project.second_responder.name', 'Eva Jansen')
                ->where('issue.project.third_responder.name', 'Fleur Bakker')
                ->has('issue.elapsed_duration')
                ->has('issue.checklist_progress')
                ->has('teamMembers'));
    }

    public function test_guests_cannot_view_or_modify_an_issue(): void
    {
        $issue = Issue::factory()->create();
        $item = IssueChecklistItem::factory()->for($issue)->create();

        $this->get(route('issues.show', $issue))->assertRedirect(route('login'));
        $this->put(route('issues.update', $issue), $this->updateAttributes())
            ->assertRedirect(route('login'));
        $this->patch(route('issues.checklist.update', [$issue, $item]), ['is_completed' => true])
            ->assertRedirect(route('login'));
    }

    public function test_completing_a_resolution_item_sets_it_to_handling_and_records_an_activity(): void
    {
        $user = User::factory()->create();
        [$issue, $resolutionItem] = $this->issueWithRequiredChecklist();

        $this->actingAs($user)
            ->from(route('issues.show', $issue))
            ->patch(route('issues.checklist.update', [$issue, $resolutionItem]), [
                'is_completed' => true,
                'resolution_summary' => 'De foutieve configuratie is hersteld.',
                'cause' => 'configuration_error',
            ])
            ->assertRedirect(route('issues.show', $issue));

        $issue->refresh();
        $resolutionItem->refresh();

        $this->assertTrue($resolutionItem->is_completed);
        $this->assertNotNull($resolutionItem->completed_at);
        $this->assertSame($user->id, $resolutionItem->completed_by);
        $this->assertSame(IssueStatus::Handling, $issue->status);
        $this->assertNotNull($issue->resolved_at);
        $this->assertSame('De foutieve configuratie is hersteld.', $issue->resolution_summary);
        $this->assertSame('configuration_error', $issue->cause->value);
        $this->assertNull($issue->completed_at);
        $this->assertDatabaseHas('issue_activities', [
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'action' => 'checklist_item_completed',
            'description' => "Checklist-item '{$resolutionItem->name}' voltooid.",
        ]);
    }

    public function test_checklist_transitions_can_complete_and_reopen_an_issue(): void
    {
        $user = User::factory()->create();
        [$issue, $resolutionItem, $requiredItem] = $this->issueWithRequiredChecklist();

        $this->actingAs($user)->patch(
            route('issues.checklist.update', [$issue, $resolutionItem]),
            ['is_completed' => true],
        );
        $this->actingAs($user)->patch(
            route('issues.checklist.update', [$issue, $requiredItem]),
            ['is_completed' => true],
        );
        $issue->refresh();

        $this->assertSame(IssueStatus::Completed, $issue->status);
        $this->assertNotNull($issue->completed_at);

        $this->actingAs($user)->patch(
            route('issues.checklist.update', [$issue, $requiredItem]),
            ['is_completed' => false],
        );
        $issue->refresh();
        $requiredItem->refresh();

        $this->assertFalse($requiredItem->is_completed);
        $this->assertNull($requiredItem->completed_at);
        $this->assertNull($requiredItem->completed_by);
        $this->assertSame(IssueStatus::Handling, $issue->status);
        $this->assertNotNull($issue->resolved_at);
        $this->assertNull($issue->completed_at);

        $this->actingAs($user)->patch(
            route('issues.checklist.update', [$issue, $resolutionItem]),
            ['is_completed' => false],
        );
        $issue->refresh();

        $this->assertSame(IssueStatus::Open, $issue->status);
        $this->assertNull($issue->resolved_at);
        $this->assertNull($issue->completed_at);
        $this->assertDatabaseCount('issue_activities', 4);
        $this->assertDatabaseHas('issue_activities', [
            'issue_id' => $issue->id,
            'action' => 'checklist_item_reopened',
            'description' => "Checklist-item '{$resolutionItem->name}' opnieuw geopend.",
        ]);
    }

    public function test_repeating_the_same_checklist_update_is_idempotent(): void
    {
        $user = User::factory()->create();
        [$issue, $resolutionItem] = $this->issueWithRequiredChecklist();

        $this->actingAs($user)->patch(
            route('issues.checklist.update', [$issue, $resolutionItem]),
            ['is_completed' => true],
        );
        $this->actingAs($user)->patch(
            route('issues.checklist.update', [$issue, $resolutionItem]),
            ['is_completed' => true],
        );

        $this->assertDatabaseCount('issue_activities', 1);
        $this->assertSame(IssueStatus::Handling, $issue->refresh()->status);
    }

    public function test_a_checklist_item_can_be_marked_as_not_applicable(): void
    {
        $user = User::factory()->create();
        [$issue, $resolutionItem, $requiredItem] = $this->issueWithRequiredChecklist();

        $this->actingAs($user)->patch(
            route('issues.checklist.update', [$issue, $resolutionItem]),
            ['is_completed' => true],
        );
        $this->actingAs($user)->patch(
            route('issues.checklist.update', [$issue, $requiredItem]),
            ['is_completed' => true, 'is_not_applicable' => true],
        );

        $requiredItem->refresh();
        $issue->refresh();

        $this->assertTrue($requiredItem->is_completed);
        $this->assertTrue($requiredItem->is_not_applicable);
        $this->assertSame(IssueStatus::Completed, $issue->status);
        $this->assertDatabaseHas('issue_activities', [
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'action' => 'checklist_item_not_applicable',
            'description' => "Checklist-item '{$requiredItem->name}' gemarkeerd als niet van toepassing.",
        ]);
    }

    public function test_a_checklist_item_cannot_be_updated_through_another_issue(): void
    {
        $user = User::factory()->create();
        $issue = Issue::factory()->create();
        $otherIssue = Issue::factory()->create();
        $item = IssueChecklistItem::factory()->for($otherIssue)->create();

        $this->actingAs($user)
            ->patch(route('issues.checklist.update', [$issue, $item]), ['is_completed' => true])
            ->assertNotFound();

        $this->assertFalse($item->refresh()->is_completed);
    }

    public function test_a_user_can_update_issue_details_and_the_change_is_added_to_the_timeline(): void
    {
        $user = User::factory()->create();
        $assignee = TeamMember::factory()->create();
        $issue = Issue::factory()->create();

        $this->actingAs($user)
            ->from(route('issues.show', $issue))
            ->put(route('issues.update', $issue), [
                'title' => 'Nieuwe titel',
                'description' => 'Aangepaste omschrijving',
                'priority' => 'p1',
                'team_member_id' => $assignee->id,
                'knowledge_base_recorded' => true,
                'is_trend' => true,
            ])
            ->assertRedirect(route('issues.show', $issue));

        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'title' => 'Nieuwe titel',
            'description' => 'Aangepaste omschrijving',
            'priority' => IssuePriority::P1->value,
            'team_member_id' => $assignee->id,
            'knowledge_base_recorded' => true,
            'is_trend' => true,
        ]);
        $this->assertDatabaseHas('issue_activities', [
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'action' => 'issue_updated',
            'description' => 'Issuegegevens bijgewerkt.',
        ]);
    }

    public function test_updating_issue_details_validates_the_input(): void
    {
        $user = User::factory()->create();
        $issue = Issue::factory()->create();

        $this->actingAs($user)
            ->from(route('issues.show', $issue))
            ->put(route('issues.update', $issue), [
                'title' => '',
                'priority' => 'hoog',
                'team_member_id' => 999999,
            ])
            ->assertRedirect(route('issues.show', $issue))
            ->assertSessionHasErrors(['title', 'priority', 'team_member_id']);
    }

    public function test_a_user_can_add_an_internal_timeline_entry_with_mentions_and_an_attachment(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $issue = Issue::factory()->create();
        $mentionedMember = TeamMember::factory()->create(['name' => 'Robin Peters']);

        $this->actingAs($user)
            ->from(route('issues.show', $issue))
            ->post(route('issues.timeline.store', $issue), [
                'type' => 'decision',
                'body' => 'We schakelen tijdelijk terug naar de vorige release.',
                'mention_ids' => [$mentionedMember->id],
                'attachment' => UploadedFile::fake()->create('rollback-plan.txt', 12, 'text/plain'),
            ])
            ->assertRedirect(route('issues.show', $issue));

        $activity = $issue->activities()->sole();

        $this->assertSame('decision', $activity->action);
        $this->assertSame('We schakelen tijdelijk terug naar de vorige release.', $activity->description);
        $this->assertSame([
            ['id' => $mentionedMember->id, 'name' => 'Robin Peters'],
        ], $activity->metadata['mentions']);
        $this->assertSame('rollback-plan.txt', $activity->metadata['attachment']['name']);
        Storage::disk('local')->assertExists($activity->metadata['attachment']['path']);

        $this->actingAs($user)
            ->get(route('issues.timeline.attachment.download', [$issue, $activity]))
            ->assertDownload('rollback-plan.txt');
    }

    public function test_a_timeline_entry_requires_a_valid_type_and_body(): void
    {
        $user = User::factory()->create();
        $issue = Issue::factory()->create();

        $this->actingAs($user)
            ->from(route('issues.show', $issue))
            ->post(route('issues.timeline.store', $issue), [
                'type' => 'update',
                'body' => '',
                'mention_ids' => [999999],
            ])
            ->assertRedirect(route('issues.show', $issue))
            ->assertSessionHasErrors(['type', 'body', 'mention_ids.0']);
    }

    public function test_a_user_can_save_a_postmortem_with_owned_action_items(): void
    {
        $user = User::factory()->create();
        $issue = Issue::factory()->create();
        $owner = TeamMember::factory()->create(['name' => 'Jamie Smit']);

        $this->actingAs($user)
            ->from(route('issues.show', $issue))
            ->put(route('issues.postmortem.update', $issue), [
                'root_cause' => 'Een fout in de cache-invalidering hield oude configuratie actief.',
                'impact' => 'Klanten konden gedurende 35 minuten geen wijzigingen opslaan.',
                'action_items' => [[
                    'title' => 'Voeg een regressietest voor cache-invalidering toe',
                    'owner_team_member_id' => $owner->id,
                    'due_date' => '2026-09-30',
                    'is_completed' => false,
                ]],
            ])
            ->assertRedirect(route('issues.show', $issue));

        $postmortem = $issue->refresh()->postmortem;

        $this->assertNotNull($postmortem);
        $this->assertSame('Een fout in de cache-invalidering hield oude configuratie actief.', $postmortem->root_cause);
        $this->assertSame('Klanten konden gedurende 35 minuten geen wijzigingen opslaan.', $postmortem->impact);
        $this->assertDatabaseHas('postmortem_action_items', [
            'issue_postmortem_id' => $postmortem->id,
            'title' => 'Voeg een regressietest voor cache-invalidering toe',
            'owner_team_member_id' => $owner->id,
            'due_date' => '2026-09-30',
        ]);
        $this->assertDatabaseHas('issue_activities', [
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'action' => 'postmortem_updated',
            'description' => 'Postmortem bijgewerkt.',
        ]);
    }

    public function test_resolving_an_issue_without_a_postmortem_marks_its_checklist_step_not_applicable(): void
    {
        $user = User::factory()->create();
        [$issue, $resolutionItem] = $this->issueWithRequiredChecklist();
        $postmortemItem = IssueChecklistItem::factory()->for($issue)->create([
            'name' => 'Postmortem verstuurd',
            'is_required' => true,
            'sort_order' => 3,
        ]);

        $this->actingAs($user)->patch(
            route('issues.checklist.update', [$issue, $resolutionItem]),
            [
                'is_completed' => true,
                'postmortem_required' => false,
            ],
        );

        $issue->refresh();
        $postmortemItem->refresh();

        $this->assertFalse($issue->postmortem_required);
        $this->assertTrue($postmortemItem->is_completed);
        $this->assertTrue($postmortemItem->is_not_applicable);
        $this->assertDatabaseHas('issue_activities', [
            'issue_id' => $issue->id,
            'action' => 'checklist_item_not_applicable',
            'description' => "Checklist-item '{$postmortemItem->name}' gemarkeerd als niet van toepassing.",
        ]);
    }

    /**
     * @return array{0: Issue, 1: IssueChecklistItem, 2?: IssueChecklistItem}
     */
    private function issueWithRequiredChecklist(): array
    {
        $issue = Issue::factory()->create();
        $resolutionItem = IssueChecklistItem::factory()->for($issue)->create([
            'name' => 'Oplossing gecontroleerd',
            'is_required' => true,
            'marks_issue_resolved' => true,
            'sort_order' => 1,
        ]);
        $requiredItem = IssueChecklistItem::factory()->for($issue)->create([
            'name' => 'Storing log bijgewerkt',
            'is_required' => true,
            'marks_issue_resolved' => false,
            'sort_order' => 2,
        ]);

        return [$issue, $resolutionItem, $requiredItem];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function updateAttributes(): array
    {
        return [
            'title' => 'Nieuwe titel',
            'description' => null,
            'priority' => 'p2',
            'team_member_id' => null,
        ];
    }
}
