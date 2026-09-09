<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_manage_team_members_without_creating_login_accounts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('team.store'), [
                'name' => 'Sam Jansen',
                'email' => 'sam@example.test',
            ])
            ->assertRedirect(route('team.index'));

        $teamMember = TeamMember::query()->sole();

        $this->assertSame(1, User::query()->count());
        $this->assertSame('Sam Jansen', $teamMember->name);

        $this->actingAs($user)
            ->patch(route('team.update', $teamMember), [
                'name' => 'Sam de Jansen',
                'email' => null,
            ])
            ->assertRedirect(route('team.index'));

        $this->assertDatabaseHas('team_members', [
            'id' => $teamMember->id,
            'name' => 'Sam de Jansen',
            'email' => null,
        ]);
    }

    public function test_deleting_a_team_member_unassigns_their_issues(): void
    {
        $user = User::factory()->create();
        $teamMember = TeamMember::factory()->create();
        $issue = Issue::factory()->create(['team_member_id' => $teamMember->id]);

        $this->actingAs($user)
            ->delete(route('team.destroy', $teamMember))
            ->assertRedirect(route('team.index'));

        $this->assertDatabaseMissing('team_members', ['id' => $teamMember->id]);
        $this->assertNull($issue->refresh()->team_member_id);
    }
}
