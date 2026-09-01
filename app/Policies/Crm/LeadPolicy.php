<?php

namespace App\Policies\Crm;

use App\Models\Crm\Lead;
use App\Models\User;
use App\Support\Crm\CrmOwnershipScope;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.ver');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->can('crm.ver') && $this->ownsOrCanViewAll($user, $lead);
    }

    public function create(User $user): bool
    {
        return $user->can('crm.crear');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->can('crm.editar') && $this->ownsOrCanViewAll($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->can('crm.eliminar') && $this->ownsOrCanViewAll($user, $lead);
    }

    public function convert(User $user, Lead $lead): bool
    {
        return ($user->can('crm.convertir') || $user->can('crm.editar')) && $this->ownsOrCanViewAll($user, $lead);
    }

    public function reassign(User $user, Lead $lead): bool
    {
        return $user->can('crm.reasignar');
    }

    private function ownsOrCanViewAll(User $user, Lead $lead): bool
    {
        return CrmOwnershipScope::canViewAll($user) || $lead->assigned_to === null || $lead->assigned_to === $user->id;
    }
}
