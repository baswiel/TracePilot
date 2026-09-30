<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();
            $table->json('working_days');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();
        });

        DB::table('business_hours')->insert([
            'working_days' => json_encode([1, 2, 3, 4, 5], JSON_THROW_ON_ERROR),
            'starts_at' => '09:00',
            'ends_at' => '17:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('business_hours');
    }
};
