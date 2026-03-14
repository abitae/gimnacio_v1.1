<?php

namespace App\Services\Crm;

use App\Models\Core\Cliente;
use App\Models\Crm\Lead;
use App\Services\ClienteMatriculaService;
use App\Services\ClienteService;
use Illuminate\Support\Facades\DB;

class ConvertLeadToClientService
{
    public function __construct(
        protected ClienteService $clienteService,
        protected LeadService $leadService,
        protected ClienteMatriculaService $clienteMatriculaService
    ) {}

    /**
     * Convierte un lead a cliente. Si ya existe cliente con mismo documento, vincula el lead a ese cliente.
     * Opcional: activar membresía y registrar pago.
     */
    public function convert(Lead $lead, array $data): array
    {
        $tipoDocumento = $data['tipo_documento'];
        $numeroDocumento = $data['numero_documento'];

        $existingCliente = $this->leadService->findExistingClienteByDocumento($tipoDocumento, $numeroDocumento);

        return DB::transaction(function () use ($lead, $data, $existingCliente, $tipoDocumento, $numeroDocumento) {
            if ($existingCliente) {
                $lead->update([
                    'cliente_id' => $existingCliente->id,
                    'estado' => 'convertido',
                    'tipo_documento' => $tipoDocumento,
                    'numero_documento' => $numeroDocumento,
                    'nombres' => $data['nombres'] ?? $lead->nombres,
                    'apellidos' => $data['apellidos'] ?? $lead->apellidos,
                ]);
                $cliente = $existingCliente;
                $created = false;
            } else {
                $cliente = $this->clienteService->create([
                    'tipo_documento' => $tipoDocumento,
                    'numero_documento' => $numeroDocumento,
                    'nombres' => $data['nombres'],
                    'apellidos' => $data['apellidos'],
                    'telefono' => $data['telefono'] ?? $lead->telefono,
                    'email' => $data['email'] ?? $lead->email,
                    'direccion' => $data['direccion'] ?? $lead->direccion ?? null,
                    'estado_cliente' => 'activo',
                    'created_by' => auth()->id(),
                ]);
                $lead->update([
                    'cliente_id' => $cliente->id,
                    'estado' => 'convertido',
                    'tipo_documento' => $tipoDocumento,
                    'numero_documento' => $numeroDocumento,
                    'nombres' => $data['nombres'],
                    'apellidos' => $data['apellidos'],
                ]);
                $created = true;
            }

            if (!empty($data['activar_membresia']) && !empty($data['membresia_id'])) {
                $this->activateMembresia($cliente, $data);
            }

            return [
                'lead' => $lead->fresh(),
                'cliente' => $cliente->fresh(),
                'cliente_created' => $created,
            ];
        });
    }

    protected function activateMembresia(Cliente $cliente, array $data): void
    {
        $membresia = \App\Models\Core\Membresia::find($data['membresia_id']);
        if (!$membresia) {
            return;
        }
        $matricula = $this->clienteMatriculaService->create([
            'cliente_id' => $cliente->id,
            'tipo' => 'membresia',
            'membresia_id' => $membresia->id,
            'fecha_matricula' => now()->toDateString(),
            'fecha_inicio' => now()->toDateString(),
            'estado' => 'activa',
            'precio_lista' => $membresia->precio_base,
            'descuento_monto' => $data['pago']['descuento'] ?? 0,
            'precio_final' => $membresia->precio_base - ($data['pago']['descuento'] ?? 0),
            'asesor_id' => auth()->id(),
            'canal_venta' => 'crm',
            'modalidad_pago' => 'contado',
        ]);

        if (! empty($data['pago']['monto']) && $data['pago']['monto'] > 0) {
            $cajaAbierta = \App\Models\Core\Caja::where('estado', 'abierta')->first();
            if ($cajaAbierta) {
                $this->clienteMatriculaService->procesarPago($matricula->id, [
                    'monto_pago' => $data['pago']['monto'],
                    'metodo_pago' => $data['pago']['metodo_pago'] ?? 'efectivo',
                    'fecha_pago' => now(),
                ]);
            }
        }
    }
}
