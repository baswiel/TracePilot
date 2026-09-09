<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\SlaLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaLevelSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_manage_sla_levels_and_assign_one_to_a_project(): void
    {
        $user = User::factory()->create();
        $targets = $this->targets();

        $this->actingAs($user)->post(route('sla-levels.store'), [
            'name' => 'Gold',
            'targets' => $targets,
        ])->assertRedirect(route('sla-levels.index'));

        $slaLevel = SlaLevel::query()->sole();
        $this->assertCount(4, $slaLevel->targets);
        $this->assertDatabaseHas('sla_targets', [
            'sla_level_id' => $slaLevel->id,
            'priority' => 'p1',
            'response_minutes' => 30,
            'resolution_minutes' => 120,
        ]);

        $project = Project::factory()->create(['sla_level_id' => $slaLevel->id]);

        $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('projects.show', $project))
            ->assertJsonPath('props.project.sla_level.name', 'Gold')
            ->assertJsonPath('props.project.sla_level.targets.0.priority', 'p1');
    }

    public function test_an_sla_level_requires_each_priority_once(): void
    {
        $user = User::factory()->create();
        $targets = $this->targets();
        $targets[3]['priority'] = 'p3';

        $this->actingAs($user)
            ->from(route('sla-levels.index'))
            ->post(route('sla-levels.store'), ['name' => 'Gold', 'targets' => $targets])
            ->assertRedirect(route('sla-levels.index'))
            ->assertSessionHasErrors('targets.3.priority');
    }

    /** @return array<int, array{priority: string, response_minutes: int, resolution_minutes: int}> */
    private function targets(): array
    {
        return [
            ['priority' => 'p1', 'response_minutes' => 30, 'resolution_minutes' => 120],
            ['priority' => 'p2', 'response_minutes' => 60, 'resolution_minutes' => 240],
            ['priority' => 'p3', 'response_minutes' => 240, 'resolution_minutes' => 1440],
            ['priority' => 'p4', 'response_minutes' => 480, 'resolution_minutes' => 2880],
        ];
    }
}
