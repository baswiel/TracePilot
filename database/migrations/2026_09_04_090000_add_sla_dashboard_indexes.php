<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->index(['is_active', 'name']);
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->index(['status', 'priority', 'reported_at']);
            $table->index(['status', 'completed_at']);
            $table->index(['project_id', 'status', 'reported_at']);
            $table->index(['assigned_to', 'status', 'reported_at']);
        });

        Schema::table('issue_checklist_templates', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order']);
        });

        Schema::table('issue_checklist_items', function (Blueprint $table) {
            $table->index(['issue_id', 'is_completed']);
            $table->index(['issue_id', 'is_required', 'is_completed']);
        });

        Schema::table('issue_activities', function (Blueprint $table) {
            $table->index(['issue_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'name']);
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropIndex(['status', 'priority', 'reported_at']);
            $table->dropIndex(['status', 'completed_at']);
            $table->dropIndex(['project_id', 'status', 'reported_at']);
            $table->dropIndex(['assigned_to', 'status', 'reported_at']);
        });

        Schema::table('issue_checklist_templates', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'sort_order']);
        });

        Schema::table('issue_checklist_items', function (Blueprint $table) {
            $table->dropIndex(['issue_id', 'is_completed']);
            $table->dropIndex(['issue_id', 'is_required', 'is_completed']);
        });

        Schema::table('issue_activities', function (Blueprint $table) {
            $table->dropIndex(['issue_id', 'created_at']);
        });
    }
};
