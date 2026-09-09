<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('sla_first_response_minutes')->nullable()->after('description');
            $table->unsignedInteger('sla_resolution_minutes')->nullable()->after('sla_first_response_minutes');
            $table->string('contact_name')->nullable()->after('sla_resolution_minutes');
            $table->string('contact_email')->nullable()->after('contact_name');
            $table->string('contact_phone')->nullable()->after('contact_email');
        });

        Schema::create('project_team_member', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_member_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'team_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_team_member');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'sla_first_response_minutes',
                'sla_resolution_minutes',
                'contact_name',
                'contact_email',
                'contact_phone',
            ]);
        });
    }
};
