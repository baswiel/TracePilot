<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'Edemocracy.',
            'Combi motors',
            'Advieswidgets',
            'Folkersma',
            'quli',
            'routebureau',
            'vereniging eigen huis',
            'Incitus',
            'Vereniging eigen huis',
        ])->each(fn (string $name) => Customer::query()->firstOrCreate(['name' => $name]));
    }
}
