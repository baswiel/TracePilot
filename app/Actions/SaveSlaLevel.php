<?php

namespace App\Actions;

use App\Enums\IssuePriority;
use App\Models\SlaLevel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SaveSlaLevel
{
    /** @param array<int, array{priority: string, response_minutes: int, resolution_minutes: int}> $targets */
    public function handle(?SlaLevel $slaLevel, string $name, array $targets): SlaLevel
    {
        Validator::make(['name' => $name, 'targets' => $targets], [
            'name' => ['required', 'string', 'max:255'],
            'targets' => ['required', 'array', 'size:4'],
            'targets.*.priority' => ['required', 'distinct', Rule::enum(IssuePriority::class)],
            'targets.*.response_minutes' => ['required', 'integer', 'between:1,525600'],
            'targets.*.resolution_minutes' => ['required', 'integer', 'between:1,525600'],
        ])->validate();

        return DB::transaction(function () use ($slaLevel, $name, $targets): SlaLevel {
            $slaLevel = $slaLevel === null
                ? new SlaLevel
                : SlaLevel::query()->lockForUpdate()->findOrFail($slaLevel->id);
            $slaLevel->fill(['name' => $name])->save();
            $slaLevel->targets()->delete();
            $slaLevel->targets()->createMany($targets);

            return $slaLevel->load('targets');
        });
    }
}
