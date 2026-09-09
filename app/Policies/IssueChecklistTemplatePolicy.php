<?php

namespace App\Policies;

use App\Models\IssueChecklistTemplate;
use App\Models\User;

class IssueChecklistTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, IssueChecklistTemplate $template): bool
    {
        return true;
    }

    public function delete(User $user, IssueChecklistTemplate $template): bool
    {
        return ! $template->is_active;
    }
}
