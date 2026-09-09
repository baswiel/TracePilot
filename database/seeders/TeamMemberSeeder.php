<?php

namespace Database\Seeders;

use App\Models\TeamMember;
use Illuminate\Database\Seeder;

class TeamMemberSeeder extends Seeder
{
    /**
     * Seed the local development response team.
     */
    public function run(): void
    {
        collect([
            ['name' => 'Bas van der Wiel', 'email' => 'bas.vanderwiel@rapide.software'],
            ['name' => 'Christof', 'email' => 'christof@rapide.software'],
            ['name' => 'Hessel', 'email' => 'hessel@rapide.software'],
            ['name' => 'Jasper', 'email' => 'jasper@rapide.software'],
            ['name' => 'Siemen', 'email' => 'siemen@rapide.software'],
            ['name' => 'Gilles', 'email' => 'gilles@rapide.software'],
            ['name' => 'Max', 'email' => 'max@rapide.software'],
        ])->each(function (array $teamMember): void {
            TeamMember::query()->updateOrCreate(
                ['email' => $teamMember['email']],
                $teamMember,
            );
        });
    }
}
