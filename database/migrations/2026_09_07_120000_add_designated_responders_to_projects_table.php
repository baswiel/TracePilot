<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('first_responder_id')
                ->nullable()
                ->after('contact_phone')
                ->constrained('team_members')
                ->nullOnDelete();
            $table->foreignId('second_responder_id')
                ->nullable()
                ->after('first_responder_id')
                ->constrained('team_members')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('second_responder_id');
            $table->dropConstrainedForeignId('first_responder_id');
        });
    }
};
