<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Project;
use App\Models\TeamMember;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Seed the local development projects.
     */
    public function run(): void
    {
        $teamMembers = TeamMember::query()->get()->keyBy('email');

        collect([
            [
                'name' => 'WieKiesJij', 'customer' => 'Edemocracy.',
                'description' => 'Het online klantportaal van Acme.',
                'first_responder' => 'bas.vanderwiel@rapide.software', 'second_responder' => 'christof@rapide.software',
            ],
            [
                'name' => 'Motor2go', 'customer' => 'Combi motors',
                'description' => 'De publieke website van Acme.',
                'first_responder' => 'bas.vanderwiel@rapide.software', 'second_responder' => 'christof@rapide.software',
            ],
            [
                'name' => 'Widgets', 'customer' => 'Advieswidgets',
                'description' => 'De publieke website van Acme.',
                'first_responder' => 'gilles@rapide.software', 'second_responder' => 'bas.vanderwiel@rapide.software',
            ],
            [
                'name' => 'Ctip', 'customer' => 'Folkersma', 'description' => 'De publieke website van Acme.',
                'first_responder' => 'bas.vanderwiel@rapide.software', 'second_responder' => 'christof@rapide.software',
            ],
            [
                'name' => 'Hurkmans', 'customer' => 'Folkersma', 'description' => 'De publieke website van Acme.',
                'first_responder' => 'christof@rapide.software', 'second_responder' => 'bas.vanderwiel@rapide.software',
            ],
            ['name' => 'Quli', 'customer' => 'quli', 'first_responder' => 'christof@rapide.software', 'second_responder' => 'bas.vanderwiel@rapide.software'],
            ['name' => 'Routebureau', 'customer' => 'routebureau', 'first_responder' => 'max@rapide.software', 'second_responder' => 'christof@rapide.software'],
            ['name' => 'App app web', 'customer' => 'vereniging eigen huis', 'first_responder' => 'christof@rapide.software', 'second_responder' => 'bas.vanderwiel@rapide.software'],
            ['name' => 'app app app', 'customer' => 'vereniging eigen huis', 'first_responder' => 'christof@rapide.software', 'second_responder' => 'bas.vanderwiel@rapide.software'],
            ['name' => 'BNP', 'customer' => 'Advieswidgets', 'first_responder' => 'gilles@rapide.software', 'second_responder' => 'bas.vanderwiel@rapide.software'],
            ['name' => 'veh', 'customer' => 'Advieswidgets', 'first_responder' => 'gilles@rapide.software', 'second_responder' => 'bas.vanderwiel@rapide.software'],
            ['name' => 'Voip', 'customer' => 'Advieswidgets', 'first_responder' => 'gilles@rapide.software', 'second_responder' => 'bas.vanderwiel@rapide.software'],
            ['name' => 'Incitus', 'customer' => 'Incitus', 'first_responder' => 'bas.vanderwiel@rapide.software', 'second_responder' => 'christof@rapide.software'],
            ['name' => 'Leadwacht', 'customer' => 'Vereniging eigen huis', 'first_responder' => 'bas.vanderwiel@rapide.software', 'second_responder' => 'christof@rapide.software'],
        ])->each(function (array $projectData) use ($teamMembers): void {
            $customer = Customer::query()->where('name', $projectData['customer'])->sole();
            $firstResponder = $teamMembers->get($projectData['first_responder']);
            $secondResponder = $teamMembers->get($projectData['second_responder']);
            $thirdResponder = $teamMembers->get('hessel@rapide.software');

            unset($projectData['customer'], $projectData['first_responder'], $projectData['second_responder']);

            Project::query()->updateOrCreate(
                ['name' => $projectData['name']],
                [
                    ...$projectData,
                    'customer_id' => $customer->id,
                    'first_responder_id' => $firstResponder->id,
                    'second_responder_id' => $secondResponder->id,
                    'third_responder_id' => $thirdResponder->id,
                    'is_active' => true,
                ],
            );
        });
    }
}
