<?php

namespace App\Livewire\Usuarios;

use App\Livewire\Concerns\FlashesToast;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\SucursalContext;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithPagination;

class UsuarioLive extends Component
{
    use FlashesToast;
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public int $perPage = 15;

    public array $modalState = ['form' => false, 'delete' => false];

    public ?int $userId = null;

    public array $formData = [
        'name' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
        'estado' => 'activo',
        'role' => '',
        'sucursal_ids' => [],
        'default_sucursal_id' => '',
    ];

    protected $paginationTheme = 'tailwind';

    protected SucursalContext $sucursalContext;

    public function boot(SucursalContext $sucursalContext): void
    {
        $this->sucursalContext = $sucursalContext;
    }

    public function mount(): void
    {
        $this->authorize('usuario.ver');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('usuario.crear');
        $this->resetForm();
        $this->modalState['form'] = true;
    }

    public function openEditModal(int $id): void
    {
        $this->authorize('usuario.editar');

        $user = User::with(['roles', 'sucursales'])->find($id);

        if (! $user) {
            $this->flashToast('error', 'Usuario no encontrado');

            return;
        }

        if ($user->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME) || $user->hasRole(PermissionCatalog::BRANCH_ADMIN_ROLE_NAME)) {
            $this->flashToast('error', 'Este usuario administrativo especial solo puede gestionarse desde el modulo de super administracion.');

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
            'sucursal_ids' => $user->sucursales->pluck('id')->map(fn ($id) => (string) $id)->all(),
            'default_sucursal_id' => (string) ($user->default_sucursal_id ?? ''),
        ];
        $this->modalState['form'] = true;
    }

    public function openDeleteModal(int $id): void
    {
        $this->authorize('usuario.eliminar');
        $this->userId = $id;
        $this->modalState['delete'] = true;
    }

    public function save(): void
    {
        $this->authorize($this->userId ? 'usuario.editar' : 'usuario.crear');

        $rules = [
            'formData.name' => 'required|string|max:255',
            'formData.email' => 'required|email|unique:users,email,'.($this->userId ?? 'NULL'),
            'formData.estado' => 'required|in:activo,inactivo',
            'formData.role' => 'required|exists:roles,name',
            'formData.sucursal_ids' => 'required|array|min:1',
            'formData.sucursal_ids.*' => 'exists:sucursales,id',
            'formData.default_sucursal_id' => 'nullable|exists:sucursales,id',
        ];

        if ($this->userId) {
            $rules['formData.password'] = ['nullable', 'string', Password::defaults()];
        } else {
            $rules['formData.password'] = ['required', 'string', 'confirmed', Password::defaults()];
        }

        $this->validate($rules);

        $isSuperAdmin = Auth::user()?->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME) ?? false;

        if (! $isSuperAdmin && in_array($this->formData['role'], [
            PermissionCatalog::SUPER_ADMIN_ROLE_NAME,
            PermissionCatalog::BRANCH_ADMIN_ROLE_NAME,
        ], true)) {
            $this->addError('formData.role', 'Este rol solo puede asignarse desde el módulo de super administración.');

            return;
        }

        if (! empty($this->formData['default_sucursal_id']) && ! in_array((string) $this->formData['default_sucursal_id'], $this->formData['sucursal_ids'], true)) {
            $this->addError('formData.default_sucursal_id', 'La sucursal predeterminada debe pertenecer a las sucursales asignadas.');

            return;
        }

        $sucursalIds = collect($this->formData['sucursal_ids'])->map(fn ($id) => (int) $id)->values()->all();
        $defaultSucursalId = ! empty($this->formData['default_sucursal_id'])
            ? (int) $this->formData['default_sucursal_id']
            : $sucursalIds[0];

        try {
            if ($this->userId) {
                $user = User::findOrFail($this->userId);
                $user->name = $this->formData['name'];
                $user->email = $this->formData['email'];
                $user->estado = $this->formData['estado'];
                $user->default_sucursal_id = $defaultSucursalId;

                if (! empty($this->formData['password'])) {
                    $user->password = Hash::make($this->formData['password']);
                }

                $user->save();
                $user->syncRoles([$this->formData['role']]);
                $user->sucursales()->sync($sucursalIds);
                $this->flashToast('success', 'Usuario actualizado correctamente');
            } else {
                $user = User::create([
                    'name' => $this->formData['name'],
                    'email' => $this->formData['email'],
                    'password' => Hash::make($this->formData['password']),
                    'estado' => $this->formData['estado'],
                    'default_sucursal_id' => $defaultSucursalId,
                ]);
                $user->syncRoles([$this->formData['role']]);
                $user->sucursales()->sync($sucursalIds);
                $this->flashToast('success', 'Usuario creado correctamente');
            }

            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function delete(): void
    {
        $this->authorize('usuario.eliminar');

        try {
            $user = User::findOrFail($this->userId);

            if ($user->id === Auth::id()) {
                $this->flashToast('error', 'No puedes eliminar tu propio usuario.');

                return;
            }

            if ($user->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME) || $user->hasRole(PermissionCatalog::BRANCH_ADMIN_ROLE_NAME)) {
                $this->flashToast('error', 'Este usuario administrativo especial solo puede gestionarse desde el modulo de super administracion.');

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

    public function closeModal(): void
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
            'sucursal_ids' => [],
            'default_sucursal_id' => '',
        ];
    }

    public function render()
    {
        $query = User::query()
            ->with(['roles', 'sucursales'])
            ->whereHas('sucursales', fn ($builder) => $builder->whereKey($this->sucursalContext->getSucursalId()))
            ->whereDoesntHave('roles', fn ($builder) => $builder->whereIn('name', [
                PermissionCatalog::SUPER_ADMIN_ROLE_NAME,
                PermissionCatalog::BRANCH_ADMIN_ROLE_NAME,
            ]));

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->roleFilter) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $this->roleFilter));
        }

        $roles = \Spatie\Permission\Models\Role::query()
            ->when(
                ! (Auth::user()?->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME) ?? false),
                fn ($query) => $query->whereNotIn('name', [
                    PermissionCatalog::SUPER_ADMIN_ROLE_NAME,
                    PermissionCatalog::BRANCH_ADMIN_ROLE_NAME,
                ])
            )
            ->orderBy('name')
            ->get();

        return view('livewire.usuarios.usuario-live', [
            'usuarios' => $query->orderBy('name')->paginate($this->perPage),
            'roles' => $roles,
            'sucursales' => Sucursal::query()
                ->with('empresa')
                ->where('estado', 'activa')
                ->orderByDesc('es_principal')
                ->orderBy('nombre')
                ->get(),
        ]);
    }
}
