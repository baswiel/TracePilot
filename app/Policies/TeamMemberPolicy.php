<?php

namespace App\Policies;

use App\Models\TeamMember;
use App\Models\User;

class TeamMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TeamMember $teamMember): bool
    {
        return true;
    }

    public function delete(User $user, TeamMember $teamMember): bool
    {
        return true;
    }
}
