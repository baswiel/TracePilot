<?php

namespace Database\Seeders;

use App\Enums\IssuePriority;
use App\Models\SlaLevel;
use Illuminate\Database\Seeder;

class SlaLevelSeeder extends Seeder
{
    private const MINUTES_PER_WORKDAY = 1_440;

    /**
     * Seed the default SLA service levels.
     */
    public function run(): void
    {
        collect([
            'Basic' => [
                'response_minutes' => self::MINUTES_PER_WORKDAY,
                'resolution_p1_p2_minutes' => 2 * self::MINUTES_PER_WORKDAY,
                'resolution_p3_p4_minutes' => 4 * self::MINUTES_PER_WORKDAY,
            ],
            'Standard' => [
                'response_minutes' => self::MINUTES_PER_WORKDAY,
                'resolution_p1_p2_minutes' => self::MINUTES_PER_WORKDAY,
                'resolution_p3_p4_minutes' => 2 * self::MINUTES_PER_WORKDAY,
            ],
            'Premium' => [
                'response_minutes' => 2 * 60,
                'resolution_p1_p2_minutes' => 4 * 60,
                'resolution_p3_p4_minutes' => 2 * self::MINUTES_PER_WORKDAY,
            ],
            'Enterprise' => [
                'response_minutes' => 60,
                'resolution_p1_p2_minutes' => 4 * 60,
                'resolution_p3_p4_minutes' => self::MINUTES_PER_WORKDAY,
            ],
        ])->each(function (array $targets, string $name): void {
            $slaLevel = SlaLevel::query()->updateOrCreate(['name' => $name]);

            foreach (IssuePriority::cases() as $priority) {
                $slaLevel->targets()->updateOrCreate(
                    ['priority' => $priority],
                    [
                        'response_minutes' => $targets['response_minutes'],
                        'resolution_minutes' => in_array($priority, [IssuePriority::P1, IssuePriority::P2], true)
                            ? $targets['resolution_p1_p2_minutes']
                            : $targets['resolution_p3_p4_minutes'],
                    ],
                );
            }
        });
    }
}
