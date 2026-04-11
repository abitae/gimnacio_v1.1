<?php

namespace App\Policies\Crm;

use App\Models\Crm\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.ver');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->can('crm.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('crm.crear');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->can('crm.editar');
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->can('crm.eliminar');
    }
}
