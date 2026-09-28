<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_postmortems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('root_cause');
            $table->text('impact');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('postmortem_action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_postmortem_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->foreignId('owner_team_member_id')->nullable()->constrained('team_members')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postmortem_action_items');
        Schema::dropIfExists('issue_postmortems');
    }
};
