<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\IssueChecklistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueChecklistItem>
 */
class IssueChecklistItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'issue_id' => Issue::factory(),
            'name' => fake()->sentence(3),
            'is_required' => true,
            'marks_issue_resolved' => false,
            'is_completed' => false,
            'completed_at' => null,
            'completed_by' => null,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
