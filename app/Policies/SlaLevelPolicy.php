<?php

namespace App\Policies;

use App\Models\SlaLevel;
use App\Models\User;

class SlaLevelPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SlaLevel $slaLevel): bool
    {
        return true;
    }

    public function delete(User $user, SlaLevel $slaLevel): bool
    {
        return ! $slaLevel->projects()->exists();
    }
}
