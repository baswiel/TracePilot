<?php

namespace App\Policies;

use App\Models\User;

class BusinessHoursPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function update(User $user): bool
    {
        return true;
    }
}
