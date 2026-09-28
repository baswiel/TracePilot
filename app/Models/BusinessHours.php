<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array<int, int> $working_days
 * @property string $starts_at
 * @property string $ends_at
 */
#[Fillable(['working_days', 'starts_at', 'ends_at'])]
class BusinessHours extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'working_days' => 'array',
        ];
    }
}
