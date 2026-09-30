<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->boolean('knowledge_base_recorded')->default(false)->after('resolution_summary');
            $table->boolean('is_trend')->default(false)->after('knowledge_base_recorded');
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn(['knowledge_base_recorded', 'is_trend']);
        });
    }
};
