<?php

namespace App\DataTransferObjects\Imports;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class SocioActivoRowData
{
    public function __construct(
        public readonly int $rowNumber,
        public readonly ?string $codigo,
        public readonly ?string $nombres,
        public readonly ?string $apellidos,
        public readonly ?string $correo,
        public readonly ?string $dni,
        public readonly ?string $edad,
        public readonly ?string $celular,
        public readonly ?CarbonImmutable $fechaNacimiento,
        public readonly ?string $direccion,
        public readonly ?string $tipoVenta,
        public readonly ?string $origen,
        public readonly ?string $paquete,
        public readonly ?CarbonImmutable $fechaInscripcion,
        public readonly ?float $costo,
        public readonly ?CarbonImmutable $fechaInicio,
        public readonly ?CarbonImmutable $fechaFin,
        public readonly ?string $vendedor,
        public readonly ?string $repartido,
        public readonly ?string $sesiones,
        public readonly ?string $asistencias,
        public readonly ?string $reservas,
    ) {}

    public function tipoVentaNormalizado(): string
    {
        return self::normalizeComparable($this->tipoVenta);
    }

    public function soportaImportacionMembresia(): bool
    {
        return $this->tipoVentaNormalizado() === 'membresia';
    }

    public function nombreCompleto(): string
    {
        return trim(implode(' ', array_filter([$this->nombres, $this->apellidos])));
    }

    public function duracionDias(): ?int
    {
        if (! $this->fechaInicio || ! $this->fechaFin) {
            return null;
        }

        return max(1, $this->fechaInicio->startOfDay()->diffInDays($this->fechaFin->startOfDay()));
    }

    public function vendedorNormalizado(): string
    {
        return self::normalizeComparable($this->vendedor);
    }

    public function repartidoNormalizado(): string
    {
        return self::normalizeComparable($this->repartido);
    }

    public static function normalizeComparable(?string $value): string
    {
        $normalized = trim((string) $value);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? '';
        $normalized = Str::ascii($normalized);

        return Str::lower($normalized);
    }
}
