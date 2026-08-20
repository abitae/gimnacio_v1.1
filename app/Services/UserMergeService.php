<?php

namespace App\Services;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserMergeService
{
    /**
     * @return list<array{table: string, column: string}>
     */
    public static function userForeignKeys(): array
    {
        return [
            ['table' => 'cliente_matriculas', 'column' => 'asesor_id'],
            ['table' => 'cliente_membresias', 'column' => 'asesor_id'],
            ['table' => 'clientes', 'column' => 'created_by'],
            ['table' => 'clientes', 'column' => 'updated_by'],
            ['table' => 'clientes', 'column' => 'trainer_user_id'],
            ['table' => 'pagos', 'column' => 'registrado_por'],
            ['table' => 'cajas', 'column' => 'usuario_id'],
            ['table' => 'caja_movimientos', 'column' => 'usuario_id'],
            ['table' => 'ventas', 'column' => 'usuario_id'],
            ['table' => 'venta_pagos', 'column' => 'usuario_id'],
            ['table' => 'movimientos_inventario', 'column' => 'usuario_id'],
            ['table' => 'ticket_reprints', 'column' => 'user_id'],
            ['table' => 'imports', 'column' => 'imported_by'],
            ['table' => 'cliente_fidelizacion_mensajes', 'column' => 'user_id'],
            ['table' => 'cliente_plan_traspasos', 'column' => 'registrado_por'],
            ['table' => 'employees', 'column' => 'user_id'],
            ['table' => 'employee_attendances', 'column' => 'registrado_por'],
            ['table' => 'rentals', 'column' => 'registrado_por'],
            ['table' => 'nutrition_goal_progress', 'column' => 'registrado_por'],
            ['table' => 'health_records', 'column' => 'actualizado_por'],
            ['table' => 'nutrition_goals', 'column' => 'trainer_user_id'],
            ['table' => 'coupon_usages', 'column' => 'usado_por'],
            ['table' => 'workout_sessions', 'column' => 'registrado_por'],
            ['table' => 'client_routines', 'column' => 'trainer_user_id'],
            ['table' => 'routine_templates', 'column' => 'created_by'],
            ['table' => 'crm_campaigns', 'column' => 'created_by'],
            ['table' => 'crm_campaign_targets', 'column' => 'assigned_to'],
            ['table' => 'crm_activities', 'column' => 'user_id'],
            ['table' => 'crm_tasks', 'column' => 'assigned_to'],
            ['table' => 'crm_tasks', 'column' => 'created_by'],
            ['table' => 'crm_deals', 'column' => 'assigned_to'],
            ['table' => 'crm_deals', 'column' => 'created_by'],
            ['table' => 'crm_leads', 'column' => 'assigned_to'],
            ['table' => 'crm_leads', 'column' => 'created_by'],
            ['table' => 'crm_leads', 'column' => 'converted_by'],
            ['table' => 'crm_mensajes', 'column' => 'created_by'],
            ['table' => 'evaluaciones_medidas_nutricion', 'column' => 'nutricionista_id'],
            ['table' => 'evaluaciones_medidas_nutricion', 'column' => 'evaluado_por'],
            ['table' => 'seguimientos_nutricion', 'column' => 'nutricionista_id'],
            ['table' => 'citas', 'column' => 'nutricionista_id'],
            ['table' => 'citas', 'column' => 'trainer_user_id'],
            ['table' => 'citas', 'column' => 'created_by'],
            ['table' => 'citas', 'column' => 'updated_by'],
            ['table' => 'clases', 'column' => 'instructor_id'],
            ['table' => 'audit_logs', 'column' => 'user_id'],
            ['table' => 'asistencias', 'column' => 'registrada_por'],
            ['table' => 'evaluacion_fisicas', 'column' => 'evaluado_por'],
            ['table' => 'sessions', 'column' => 'user_id'],
        ];
    }

    /**
     * @param  list<int>  $origenIds
     */
    public function unificar(User $destino, array $origenIds): int
    {
        $origenIds = collect($origenIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn (int $id) => $id === (int) $destino->id)
            ->values()
            ->all();

        if ($origenIds === []) {
            throw new \InvalidArgumentException('Selecciona al menos un usuario duplicado además del que se conserva.');
        }

        $this->assertMergeable($destino);

        $origenes = User::query()->whereIn('id', $origenIds)->get();
        if ($origenes->count() !== count($origenIds)) {
            throw new \InvalidArgumentException('Uno o más usuarios a unificar no existen.');
        }

        foreach ($origenes as $origen) {
            $this->assertMergeable($origen);

            if ((int) $origen->id === (int) Auth::id()) {
                throw new \InvalidArgumentException('No puedes unificar tu propio usuario como duplicado. Conserva tu cuenta como destino.');
            }
        }

        return DB::transaction(function () use ($destino, $origenes, $origenIds) {
            $this->mergeSucursales($destino, $origenIds);
            $this->reassignForeignKeys((int) $destino->id, $origenIds);
            $this->forgetSourceAuthRecords($origenIds);

            $eliminados = 0;
            foreach ($origenes as $origen) {
                $origen->syncRoles([]);
                $origen->syncPermissions([]);
                $origen->delete();
                $eliminados++;
            }

            return $eliminados;
        });
    }

    protected function assertMergeable(User $user): void
    {
        if ($user->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME)
            || $user->hasRole(PermissionCatalog::BRANCH_ADMIN_ROLE_NAME)) {
            throw new \InvalidArgumentException('Los usuarios administrativos especiales no se pueden unificar desde este módulo.');
        }
    }

    /**
     * @param  list<int>  $origenIds
     */
    protected function mergeSucursales(User $destino, array $origenIds): void
    {
        $sucursalIds = DB::table('sucursal_user')
            ->whereIn('user_id', $origenIds)
            ->pluck('sucursal_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($sucursalIds !== []) {
            $destino->sucursales()->syncWithoutDetaching($sucursalIds);
        }

        DB::table('sucursal_user')->whereIn('user_id', $origenIds)->delete();
    }

    /**
     * @param  list<int>  $origenIds
     */
    protected function reassignForeignKeys(int $destinoId, array $origenIds): void
    {
        foreach (self::userForeignKeys() as $fk) {
            if (! Schema::hasTable($fk['table']) || ! Schema::hasColumn($fk['table'], $fk['column'])) {
                continue;
            }

            DB::table($fk['table'])
                ->whereIn($fk['column'], $origenIds)
                ->update([$fk['column'] => $destinoId]);
        }

        if (Schema::hasTable('personal_access_tokens') && Schema::hasColumn('personal_access_tokens', 'tokenable_id')) {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $origenIds)
                ->delete();
        }
    }

    /**
     * @param  list<int>  $origenIds
     */
    protected function forgetSourceAuthRecords(array $origenIds): void
    {
        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            DB::table('sessions')->whereIn('user_id', $origenIds)->delete();
        }
    }
}
