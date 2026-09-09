<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('sla_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sla_level_id')->constrained()->cascadeOnDelete();
            $table->string('priority', 2);
            $table->unsignedInteger('response_minutes');
            $table->unsignedInteger('resolution_minutes');
            $table->timestamps();
            $table->unique(['sla_level_id', 'priority']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('sla_level_id')
                ->nullable()
                ->after('description')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sla_level_id');
        });

        Schema::dropIfExists('sla_targets');
        Schema::dropIfExists('sla_levels');
    }
};
