<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\IssueActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueActivity>
 */
class IssueActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'issue_id' => Issue::factory(),
            'user_id' => null,
            'action' => 'issue_created',
            'description' => 'Storing aangemaakt.',
            'metadata' => null,
            'created_at' => now(),
        ];
    }
}
