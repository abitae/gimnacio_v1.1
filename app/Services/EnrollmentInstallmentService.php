<?php

namespace App\Services;

use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\EnrollmentInstallmentPlan;
use App\Models\Core\Pago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EnrollmentInstallmentService
{
    /**
     * Crear plan de cuotas y generar las fechas de vencimiento según frecuencia.
     */
    public function createPlan(ClienteMatricula $clienteMatricula, array $data): EnrollmentInstallmentPlan
    {
        $validated = [
            'cliente_matricula_id' => $clienteMatricula->id,
            'monto_total' => (float) ($data['monto_total'] ?? $clienteMatricula->precio_final),
            'numero_cuotas' => (int) $data['numero_cuotas'],
            'frecuencia' => $data['frecuencia'] ?? 'mensual',
            'fecha_inicio' => isset($data['fecha_inicio']) ? Carbon::parse($data['fecha_inicio']) : $clienteMatricula->fecha_matricula ?? Carbon::today(),
            'observaciones' => $data['observaciones'] ?? null,
        ];

        $validated['monto_cuota'] = round($validated['monto_total'] / $validated['numero_cuotas'], 2);

        return DB::transaction(function () use ($validated) {
            $plan = EnrollmentInstallmentPlan::create($validated);
            $fechas = $this->generarFechasVencimiento(
                $plan->fecha_inicio,
                $plan->numero_cuotas,
                $plan->frecuencia
            );
            foreach ($fechas as $i => $fecha) {
                EnrollmentInstallment::create([
                    'enrollment_installment_plan_id' => $plan->id,
                    'numero_cuota' => $i + 1,
                    'monto' => $plan->monto_cuota,
                    'fecha_vencimiento' => $fecha,
                    'estado' => 'pendiente',
                ]);
            }
            return $plan->fresh('installments');
        });
    }

    /**
     * Generar fechas de vencimiento según frecuencia.
     *
     * @return array<int, Carbon>
     */
    private function generarFechasVencimiento(Carbon $fechaInicio, int $numeroCuotas, string $frecuencia): array
    {
        $fechas = [];
        $current = $fechaInicio->copy();
        for ($i = 0; $i < $numeroCuotas; $i++) {
            $fechas[] = $current->copy();
            match ($frecuencia) {
                'semanal' => $current->addWeek(),
                'quincenal' => $current->addWeeks(2),
                'mensual' => $current->addMonth(),
                default => $current->addMonth(),
            };
        }
        return $fechas;
    }

    /**
     * Registrar pago de una cuota (crear Pago y actualizar installment).
     */
    public function pagarCuota(EnrollmentInstallment $installment, array $data): Pago
    {
        if (! in_array($installment->estado, ['pendiente', 'vencida', 'parcial'], true)) {
            throw new \InvalidArgumentException('Esta cuota ya está pagada.');
        }

        return DB::transaction(function () use ($installment, $data) {
            $plan = $installment->plan;
            $matricula = $plan->clienteMatricula;
            $monto = (float) ($data['monto'] ?? $installment->monto);
            $cajaId = $data['caja_id'] ?? null;

            $pago = Pago::create([
                'cliente_id' => $matricula->cliente_id,
                'cliente_matricula_id' => $matricula->id,
                'monto' => $monto,
                'metodo_pago' => 'Cuota ' . $installment->numero_cuota,
                'fecha_pago' => $data['fecha_pago'] ?? now(),
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'numero_operacion' => $data['numero_operacion'] ?? null,
                'entidad_financiera' => $data['entidad_financiera'] ?? null,
                'caja_id' => $cajaId,
                'registrado_por' => auth()->id(),
            ]);

            $installment->update([
                'estado' => $monto >= (float) $installment->monto ? 'pagada' : 'parcial',
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'numero_operacion' => $data['numero_operacion'] ?? null,
                'pago_id' => $pago->id,
                'fecha_pago' => $pago->fecha_pago ? \Carbon\Carbon::parse($pago->fecha_pago)->toDateString() : null,
            ]);

            // Marcar cuotas vencidas (pendientes con fecha pasada)
            $plan->installments()
                ->where('estado', 'pendiente')
                ->where('fecha_vencimiento', '<', now()->toDateString())
                ->update(['estado' => 'vencida']);

            return $pago;
        });
    }
}
