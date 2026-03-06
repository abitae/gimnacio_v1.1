<?php

namespace App\Livewire\Usuarios;

use App\Livewire\Concerns\FlashesToast;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithPagination;

class UsuarioLive extends Component
{
    use FlashesToast, WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $perPage = 15;

    public $modalState = [
        'form' => false,
        'delete' => false,
    ];

    public $userId = null;
    public $formData = [
        'name' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
        'estado' => 'activo',
        'role' => '',
    ];

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        $this->authorize('usuarios.view');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->authorize('usuarios.create');
        $this->resetForm();
        $this->modalState['form'] = true;
    }

    public function openEditModal($id)
    {
        $this->authorize('usuarios.update');
        $user = User::with('roles')->find($id);
        if (! $user) {
            $this->flashToast('error', 'Usuario no encontrado');
            return;
        }
        $this->userId = $user->id;
        $this->formData = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
            'estado' => $user->estado ?? 'activo',
            'role' => $user->roles->first()?->name ?? '',
        ];
        $this->modalState['form'] = true;
    }

    public function openDeleteModal($id)
    {
        $this->authorize('usuarios.delete');
        $this->userId = $id;
        $this->modalState['delete'] = true;
    }

    public function save()
    {
        $this->authorize($this->userId ? 'usuarios.update' : 'usuarios.create');
        $rules = [
            'formData.name' => 'required|string|max:255',
            'formData.email' => 'required|email|unique:users,email,' . ($this->userId ?? 'NULL'),
            'formData.estado' => 'required|in:activo,inactivo',
            'formData.role' => 'required|exists:roles,name',
        ];
        if ($this->userId) {
            $rules['formData.password'] = ['nullable', 'string', Password::defaults()];
        } else {
            $rules['formData.password'] = ['required', 'string', 'confirmed', Password::defaults()];
        }

        $this->validate($rules);

        try {
            if ($this->userId) {
                $user = User::findOrFail($this->userId);
                $user->name = $this->formData['name'];
                $user->email = $this->formData['email'];
                $user->estado = $this->formData['estado'];
                if (! empty($this->formData['password'])) {
                    $user->password = Hash::make($this->formData['password']);
                }
                $user->save();
                $user->syncRoles([$this->formData['role']]);
                $this->flashToast('success', 'Usuario actualizado correctamente');
            } else {
                $user = User::create([
                    'name' => $this->formData['name'],
                    'email' => $this->formData['email'],
                    'password' => Hash::make($this->formData['password']),
                    'estado' => $this->formData['estado'],
                ]);
                $user->syncRoles([$this->formData['role']]);
                $this->flashToast('success', 'Usuario creado correctamente');
            }
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function delete()
    {
        $this->authorize('usuarios.delete');
        try {
            $user = User::findOrFail($this->userId);
            if ($user->id === Auth::user()->id) {
                $this->flashToast('error', 'No puedes eliminar tu propio usuario.');
                return;
            }
            $user->delete();
            $this->flashToast('success', 'Usuario eliminado correctamente.');
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->modalState = ['form' => false, 'delete' => false];
        $this->userId = null;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->formData = [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
            'estado' => 'activo',
            'role' => '',
        ];
    }

    public function render()
    {
        $query = User::query()->with('roles');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->roleFilter) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $this->roleFilter));
        }

        $usuarios = $query->orderBy('name')->paginate($this->perPage);
        $roles = \Spatie\Permission\Models\Role::orderBy('name')->get();

        return view('livewire.usuarios.usuario-live', [
            'usuarios' => $usuarios,
            'roles' => $roles,
        ]);
    }
}
