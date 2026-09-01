<?php

namespace App\Policies\Crm;

use App\Models\Crm\CrmActivity;
use App\Models\User;
use App\Support\Crm\CrmOwnershipScope;

class CrmActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.ver');
    }

    public function view(User $user, CrmActivity $activity): bool
    {
        return $user->can('crm.ver') && $this->ownsOrCanViewAll($user, $activity);
    }

    public function create(User $user): bool
    {
        return $user->can('crm.crear');
    }

    public function update(User $user, CrmActivity $activity): bool
    {
        return $user->can('crm.editar') && $this->ownsOrCanViewAll($user, $activity);
    }

    public function delete(User $user, CrmActivity $activity): bool
    {
        return $user->can('crm.eliminar') && $this->ownsOrCanViewAll($user, $activity);
    }

    private function ownsOrCanViewAll(User $user, CrmActivity $activity): bool
    {
        if (CrmOwnershipScope::canViewAll($user)) {
            return true;
        }

        if (! $activity->lead && ! $activity->cliente && ! $activity->deal) {
            return true;
        }

        return ($activity->lead && ($activity->lead->assigned_to === null || $activity->lead->assigned_to === $user->id))
            || ($activity->cliente && ($activity->cliente->asesor_crm_id === null || $activity->cliente->asesor_crm_id === $user->id))
            || ($activity->deal && ($activity->deal->assigned_to === null || $activity->deal->assigned_to === $user->id));
    }
}
