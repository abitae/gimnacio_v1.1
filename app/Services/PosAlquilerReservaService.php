<?php

namespace App\Services;

use App\Models\Core\RentalPayment;
use App\Models\Core\Venta;

class PosAlquilerReservaService
{
    public function __construct(
        protected RentalService $rentalService,
        protected CajaService $cajaService,
    ) {}

    /**
     * Crea reservas pagadas en calendario por cada línea de alquiler del carrito POS.
     *
     * @param  array<string, mixed>  $carrito
     * @param  array{fecha: string, hora_inicio: string, hora_fin: string}  $slot
     */
    public function crearDesdeVenta(Venta $venta, array $carrito, array $slot): void
    {
        $alquilerItems = collect($carrito)->filter(
            fn (array $item) => ($item['tipo'] ?? '') === 'alquiler'
        );

        if ($alquilerItems->isEmpty()) {
            return;
        }

        $venta->loadMissing('employee');

        foreach ($alquilerItems as $item) {
            $cantidad = max(1, (int) ($item['cantidad'] ?? 1));
            $precioUnitario = (float) ($item['precio'] ?? 0);

            for ($i = 0; $i < $cantidad; $i++) {
                $rental = $this->rentalService->create(array_merge(
                    $this->datosCompradorReserva($venta),
                    [
                        'rentable_space_id' => (int) $item['id'],
                        'fecha' => $slot['fecha'],
                        'hora_inicio' => $slot['hora_inicio'],
                        'hora_fin' => $slot['hora_fin'],
                        'precio' => $precioUnitario,
                        'estado' => 'pagado',
                        'observaciones' => 'Venta POS '.$venta->numero_venta.' #'.$venta->id,
                    ]
                ));

                if ($venta->caja_id && $precioUnitario > 0) {
                    $payment = RentalPayment::create([
                        'rental_id' => $rental->id,
                        'monto' => $precioUnitario,
                        'payment_method_id' => $venta->payment_method_id,
                        'numero_operacion' => $venta->numero_operacion,
                        'entidad_financiera' => $venta->entidad_financiera,
                        'fecha_pago' => now()->toDateString(),
                        'caja_id' => $venta->caja_id,
                        'sucursal_id' => $venta->sucursal_id,
                    ]);

                    $rental->load('rentableSpace');
                    $nombreEspacio = $rental->rentableSpace?->nombre ?? 'Espacio';

                    $this->cajaService->registrarIngresoAlquiler(
                        $payment,
                        "Alquiler POS - {$nombreEspacio}",
                        "Venta POS {$venta->numero_venta} #{$venta->id}"
                    );
                }
            }
        }
    }

    /**
     * @return array{cliente_id?: int, nombre_externo?: string, documento_externo?: string}
     */
    protected function datosCompradorReserva(Venta $venta): array
    {
        if ($venta->cliente_id) {
            return ['cliente_id' => (int) $venta->cliente_id];
        }

        if ($venta->employee_id && $venta->employee) {
            return [
                'nombre_externo' => trim($venta->employee->nombre_completo).' (Empleado)',
                'documento_externo' => $venta->employee->documento ?: null,
            ];
        }

        if (filled($venta->cliente_venta_nombre)) {
            return [
                'nombre_externo' => $venta->cliente_venta_nombre,
                'documento_externo' => $venta->cliente_venta_documento ?: null,
            ];
        }

        throw new \InvalidArgumentException('Se requiere un comprador para registrar la reserva de alquiler.');
    }
}
