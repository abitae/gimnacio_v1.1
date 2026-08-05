<?php

namespace App\Services\Cliente;

use App\Data\Cliente\ClienteOperationsSummary;
use App\Models\Core\Pago;
use App\Services\AsistenciaService;
use App\Services\ClientEnrollmentService;
use App\Services\DailyOperationsDebtService;

class ClienteOperationsProfileService
{
    public function __construct(
        protected AsistenciaService $asistenciaService,
        protected ClientEnrollmentService $clientEnrollmentService,
        protected DailyOperationsDebtService $dailyOperationsDebtService,
    ) {}

    public function getSummary(int $clienteId): ClienteOperationsSummary
    {
        $activeEnrollment = $this->clientEnrollmentService->resolveActiveEnrollment($clienteId);
        $membresiaActiva = $activeEnrollment['source_model'] ?? null;

        $operacionDiariaDebtSummary = $this->dailyOperationsDebtService->summarizeCliente($clienteId);
        $saldoPendiente = (float) ($operacionDiariaDebtSummary['total_pendiente'] ?? 0);
        $deudaProductoPendiente = (float) collect($operacionDiariaDebtSummary['items'] ?? [])
            ->where('tipo', 'client_debt')
            ->sum('saldo_pendiente');
        $deudaMembresiaPendiente = (float) collect($operacionDiariaDebtSummary['items'] ?? [])
            ->where('tipo', 'client_debt_membership')
            ->sum('saldo_pendiente');

        $validacionAcceso = $membresiaActiva
            ? $this->asistenciaService->validarAccesoPorHorario($membresiaActiva)
            : [];

        $estadisticasAsistencia = $membresiaActiva
            ? $this->asistenciaService->obtenerEstadisticasAsistencia($clienteId, $membresiaActiva->id)
            : [
                'total_asistencias' => 0,
                'asistencias_completas' => 0,
                'asistencias_pendientes' => 0,
                'total_sesiones' => 0,
                'porcentaje_efectividad' => 0,
            ];

        $pagosRecientes = Pago::query()
            ->where('cliente_id', $clienteId)
            ->with(['registradoPor', 'clienteMembresia.membresia', 'clienteMatricula.clase', 'clienteMatricula.membresia', 'detalles.paymentMethod'])
            ->orderByDesc('fecha_pago')
            ->limit(5)
            ->get()
            ->all();

        return new ClienteOperationsSummary(
            membresiaActiva: $membresiaActiva,
            operacionDiariaDebtSummary: $operacionDiariaDebtSummary,
            saldoPendiente: $saldoPendiente,
            deudaProductoPendiente: $deudaProductoPendiente,
            deudaMembresiaPendiente: $deudaMembresiaPendiente,
            asistenciasRecientes: $this->asistenciaService->obtenerAsistenciasRecientes($clienteId, 5)->all(),
            estadisticasAsistencia: $estadisticasAsistencia,
            validacionAcceso: $validacionAcceso,
            ingresoEnCurso: $this->asistenciaService->obtenerIngresoEnCurso($clienteId),
            pagosRecientes: $pagosRecientes,
        );
    }
}
