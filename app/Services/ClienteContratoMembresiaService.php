<?php

namespace App\Services;

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\Pago;
use App\Support\BrandingResolver;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ClienteContratoMembresiaService
{
    /** @var list<string> */
    private const SEDES_CONTRATO = ['Ayacucho', 'Cajamarca', 'Chilca'];

    public function __construct(
        protected BrandingResolver $brandingResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function datosContrato(Cliente $cliente): array
    {
        $cliente->loadMissing(['sucursal', 'registroPor']);

        $matricula = $this->matriculaReferencia($cliente);
        if ($matricula) {
            $matricula->loadMissing(['membresia', 'clase', 'asesor', 'sucursal', 'pagos', 'enrollmentInstallments']);
        }

        $branding = $this->brandingResolver->resolve();
        $tipoMembresia = $this->resolverTipoMembresia($matricula);
        $sedeNombre = $this->resolverSede($matricula, $cliente);
        $montoPagado = $this->resolverMontoPagado($matricula);
        $formaPago = $this->resolverFormaPago($matricula);
        $fechasFraccionado = $this->resolverFechasFraccionado($matricula);

        $fechaNacimiento = $cliente->fecha_nacimiento instanceof Carbon
            ? $cliente->fecha_nacimiento
            : ($cliente->fecha_nacimiento ? Carbon::parse($cliente->fecha_nacimiento) : null);

        $fechaInicio = $matricula?->fecha_inicio;
        $fechaFin = $matricula?->fecha_fin;

        $asesorNombre = $matricula?->asesor?->name
            ?? $cliente->registroPor?->name
            ?? '';

        $ciudadFirma = $this->resolverCiudadFirma($matricula, $cliente);

        return [
            'gimnasio_nombre' => Str::upper($branding['name'] ?: 'FITNESS CENTER'),
            'gimnasio_nombre_titulo' => 'GIMNASIO '.Str::upper($branding['name'] ?: 'FITNESS CENTER'),
            'afiliado_nombre' => trim($cliente->nombres.' '.$cliente->apellidos),
            'afiliado_dni' => $cliente->numero_documento ?? '',
            'afiliado_celular' => $cliente->telefono ?? '',
            'afiliado_fecha_nacimiento' => $fechaNacimiento?->format('d / m / Y') ?? '___ / ___ / ___',
            'afiliado_direccion' => $cliente->direccion ?? '',
            'afiliado_codigo' => $cliente->codigo ?? '',
            'asesor_nombre' => $asesorNombre,
            'tipo_membresia' => $tipoMembresia,
            'tipo_membresia_otro' => $tipoMembresia['otro'] ?? ($matricula?->membresia?->nombre ?? ''),
            'sedes' => collect(self::SEDES_CONTRATO)->mapWithKeys(fn (string $sede) => [
                $sede => Str::contains(Str::lower($sedeNombre), Str::lower($sede)),
            ])->all(),
            'fecha_inicio' => $fechaInicio?->format('d / m / Y') ?? '____ / ____ / ____',
            'fecha_termino' => $fechaFin?->format('d / m / Y') ?? '____ / ____ / ____',
            'monto_pagado' => $montoPagado > 0 ? number_format($montoPagado, 2) : '___________',
            'forma_pago' => $formaPago,
            'fechas_pago_fraccionado' => $fechasFraccionado,
            'ciudad_firma' => $ciudadFirma,
            'fecha_firma_dia' => now()->format('d'),
            'fecha_firma_mes' => now()->translatedFormat('F'),
            'fecha_firma_anio' => now()->format('y'),
            'matricula_nombre' => $matricula?->membresia?->nombre ?? $matricula?->clase?->nombre ?? '',
        ];
    }

    protected function matriculaReferencia(Cliente $cliente): ?ClienteMatricula
    {
        $membresia = $cliente->clienteMatriculas()
            ->where('tipo', 'membresia')
            ->orderByRaw("CASE WHEN estado = 'activa' THEN 0 ELSE 1 END")
            ->orderByDesc('fecha_matricula')
            ->first();

        if ($membresia) {
            return $membresia;
        }

        return $cliente->clienteMatriculas()
            ->orderByDesc('fecha_matricula')
            ->first();
    }

    /**
     * @return array{mensual: bool, trimestral: bool, semestral: bool, anual: bool, otro: bool, otro_texto: string}
     */
    protected function resolverTipoMembresia(?ClienteMatricula $matricula): array
    {
        $base = [
            'mensual' => false,
            'trimestral' => false,
            'semestral' => false,
            'anual' => false,
            'otro' => false,
            'otro_texto' => '',
        ];

        if (! $matricula) {
            return $base;
        }

        $nombre = Str::lower((string) ($matricula->membresia?->nombre ?? ''));
        $dias = (int) ($matricula->membresia?->duracion_dias ?? 0);

        if (Str::contains($nombre, 'mensual') || ($dias > 0 && $dias <= 35)) {
            $base['mensual'] = true;

            return $base;
        }

        if (Str::contains($nombre, 'trimestral') || ($dias > 35 && $dias <= 100)) {
            $base['trimestral'] = true;

            return $base;
        }

        if (Str::contains($nombre, 'semestral') || ($dias > 100 && $dias <= 200)) {
            $base['semestral'] = true;

            return $base;
        }

        if (Str::contains($nombre, 'anual') || $dias > 200) {
            $base['anual'] = true;

            return $base;
        }

        $base['otro'] = true;
        $base['otro_texto'] = $matricula->membresia?->nombre ?? 'Plan contratado';

        return $base;
    }

    protected function resolverSede(?ClienteMatricula $matricula, Cliente $cliente): string
    {
        return trim((string) (
            $matricula?->sucursal?->nombre
            ?? $cliente->sucursal?->nombre
            ?? ''
        ));
    }

    protected function resolverMontoPagado(?ClienteMatricula $matricula): float
    {
        if (! $matricula) {
            return 0.0;
        }

        $pagado = (float) $matricula->pagos->sum(fn (Pago $pago) => (float) $pago->monto);

        if ($pagado > 0) {
            return round($pagado, 2);
        }

        return round((float) ($matricula->precio_final ?? 0), 2);
    }

    protected function resolverFormaPago(?ClienteMatricula $matricula): string
    {
        if (! $matricula) {
            return '';
        }

        return match ($matricula->modalidad_pago) {
            'cuotas' => 'Pago fraccionado / cuotas',
            'contado' => 'Contado',
            default => ucfirst((string) $matricula->modalidad_pago),
        };
    }

    protected function resolverFechasFraccionado(?ClienteMatricula $matricula): string
    {
        if (! $matricula || $matricula->modalidad_pago !== 'cuotas') {
            return '';
        }

        $fechas = $matricula->enrollmentInstallments
            ->map(function (EnrollmentInstallment $cuota) {
                $fecha = $cuota->fecha_vencimiento ?? $cuota->fecha_pago;

                return $fecha ? Carbon::parse($fecha)->format('d/m/Y') : null;
            })
            ->filter()
            ->values();

        if ($fechas->isEmpty()) {
            return 'Según cronograma acordado en recepción';
        }

        return $fechas->implode(' · ');
    }

    protected function resolverCiudadFirma(?ClienteMatricula $matricula, Cliente $cliente): string
    {
        $sede = $this->resolverSede($matricula, $cliente);

        foreach (self::SEDES_CONTRATO as $nombreSede) {
            if (Str::contains(Str::lower($sede), Str::lower($nombreSede))) {
                return $nombreSede === 'Chilca' ? 'Chilca' : 'Huancayo';
            }
        }

        return 'Huancayo';
    }
}
