<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    /** @return array{name: string} */
    public function definition(): array
    {
        return ['name' => fake()->unique()->company()];
    }
}
