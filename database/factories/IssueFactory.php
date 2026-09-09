<?php

namespace Database\Factories;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'priority' => fake()->randomElement(IssuePriority::cases()),
            'status' => IssueStatus::Open,
            'reported_at' => now(),
            'resolved_at' => null,
            'completed_at' => null,
            'team_member_id' => null,
            'created_by' => User::factory(),
        ];
    }
}
