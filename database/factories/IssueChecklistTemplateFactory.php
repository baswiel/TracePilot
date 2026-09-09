<?php

namespace Database\Factories;

use App\Models\IssueChecklistTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueChecklistTemplate>
 */
class IssueChecklistTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'is_required' => true,
            'marks_issue_resolved' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function resolutionMarker(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
            'marks_issue_resolved' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
