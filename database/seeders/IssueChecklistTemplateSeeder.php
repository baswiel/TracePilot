<?php

namespace Database\Seeders;

use App\Models\IssueChecklistTemplate;
use Illuminate\Database\Seeder;

class IssueChecklistTemplateSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            [
                'name' => 'Storing opgelost',
                'is_required' => true,
                'marks_issue_resolved' => true,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Toegevoegd aan Storing Log',
                'is_required' => true,
                'marks_issue_resolved' => false,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Postmortem verstuurd',
                'is_required' => false,
                'marks_issue_resolved' => false,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ])->each(function (array $template): void {
            IssueChecklistTemplate::query()->updateOrCreate(
                ['name' => $template['name']],
                $template,
            );
        });
    }
}
