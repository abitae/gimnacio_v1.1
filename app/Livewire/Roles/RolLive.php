<?php

namespace App\Livewire\Roles;

use App\Livewire\Concerns\FlashesToast;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class RolLive extends Component
{
    use FlashesToast, WithPagination;

    public $search = '';
    public $perPage = 15;

    public $modalState = [
        'form' => false,
        'delete' => false,
    ];

    public $roleId = null;
    public $formData = [
        'name' => '',
        'guard_name' => 'web',
        'permissions' => [],
    ];

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        $this->authorize('roles.view');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->authorize('roles.create');
        $this->resetForm();
        $this->modalState['form'] = true;
    }

    public function openEditModal($id)
    {
        $this->authorize('roles.update');
        $role = Role::with('permissions')->find($id);
        if (! $role) {
            $this->flashToast('error', 'Rol no encontrado');
            return;
        }
        $this->roleId = $role->id;
        $this->formData = [
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ];
        $this->modalState['form'] = true;
    }

    public function openDeleteModal($id)
    {
        $this->authorize('roles.delete');
        $this->roleId = $id;
        $this->modalState['delete'] = true;
    }

    public function save()
    {
        $this->authorize($this->roleId ? 'roles.update' : 'roles.create');
        $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.guard_name' => 'required|string|in:web,api',
            'formData.permissions' => 'array',
            'formData.permissions.*' => 'exists:permissions,name',
        ]);

        try {
            if ($this->roleId) {
                $role = Role::findOrFail($this->roleId);
                $role->name = $this->formData['name'];
                $role->guard_name = $this->formData['guard_name'];
                $role->save();
                $role->syncPermissions($this->formData['permissions'] ?? []);
                $this->flashToast('success', 'Rol actualizado correctamente');
            } else {
                $role = Role::create([
                    'name' => $this->formData['name'],
                    'guard_name' => $this->formData['guard_name'],
                ]);
                $role->syncPermissions($this->formData['permissions'] ?? []);
                $this->flashToast('success', 'Rol creado correctamente');
            }
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function delete()
    {
        $this->authorize('roles.delete');
        try {
            $role = Role::withCount('users')->findOrFail($this->roleId);
            if ($role->users_count > 0) {
                $this->flashToast('error', 'No se puede eliminar el rol: tiene ' . $role->users_count . ' usuario(s) asignado(s). Reasigna los usuarios antes de eliminar.');
                return;
            }
            $role->delete();
            $this->flashToast('success', 'Rol eliminado correctamente.');
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->modalState = ['form' => false, 'delete' => false];
        $this->roleId = null;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->formData = [
            'name' => '',
            'guard_name' => 'web',
            'permissions' => [],
        ];
    }

    public function render()
    {
        $query = Role::query()->withCount(['permissions', 'users']);

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $roles = $query->orderBy('name')->paginate($this->perPage);
        $permissions = \Spatie\Permission\Models\Permission::orderBy('name')->get();

        return view('livewire.roles.rol-live', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }
}
