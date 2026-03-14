<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_movimientos', function (Blueprint $table) {
            $table->string('categoria', 40)->nullable()->after('tipo');
            $table->string('origen_modulo', 40)->nullable()->after('categoria');

            $table->index('categoria');
            $table->index('origen_modulo');
        });

        DB::table('caja_movimientos')->orderBy('id')->chunkById(100, function ($movimientos): void {
            foreach ($movimientos as $movimiento) {
                [$categoria, $origen] = match ($movimiento->referencia_tipo) {
                    'App\\Models\\Core\\Venta' => ['pos', 'ventas'],
                    'App\\Models\\Core\\ClienteMembresia' => ['membresia', 'cliente_membresias'],
                    'App\\Models\\Core\\ClienteMatricula' => $this->resolverCategoriaMatricula((int) $movimiento->referencia_id),
                    'App\\Models\\Core\\EnrollmentInstallment' => ['cuota', 'enrollment_installments'],
                    'App\\Models\\Core\\RentalPayment', 'App\\Models\\Core\\Rental' => ['alquiler', 'rentals'],
                    default => [$movimiento->tipo === 'entrada' ? 'manual_ingreso' : 'manual_salida', 'manual'],
                };

                DB::table('caja_movimientos')
                    ->where('id', $movimiento->id)
                    ->update([
                        'categoria' => $categoria,
                        'origen_modulo' => $origen,
                    ]);
            }
        });

        DB::table('pagos')
            ->whereNotNull('caja_id')
            ->orderBy('id')
            ->chunkById(100, function ($pagos): void {
                foreach ($pagos as $pago) {
                    $referenciaTipo = null;
                    $referenciaId = null;
                    $categoria = 'ajuste';
                    $origen = 'manual';
                    $concepto = 'Pago';

                    if ($pago->cliente_matricula_id) {
                        $referenciaTipo = 'App\\Models\\Core\\ClienteMatricula';
                        $referenciaId = $pago->cliente_matricula_id;
                        [$categoria, $origen] = $this->resolverCategoriaMatricula((int) $pago->cliente_matricula_id);
                        $concepto = $categoria === 'clase' ? 'Cobro de clase' : 'Cobro de membresia';
                    } elseif ($pago->cliente_membresia_id) {
                        $referenciaTipo = 'App\\Models\\Core\\ClienteMembresia';
                        $referenciaId = $pago->cliente_membresia_id;
                        $categoria = 'membresia';
                        $origen = 'cliente_membresias';
                        $concepto = 'Cobro de membresia';
                    }

                    $existe = DB::table('caja_movimientos')
                        ->where('caja_id', $pago->caja_id)
                        ->where('tipo', 'entrada')
                        ->where('referencia_tipo', $referenciaTipo)
                        ->where('referencia_id', $referenciaId)
                        ->exists();

                    if ($existe) {
                        continue;
                    }

                    DB::table('caja_movimientos')->insert([
                        'caja_id' => $pago->caja_id,
                        'tipo' => 'entrada',
                        'categoria' => $categoria,
                        'origen_modulo' => $origen,
                        'monto' => $pago->monto,
                        'concepto' => $concepto,
                        'referencia_tipo' => $referenciaTipo,
                        'referencia_id' => $referenciaId,
                        'usuario_id' => $pago->registrado_por,
                        'observaciones' => 'Backfill desde pagos',
                        'fecha_movimiento' => $pago->fecha_pago,
                        'created_at' => $pago->created_at ?? now(),
                        'updated_at' => $pago->updated_at ?? now(),
                    ]);
                }
            });

        if (Schema::hasTable('rental_payments')) {
            DB::table('rental_payments')
                ->whereNotNull('caja_id')
                ->orderBy('id')
                ->chunkById(100, function ($payments): void {
                    foreach ($payments as $payment) {
                        $existe = DB::table('caja_movimientos')
                            ->where('caja_id', $payment->caja_id)
                            ->where('tipo', 'entrada')
                            ->where('referencia_tipo', 'App\\Models\\Core\\RentalPayment')
                            ->where('referencia_id', $payment->id)
                            ->exists();

                        if ($existe) {
                            continue;
                        }

                        $registradoPor = DB::table('rentals')->where('id', $payment->rental_id)->value('registrado_por')
                            ?? DB::table('cajas')->where('id', $payment->caja_id)->value('usuario_id');

                        DB::table('caja_movimientos')->insert([
                            'caja_id' => $payment->caja_id,
                            'tipo' => 'entrada',
                            'categoria' => 'alquiler',
                            'origen_modulo' => 'rentals',
                            'monto' => $payment->monto,
                            'concepto' => 'Pago de alquiler',
                            'referencia_tipo' => 'App\\Models\\Core\\RentalPayment',
                            'referencia_id' => $payment->id,
                            'usuario_id' => $registradoPor,
                            'observaciones' => 'Backfill desde rental_payments',
                            'fecha_movimiento' => $payment->fecha_pago,
                            'created_at' => $payment->created_at ?? now(),
                            'updated_at' => $payment->updated_at ?? now(),
                        ]);
                    }
                });
        }

        DB::table('caja_movimientos')
            ->whereNull('categoria')
            ->update([
                'categoria' => DB::raw("CASE WHEN tipo = 'entrada' THEN 'manual_ingreso' ELSE 'manual_salida' END"),
                'origen_modulo' => 'manual',
            ]);

        Schema::table('caja_movimientos', function (Blueprint $table) {
            $table->string('categoria', 40)->nullable(false)->change();
            $table->string('origen_modulo', 40)->nullable(false)->change();
        });
    }

    private function resolverCategoriaMatricula(int $matriculaId): array
    {
        $tipo = DB::table('cliente_matriculas')->where('id', $matriculaId)->value('tipo');

        return $tipo === 'clase'
            ? ['clase', 'cliente_matriculas']
            : ['membresia', 'cliente_matriculas'];
    }

    public function down(): void
    {
        Schema::table('caja_movimientos', function (Blueprint $table) {
            $table->dropIndex(['categoria']);
            $table->dropIndex(['origen_modulo']);
            $table->dropColumn(['categoria', 'origen_modulo']);
        });
    }
};
