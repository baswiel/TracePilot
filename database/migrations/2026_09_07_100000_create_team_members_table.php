<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->foreignId('team_member_id')
                ->nullable()
                ->after('assigned_to')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropIndex(['assigned_to', 'status', 'reported_at']);
            $table->index(['team_member_id', 'status', 'reported_at']);
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_member_id');
            $table->dropIndex(['team_member_id', 'status', 'reported_at']);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['assigned_to', 'status', 'reported_at']);
        });

        Schema::dropIfExists('team_members');
    }
};
