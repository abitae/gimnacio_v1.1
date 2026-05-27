<?php

namespace App\Console\Commands;

use App\Services\AsistenciaService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckingAutoCheckoutCommand extends Command
{
    protected $signature = 'checking:auto-checkout {--date= : Fecha a procesar en formato YYYY-MM-DD}';

    protected $description = 'Cierra automaticamente asistencias de clientes sin checkout hasta el fin del dia';

    public function handle(AsistenciaService $service): int
    {
        $dateOption = $this->option('date');

        $fecha = $this->resolveDateOption($dateOption);

        if (! $fecha) {
            $this->error('La opcion --date debe tener formato YYYY-MM-DD.');

            return self::FAILURE;
        }

        $result = $service->cerrarIngresosAbiertosHastaFinDelDia($fecha);
        $checkoutAt = $result['fecha_hora_salida']->format('Y-m-d H:i:s');

        Log::info('Checkout automatico diario ejecutado.', [
            'fecha_procesada' => $fecha->toDateString(),
            'fecha_hora_salida' => $checkoutAt,
            'total' => $result['total'],
            'asistencia_ids' => $result['ids'],
        ]);

        $this->info("Asistencias cerradas automaticamente: {$result['total']}.");
        $this->line("Fecha/hora de salida aplicada: {$checkoutAt}.");

        return self::SUCCESS;
    }

    protected function resolveDateOption(mixed $dateOption): ?Carbon
    {
        if ($dateOption === null) {
            return now();
        }

        $dateOption = (string) $dateOption;

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOption)) {
            return null;
        }

        try {
            $fecha = Carbon::createFromFormat('!Y-m-d', $dateOption);
        } catch (Throwable) {
            return null;
        }

        if ($fecha === false || $fecha->format('Y-m-d') !== $dateOption) {
            return null;
        }

        return $fecha->startOfDay();
    }
}
