<?php

namespace App\Policies\Crm;

use App\Models\Crm\CrmTask;
use App\Models\User;
use App\Support\Crm\CrmOwnershipScope;

class CrmTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.ver');
    }

    public function view(User $user, CrmTask $task): bool
    {
        return $user->can('crm.ver') && $this->ownsOrCanViewAll($user, $task);
    }

    public function create(User $user): bool
    {
        return $user->can('crm.crear');
    }

    public function update(User $user, CrmTask $task): bool
    {
        return $user->can('crm.editar') && $this->ownsOrCanViewAll($user, $task);
    }

    public function delete(User $user, CrmTask $task): bool
    {
        return $user->can('crm.eliminar') && $this->ownsOrCanViewAll($user, $task);
    }

    private function ownsOrCanViewAll(User $user, CrmTask $task): bool
    {
        return CrmOwnershipScope::canViewAll($user) || $task->assigned_to === null || $task->assigned_to === $user->id;
    }
}
