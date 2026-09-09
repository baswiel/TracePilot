<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the local development user.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'bas.vanderwiel@rapide.software'],
            [
                'name' => 'Bas van der Wiel',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );
    }
}
