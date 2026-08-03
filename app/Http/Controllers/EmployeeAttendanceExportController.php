<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeAttendanceReportExport;
use App\Services\EmployeeAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeAttendanceExportController extends Controller
{
    public function __construct(
        protected EmployeeAttendanceService $attendanceService,
    ) {}

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('asistencia_empleado.ver');

        $mes = (int) $request->query('mes', now()->month);
        $anio = (int) $request->query('anio', now()->year);
        $employeeId = $request->query('employee_id') ? (int) $request->query('employee_id') : null;

        $start = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $reporte = $this->attendanceService->reporteResumen($start, $end, $employeeId);
        $detalle = $this->attendanceService->filasDetallePeriodo($start, $end, $employeeId);

        $filasResumen = collect($reporte['filas'])->map(fn (array $fila) => [
            'empleado' => $fila['employee']->nombre_completo,
            'documento' => $fila['employee']->documento ?? '',
            'cargo' => $fila['employee']->cargo ?? '',
            'dias' => $fila['dias'],
            'tardanza_minutos' => $fila['tardanza_minutos'],
        ])->all();

        $periodo = $start->translatedFormat('F Y');
        $filename = 'asistencia-empleados-'.$start->format('Y-m').'.xlsx';

        return Excel::download(
            new EmployeeAttendanceReportExport(
                $filasResumen,
                $detalle,
                $reporte['totales'],
                $periodo,
            ),
            $filename,
        );
    }
}
