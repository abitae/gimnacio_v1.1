<?php

namespace App\Policies\Crm;

use App\Models\Crm\Deal;
use App\Models\User;
use App\Support\Crm\CrmOwnershipScope;

class DealPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.ver');
    }

    public function view(User $user, Deal $deal): bool
    {
        return $user->can('crm.ver') && $this->ownsOrCanViewAll($user, $deal);
    }

    public function create(User $user): bool
    {
        return $user->can('crm.crear');
    }

    public function update(User $user, Deal $deal): bool
    {
        return $user->can('crm.editar') && $this->ownsOrCanViewAll($user, $deal);
    }

    public function delete(User $user, Deal $deal): bool
    {
        return $user->can('crm.eliminar') && $this->ownsOrCanViewAll($user, $deal);
    }

    private function ownsOrCanViewAll(User $user, Deal $deal): bool
    {
        return CrmOwnershipScope::canViewAll($user) || $deal->assigned_to === null || $deal->assigned_to === $user->id;
    }
}
