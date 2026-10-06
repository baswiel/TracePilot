<?php

namespace Tests\Feature;

use App\Actions\SaveSlaLevel;
use App\Models\Project;
use App\Models\SlaLevel;
use App\Models\SlaTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
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

    public function test_failed_target_storage_rolls_back_the_entire_level(): void
    {
        $save = app(SaveSlaLevel::class);
        $level = $save->handle(null, 'Gold', $this->targets());
        SlaTarget::creating(function (): void {
            throw new RuntimeException('Doelopslag mislukt.');
        });

        try {
            foreach ([$level, null] as $existingLevel) {
                try {
                    $save->handle($existingLevel, 'Silver', $this->targets());
                    $this->fail('Opslag had moeten mislukken.');
                } catch (RuntimeException $exception) {
                    $this->assertSame('Doelopslag mislukt.', $exception->getMessage());
                }
            }
        } finally {
            SlaTarget::flushEventListeners();
        }

        $this->assertDatabaseCount('sla_levels', 1);
        $this->assertSame('Gold', $level->refresh()->name);
        $this->assertCount(4, $level->targets()->get());
        $this->assertDatabaseMissing('sla_levels', ['name' => 'Silver']);
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
