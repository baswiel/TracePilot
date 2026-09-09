<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('name')->constrained()->nullOnDelete();
        });

        DB::table('projects')->whereNotNull('customer_name')->where('customer_name', '!=', '')
            ->orderBy('id')->get(['id', 'customer_name'])->each(function (object $project): void {
                $customerId = DB::table('customers')->where('name', $project->customer_name)->value('id');

                if ($customerId === null) {
                    $customerId = DB::table('customers')->insertGetId([
                        'name' => $project->customer_name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('projects')->where('id', $project->id)->update(['customer_id' => $customerId]);
            });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::dropIfExists('customers');
    }
};
