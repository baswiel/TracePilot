<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_notifications_keep_the_latest_five_active_issues_and_project_names(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Klantportaal']);
        $issues = collect();

        foreach (range(1, 6) as $index) {
            $issues->push(Issue::factory()->for($project)->create([
                'title' => 'Storing '.$index,
                'description' => str_repeat('Lange omschrijving. ', 1000),
                'status' => $index === 6 ? 'handling' : 'open',
                'reported_at' => now()->subMinutes(7 - $index),
            ]));
        }

        Issue::factory()->for($project)->create([
            'status' => 'completed',
            'reported_at' => now(),
        ]);

        $response = $this->actingAs($user)->asInertiaRequest()->get(route('dashboard'));
        $response->assertOk()->assertJsonPath('props.activeIssuesCount', 6);
        $notifications = $response->json('props.notifications');

        $this->assertSame($issues->reverse()->take(5)->pluck('id')->all(), array_column($notifications, 'id'));
        $this->assertSame([
            'id' => $issues->last()->id,
            'title' => 'Storing 6',
            'project' => 'Klantportaal',
            'status' => 'handling',
        ], $notifications[0]);
        $this->assertCount(5, $notifications);
    }

    public function test_guests_have_no_shared_notifications(): void
    {
        $response = $this->asInertiaRequest()->get(route('login'));
        $response->assertOk()
            ->assertJsonPath('props.notifications', [])
            ->assertJsonPath('props.activeIssuesCount', 0);
    }
}
