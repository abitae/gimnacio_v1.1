<?php

namespace App\Livewire\Settings\CompanyBranches;

use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Index extends Component
{
    public ?int $empresaId = null;

    public bool $showSucursalModal = false;

    public bool $showAdminModal = false;

    public ?int $sucursalId = null;

    public ?int $adminUserId = null;

    public array $empresaForm = [
        'nombre' => '',
        'razon_social' => '',
        'ruc' => '',
        'direccion' => '',
        'telefono' => '',
        'email' => '',
        'estado' => 'activa',
    ];

    public array $sucursalForm = [
        'codigo' => '',
        'nombre' => '',
        'direccion' => '',
        'telefono' => '',
        'email' => '',
        'estado' => 'activa',
        'es_principal' => false,
    ];

    public array $adminForm = [
        'name' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
        'estado' => 'activo',
        'sucursal_id' => '',
    ];

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME), 403);

        $empresa = Empresa::query()->first();

        if (! $empresa) {
            return;
        }

        $this->empresaId = $empresa->id;
        $this->empresaForm = [
            'nombre' => $empresa->nombre,
            'razon_social' => $empresa->razon_social ?? '',
            'ruc' => $empresa->ruc ?? '',
            'direccion' => $empresa->direccion ?? '',
            'telefono' => $empresa->telefono ?? '',
            'email' => $empresa->email ?? '',
            'estado' => $empresa->estado,
        ];
    }

    public function saveEmpresa(): void
    {
        $validated = $this->validate([
            'empresaForm.nombre' => ['required', 'string', 'max:255'],
            'empresaForm.razon_social' => ['nullable', 'string', 'max:255'],
            'empresaForm.ruc' => ['nullable', 'string', 'max:20'],
            'empresaForm.direccion' => ['nullable', 'string'],
            'empresaForm.telefono' => ['nullable', 'string', 'max:30'],
            'empresaForm.email' => ['nullable', 'email', 'max:255'],
            'empresaForm.estado' => ['required', 'in:activa,inactiva'],
        ]);

        $empresa = Empresa::query()->updateOrCreate(
            ['id' => $this->empresaId],
            $validated['empresaForm']
        );

        $this->empresaId = $empresa->id;
        session()->flash('success', 'Datos de la empresa actualizados correctamente.');
    }

    public function openCreateSucursal(): void
    {
        $this->sucursalId = null;
        $this->sucursalForm = [
            'codigo' => '',
            'nombre' => '',
            'direccion' => '',
            'telefono' => '',
            'email' => '',
            'estado' => 'activa',
            'es_principal' => false,
        ];
        $this->showSucursalModal = true;
    }

    public function openEditSucursal(int $id): void
    {
        $sucursal = Sucursal::query()->findOrFail($id);

        $this->sucursalId = $sucursal->id;
        $this->sucursalForm = [
            'codigo' => $sucursal->codigo,
            'nombre' => $sucursal->nombre,
            'direccion' => $sucursal->direccion ?? '',
            'telefono' => $sucursal->telefono ?? '',
            'email' => $sucursal->email ?? '',
            'estado' => $sucursal->estado,
            'es_principal' => (bool) $sucursal->es_principal,
        ];
        $this->showSucursalModal = true;
    }

    public function saveSucursal(): void
    {
        abort_if(! $this->empresaId, 422, 'Primero debes guardar la empresa.');

        $validated = $this->validate([
            'sucursalForm.codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sucursales', 'codigo')->ignore($this->sucursalId),
            ],
            'sucursalForm.nombre' => ['required', 'string', 'max:255'],
            'sucursalForm.direccion' => ['nullable', 'string'],
            'sucursalForm.telefono' => ['nullable', 'string', 'max:30'],
            'sucursalForm.email' => ['nullable', 'email', 'max:255'],
            'sucursalForm.estado' => ['required', 'in:activa,inactiva'],
            'sucursalForm.es_principal' => ['boolean'],
        ]);

        if ($validated['sucursalForm']['es_principal']) {
            Sucursal::query()->where('empresa_id', $this->empresaId)->update(['es_principal' => false]);
        }

        $sucursal = Sucursal::query()->updateOrCreate(
            ['id' => $this->sucursalId],
            array_merge($validated['sucursalForm'], ['empresa_id' => $this->empresaId])
        );

        $this->showSucursalModal = false;
        session()->flash('success', "Sucursal {$sucursal->nombre} guardada correctamente.");
    }

    public function deleteSucursal(int $id): void
    {
        $sucursal = Sucursal::query()
            ->withCount('usuarios')
            ->findOrFail($id);

        $activeBranchesCount = Sucursal::query()
            ->where('empresa_id', $sucursal->empresa_id)
            ->where('estado', 'activa')
            ->count();

        if ($activeBranchesCount <= 1) {
            session()->flash('error', 'Debes mantener al menos una sucursal activa en el sistema.');

            return;
        }

        if ($sucursal->usuarios_count > 0) {
            session()->flash('error', 'No puedes eliminar una sucursal que tiene usuarios asignados.');

            return;
        }

        if ($sucursal->es_principal) {
            Sucursal::query()
                ->where('empresa_id', $sucursal->empresa_id)
                ->whereKeyNot($sucursal->id)
                ->orderByDesc('estado')
                ->orderBy('nombre')
                ->limit(1)
                ->update(['es_principal' => true]);
        }

        try {
            $nombre = $sucursal->nombre;
            $sucursal->delete();

            session()->flash('success', "Sucursal {$nombre} eliminada correctamente.");
        } catch (QueryException) {
            session()->flash('error', 'No se puede eliminar la sucursal porque tiene datos operativos relacionados.');
        }
    }

    public function openCreateAdmin(): void
    {
        $this->adminUserId = null;
        $this->adminForm = [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
            'estado' => 'activo',
            'sucursal_id' => '',
        ];
        $this->showAdminModal = true;
    }

    public function openEditAdmin(int $id): void
    {
        $user = User::query()
            ->with(['sucursales', 'roles'])
            ->findOrFail($id);

        abort_unless($user->hasRole(PermissionCatalog::BRANCH_ADMIN_ROLE_NAME), 404);

        $this->adminUserId = $user->id;
        $this->adminForm = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
            'estado' => $user->estado ?? 'activo',
            'sucursal_id' => (string) ($user->default_sucursal_id ?? $user->sucursales->first()?->id ?? ''),
        ];
        $this->showAdminModal = true;
    }

    public function saveAdmin(): void
    {
        $rules = [
            'adminForm.name' => ['required', 'string', 'max:255'],
            'adminForm.email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->adminUserId),
            ],
            'adminForm.estado' => ['required', 'in:activo,inactivo'],
            'adminForm.sucursal_id' => ['required', 'exists:sucursales,id'],
        ];

        if ($this->adminUserId) {
            $rules['adminForm.password'] = ['nullable', 'string', Password::defaults()];
        } else {
            $rules['adminForm.password'] = ['required', 'string', 'confirmed', Password::defaults()];
        }

        $validated = $this->validate($rules);
        $sucursalId = (int) $validated['adminForm']['sucursal_id'];

        if ($this->adminUserId) {
            $user = User::query()->findOrFail($this->adminUserId);
            $user->forceFill([
                'name' => $validated['adminForm']['name'],
                'email' => $validated['adminForm']['email'],
                'estado' => $validated['adminForm']['estado'],
                'default_sucursal_id' => $sucursalId,
            ]);

            if (! empty($validated['adminForm']['password'])) {
                $user->password = Hash::make($validated['adminForm']['password']);
            }

            $user->save();
        } else {
            $user = User::query()->create([
                'name' => $validated['adminForm']['name'],
                'email' => $validated['adminForm']['email'],
                'password' => Hash::make($validated['adminForm']['password']),
                'estado' => $validated['adminForm']['estado'],
                'default_sucursal_id' => $sucursalId,
            ]);
        }

        $user->syncRoles([PermissionCatalog::BRANCH_ADMIN_ROLE_NAME]);
        $user->sucursales()->sync([$sucursalId]);

        $this->showAdminModal = false;
        session()->flash('success', 'Administrador de sucursal guardado correctamente.');
    }

    public function deleteAdmin(int $id): void
    {
        $user = User::query()->with('roles')->findOrFail($id);
        abort_if($user->id === Auth::id(), 422, 'No puedes eliminar tu propio usuario.');
        abort_unless($user->hasRole(PermissionCatalog::BRANCH_ADMIN_ROLE_NAME), 404);

        $user->sucursales()->detach();
        $user->syncRoles([]);
        $user->delete();

        session()->flash('success', 'Administrador de sucursal eliminado correctamente.');
    }

    public function render()
    {
        return view('livewire.settings.company-branches.index', [
            'sucursales' => Sucursal::query()
                ->with('empresa')
                ->orderByDesc('es_principal')
                ->orderBy('nombre')
                ->get(),
            'branchAdmins' => User::query()
                ->with(['sucursales', 'roles'])
                ->role(PermissionCatalog::BRANCH_ADMIN_ROLE_NAME)
                ->orderBy('name')
                ->get(),
        ]);
    }
}
