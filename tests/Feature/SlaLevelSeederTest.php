<?php

namespace Tests\Feature;

use App\Models\SlaLevel;
use Database\Seeders\SlaLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaLevelSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_default_sla_service_levels(): void
    {
        $this->seed(SlaLevelSeeder::class);
        $this->seed(SlaLevelSeeder::class);

        $this->assertSame(4, SlaLevel::query()->count());

        $this->assertTargets('Basic', 1440, 2880, 5760);
        $this->assertTargets('Standard', 1440, 1440, 2880);
        $this->assertTargets('Premium', 120, 240, 2880);
        $this->assertTargets('Enterprise', 60, 240, 1440);
    }

    private function assertTargets(string $name, int $responseMinutes, int $p1P2ResolutionMinutes, int $p3P4ResolutionMinutes): void
    {
        $slaLevel = SlaLevel::query()->where('name', $name)->sole();

        foreach (['p1', 'p2'] as $priority) {
            $this->assertDatabaseHas('sla_targets', [
                'sla_level_id' => $slaLevel->id,
                'priority' => $priority,
                'response_minutes' => $responseMinutes,
                'resolution_minutes' => $p1P2ResolutionMinutes,
            ]);
        }

        foreach (['p3', 'p4'] as $priority) {
            $this->assertDatabaseHas('sla_targets', [
                'sla_level_id' => $slaLevel->id,
                'priority' => $priority,
                'response_minutes' => $responseMinutes,
                'resolution_minutes' => $p3P4ResolutionMinutes,
            ]);
        }
    }
}
