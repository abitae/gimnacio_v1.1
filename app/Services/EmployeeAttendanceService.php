<?php

namespace App\Services;

use App\Models\Core\Employee;
use App\Models\Core\EmployeeAttendance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class EmployeeAttendanceService
{
    public function searchEmployees(string $term, int $limit = 15): Collection
    {
        $term = trim($term);

        if (strlen($term) < 2) {
            return new Collection;
        }

        return Employee::query()
            ->activos()
            ->where(function ($query) use ($term) {
                $query->where('nombres', 'like', "%{$term}%")
                    ->orWhere('apellidos', 'like', "%{$term}%")
                    ->orWhere('documento', 'like', "%{$term}%")
                    ->orWhereRaw("CONCAT(nombres, ' ', apellidos) LIKE ?", ["%{$term}%"]);
            })
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->limit($limit)
            ->get(['id', 'nombres', 'apellidos', 'documento', 'cargo']);
    }

    public function attendanceForEmployeeOnDate(int $employeeId, string $fecha): ?EmployeeAttendance
    {
        return EmployeeAttendance::query()
            ->where('employee_id', $employeeId)
            ->whereDate('fecha', $fecha)
            ->first();
    }

    /**
     * @return SupportCollection<int, EmployeeAttendance|null>
     */
    public function attendancesForEmployeesOnDate(SupportCollection $employeeIds, string $fecha): SupportCollection
    {
        if ($employeeIds->isEmpty()) {
            return collect();
        }

        return EmployeeAttendance::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('fecha', $fecha)
            ->get()
            ->keyBy('employee_id');
    }

    public function registrarIngreso(int $employeeId, string $fecha, ?string $hora = null): EmployeeAttendance
    {
        $existing = $this->attendanceForEmployeeOnDate($employeeId, $fecha);

        if ($existing !== null) {
            throw new \InvalidArgumentException('Ya existe un registro de asistencia para este empleado en esta fecha.');
        }

        return EmployeeAttendance::create([
            'employee_id' => $employeeId,
            'fecha' => $fecha,
            'hora_ingreso' => $hora ?? now()->format('H:i'),
            'hora_salida' => null,
            'observaciones' => null,
            'registrado_por' => auth()->id(),
        ]);
    }

    public function registrarSalida(int $employeeId, string $fecha, ?string $hora = null): EmployeeAttendance
    {
        $existing = $this->attendanceForEmployeeOnDate($employeeId, $fecha);

        if ($existing === null) {
            throw new \InvalidArgumentException('No hay ingreso registrado para este empleado en la fecha seleccionada.');
        }

        if ($existing->hora_salida) {
            throw new \InvalidArgumentException('La salida ya fue registrada para este empleado en la fecha seleccionada.');
        }

        $existing->update([
            'hora_salida' => $hora ?? now()->format('H:i'),
        ]);

        return $existing->fresh();
    }

    /**
     * @param  array{employee_id:int,fecha:string,hora_ingreso?:string|null,hora_salida?:string|null,observaciones?:string|null}  $data
     */
    public function registrarManual(array $data): EmployeeAttendance
    {
        $employeeId = (int) $data['employee_id'];
        $fecha = $data['fecha'];

        if ($this->attendanceForEmployeeOnDate($employeeId, $fecha) !== null) {
            throw new \InvalidArgumentException('Ya existe un registro de asistencia para este empleado en esta fecha.');
        }

        return EmployeeAttendance::create([
            'employee_id' => $employeeId,
            'fecha' => $fecha,
            'hora_ingreso' => ! empty($data['hora_ingreso']) ? $data['hora_ingreso'] : null,
            'hora_salida' => ! empty($data['hora_salida']) ? $data['hora_salida'] : null,
            'observaciones' => ! empty($data['observaciones']) ? $data['observaciones'] : null,
            'registrado_por' => auth()->id(),
        ]);
    }

    /**
     * @return array{
     *     filas: list<array{employee: Employee, dias: int, tardanza_minutos: int, horas_trabajadas: float}>,
     *     totales: array{empleados_con_registro: int, total_dias: int, total_tardanza_minutos: int}
     * }
     */
    public function reporteResumen(Carbon $start, Carbon $end, ?int $employeeId = null): array
    {
        $aggRows = EmployeeAttendance::query()
            ->whereBetween('fecha', [$start->toDateString(), $end->toDateString()])
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->selectRaw('employee_id, COUNT(*) as dias, COALESCE(SUM(tardanza_minutos), 0) as tardanza_minutos')
            ->groupBy('employee_id')
            ->get()
            ->keyBy(fn ($row) => (int) $row->employee_id);

        $employeesQuery = Employee::query()->activos()->orderBy('apellidos')->orderBy('nombres');

        if ($employeeId) {
            $employeesQuery->whereKey($employeeId);
        }

        $employees = $employeesQuery->get(['id', 'nombres', 'apellidos', 'documento', 'cargo']);

        $filas = [];
        $totalDias = 0;
        $totalTardanza = 0;
        $empleadosConRegistro = 0;

        foreach ($employees as $employee) {
            $row = $aggRows->get($employee->id);
            $dias = (int) ($row->dias ?? 0);
            $tardanza = (int) ($row->tardanza_minutos ?? 0);

            if ($dias === 0 && ! $employeeId) {
                continue;
            }

            if ($dias > 0) {
                $empleadosConRegistro++;
            }

            $totalDias += $dias;
            $totalTardanza += $tardanza;

            $filas[] = [
                'employee' => $employee,
                'dias' => $dias,
                'tardanza_minutos' => $tardanza,
            ];
        }

        return [
            'filas' => $filas,
            'totales' => [
                'empleados_con_registro' => $empleadosConRegistro,
                'total_dias' => $totalDias,
                'total_tardanza_minutos' => $totalTardanza,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function filasDetallePeriodo(Carbon $start, Carbon $end, ?int $employeeId = null): array
    {
        return EmployeeAttendance::query()
            ->with(['employee', 'registradoPor'])
            ->whereBetween('fecha', [$start->toDateString(), $end->toDateString()])
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->orderByDesc('fecha')
            ->orderByDesc('hora_ingreso')
            ->get()
            ->map(fn (EmployeeAttendance $attendance) => [
                'empleado' => $attendance->employee?->nombre_completo ?? '—',
                'documento' => $attendance->employee?->documento ?? '',
                'cargo' => $attendance->employee?->cargo ?? '',
                'fecha' => $attendance->fecha?->format('d/m/Y') ?? '',
                'hora_ingreso' => $attendance->hora_ingreso ? Carbon::parse($attendance->hora_ingreso)->format('H:i') : '',
                'hora_salida' => $attendance->hora_salida ? Carbon::parse($attendance->hora_salida)->format('H:i') : '',
                'tardanza_minutos' => (int) ($attendance->tardanza_minutos ?? 0),
                'observaciones' => $attendance->observaciones ?? '',
                'registrado_por' => $attendance->registradoPor?->name ?? '',
            ])
            ->all();
    }
}
