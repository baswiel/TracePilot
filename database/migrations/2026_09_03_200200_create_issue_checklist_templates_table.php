<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_required');
            $table->boolean('marks_issue_resolved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_checklist_templates');
    }
};
