<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\SocioActivoRowData;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use App\Models\Core\PaymentMethod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClienteImportProcessor
{
    public function __construct(
        private readonly SellerUserResolver $sellerUserResolver,
    ) {}

    /**
     * @param  list<SocioActivoRowData>  $rows
     * @return array<string, mixed>
     */
    public function import(array $rows, bool $execute = false): array
    {
        $report = [
            'phase' => 'clients',
            'dry_run' => ! $execute,
            'processed_rows' => count($rows),
            'created_clients' => 0,
            'updated_clients' => 0,
            'created_matriculas' => 0,
            'created_pagos' => 0,
            'omitted_duplicate_matriculas' => 0,
            'skipped_non_membership' => 0,
            'rejected' => [],
            'errors' => [],
        ];

        $fallbackUser = $execute ? $this->sellerUserResolver->fallbackUser() : null;
        $cashMethod = $execute ? $this->resolveCashMethod() : null;

        foreach ($rows as $row) {
            if (! $row->soportaImportacionMembresia()) {
                $report['skipped_non_membership']++;
                $report['rejected'][] = [
                    'row' => $row->rowNumber,
                    'reason' => 'TIPO DE VENTA no soportado en v1.',
                    'tipo_venta' => $row->tipoVenta,
                ];
                continue;
            }

            $validationError = $this->validateRow($row);
            if ($validationError !== null) {
                $report['rejected'][] = [
                    'row' => $row->rowNumber,
                    'reason' => $validationError,
                ];
                continue;
            }

            $membership = Membresia::query()->where('nombre', $row->paquete)->first();
            if (! $membership) {
                $report['rejected'][] = [
                    'row' => $row->rowNumber,
                    'reason' => 'No existe la membresía del paquete en el sistema.',
                    'paquete' => $row->paquete,
                ];
                continue;
            }

            if (! $execute) {
                $previewCliente = Cliente::query()
                    ->where('tipo_documento', 'DNI')
                    ->where('numero_documento', $row->dni)
                    ->first();
                if ($previewCliente) {
                    $report['updated_clients']++;
                } else {
                    $report['created_clients']++;
                }

                $duplicateMatricula = $previewCliente
                    ? $this->findDuplicateMatricula($previewCliente, $membership->id, $row->fechaInicio, $row->fechaFin) !== null
                    : false;

                if ($duplicateMatricula) {
                    $report['omitted_duplicate_matriculas']++;
                } else {
                    $report['created_matriculas']++;
                    $report['created_pagos']++;
                }

                continue;
            }

            try {
                DB::transaction(function () use ($row, $membership, $fallbackUser, $cashMethod, &$report): void {
                    $cliente = Cliente::query()
                        ->where('tipo_documento', 'DNI')
                        ->where('numero_documento', $row->dni)
                        ->first();

                    if ($cliente) {
                        $this->updateCliente($cliente, $row, $fallbackUser?->id);
                        $report['updated_clients']++;
                    } else {
                        $cliente = $this->createCliente($row, $fallbackUser?->id);
                        $report['created_clients']++;
                    }

                    if ($this->findDuplicateMatricula($cliente, $membership->id, $row->fechaInicio, $row->fechaFin)) {
                        $report['omitted_duplicate_matriculas']++;

                        return;
                    }

                    $asesor = $this->sellerUserResolver->resolveOrFallback($row->vendedor);
                    $matricula = ClienteMatricula::create([
                        'cliente_id' => $cliente->id,
                        'tipo' => 'membresia',
                        'membresia_id' => $membership->id,
                        'fecha_matricula' => ($row->fechaInscripcion ?? $row->fechaInicio)?->toDateString(),
                        'fecha_inicio' => $row->fechaInicio?->toDateString(),
                        'fecha_fin' => $row->fechaFin?->toDateString(),
                        'estado' => $this->resolveMatriculaEstado($row->fechaFin),
                        'precio_lista' => $row->costo,
                        'descuento_monto' => 0,
                        'precio_final' => $row->costo,
                        'modalidad_pago' => 'contado',
                        'requiere_plan_cuotas' => false,
                        'cuota_inicial_monto' => 0,
                        'asesor_id' => $asesor->id,
                        'canal_venta' => $this->normalizeChannel($row->origen),
                    ]);

                    $report['created_matriculas']++;

                    Pago::create([
                        'cliente_id' => $cliente->id,
                        'cliente_matricula_id' => $matricula->id,
                        'monto' => $row->costo,
                        'moneda' => 'PEN',
                        'metodo_pago' => 'efectivo',
                        'payment_method_id' => $cashMethod?->id,
                        'fecha_pago' => ($row->fechaInscripcion ?? $row->fechaInicio)?->toDateTimeString(),
                        'es_pago_parcial' => false,
                        'saldo_pendiente' => 0,
                        'registrado_por' => $asesor->id,
                    ]);

                    $report['created_pagos']++;
                });
            } catch (\Throwable $e) {
                $report['errors'][] = [
                    'row' => $row->rowNumber,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return $report;
    }

    private function validateRow(SocioActivoRowData $row): ?string
    {
        if (! $row->dni) {
            return 'La fila no tiene DNI válido.';
        }

        if (! $row->paquete) {
            return 'La fila no tiene PAQUETE.';
        }

        if ($row->costo === null || $row->costo < 0) {
            return 'La fila no tiene COSTO válido.';
        }

        if (! $row->fechaInicio || ! $row->fechaFin) {
            return 'La fila no tiene FECHA INICIO/FIN válida.';
        }

        if (! $row->nombres || ! $row->apellidos) {
            return 'La fila no tiene nombre completo válido.';
        }

        return null;
    }

    private function createCliente(SocioActivoRowData $row, ?int $fallbackUserId): Cliente
    {
        $email = $this->resolveClienteEmail($row->correo);

        return Cliente::create([
            'codigo' => filled($row->codigo) ? trim((string) $row->codigo) : null,
            'tipo_documento' => 'DNI',
            'numero_documento' => $row->dni,
            'nombres' => $row->nombres,
            'apellidos' => $row->apellidos,
            'telefono' => $row->celular,
            'email' => $email,
            'direccion' => $row->direccion,
            'fecha_nacimiento' => $row->fechaNacimiento?->toDateString(),
            'estado_cliente' => 'activo',
            'created_by' => $fallbackUserId,
            'updated_by' => $fallbackUserId,
        ]);
    }

    private function updateCliente(Cliente $cliente, SocioActivoRowData $row, ?int $fallbackUserId): void
    {
        $updates = [];

        foreach ([
            'codigo' => filled($row->codigo) ? trim((string) $row->codigo) : null,
            'nombres' => $row->nombres,
            'apellidos' => $row->apellidos,
            'telefono' => $row->celular,
            'direccion' => $row->direccion,
        ] as $field => $incoming) {
            if (blank($cliente->{$field}) && filled($incoming)) {
                $updates[$field] = $incoming;
            }
        }

        if (blank($cliente->email) && filled($row->correo)) {
            $resolvedEmail = $this->resolveClienteEmail($row->correo, $cliente->id);
            if ($resolvedEmail !== null) {
                $updates['email'] = $resolvedEmail;
            }
        }

        if ($cliente->fecha_nacimiento === null && $row->fechaNacimiento) {
            $updates['fecha_nacimiento'] = $row->fechaNacimiento->toDateString();
        }

        $updates['estado_cliente'] = 'activo';
        $updates['updated_by'] = $fallbackUserId;

        if ($updates !== []) {
            $cliente->update($updates);
        }
    }

    private function resolveClienteEmail(?string $email, ?int $ignoreClienteId = null): ?string
    {
        if (blank($email)) {
            return null;
        }

        $query = Cliente::query()->where('email', $email);
        if ($ignoreClienteId) {
            $query->where('id', '!=', $ignoreClienteId);
        }

        return $query->exists() ? null : Str::lower(trim($email));
    }

    private function findDuplicateMatricula(Cliente $cliente, int $membresiaId, ?CarbonImmutable $fechaInicio, ?CarbonImmutable $fechaFin): ?ClienteMatricula
    {
        $query = ClienteMatricula::query()
            ->where('cliente_id', $cliente->id)
            ->where('tipo', 'membresia')
            ->where('membresia_id', $membresiaId)
            ->whereDate('fecha_inicio', $fechaInicio?->toDateString());

        if ($fechaFin) {
            $query->whereDate('fecha_fin', $fechaFin->toDateString());
        } else {
            $query->whereNull('fecha_fin');
        }

        return $query->first();
    }

    private function resolveMatriculaEstado(?CarbonImmutable $fechaFin): string
    {
        if ($fechaFin === null) {
            return 'activa';
        }

        return $fechaFin->startOfDay()->lt(now()->startOfDay()) ? 'vencida' : 'activa';
    }

    private function normalizeChannel(?string $origen): ?string
    {
        if (blank($origen)) {
            return null;
        }

        return preg_replace('/\s+/u', ' ', trim((string) $origen)) ?: null;
    }

    private function resolveCashMethod(): PaymentMethod
    {
        return PaymentMethod::withTrashed()->firstOrCreate(
            ['nombre' => 'Efectivo'],
            [
                'descripcion' => 'Pago en efectivo',
                'requiere_numero_operacion' => false,
                'requiere_entidad' => false,
                'estado' => 'activo',
            ]
        );
    }
}
