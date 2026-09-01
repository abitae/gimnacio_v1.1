<?php

namespace App\Services\Crm;

use App\Models\Core\Cliente;
use App\Models\Crm\CrmStage;
use App\Models\Crm\Deal;
use App\Models\Crm\Lead;
use App\Services\ClienteMatriculaService;
use App\Services\ClienteService;
use Illuminate\Support\Facades\DB;

class ConvertLeadToClientService
{
    public function __construct(
        protected ClienteService $clienteService,
        protected LeadService $leadService,
        protected ClienteMatriculaService $clienteMatriculaService,
        protected CrmActivityService $crmActivityService,
        protected DealService $dealService,
    ) {}

    /**
     * Convierte un lead a cliente. Si ya existe cliente con mismo documento, vincula el lead a ese cliente.
     * Opcional: activar membresía y registrar pago.
     *
     * @return array{lead: Lead, cliente: Cliente, cliente_created: bool}
     */
    public function convert(Lead $lead, array $data): array
    {
        $this->leadService->assertCanConvert($lead);

        $tipoDocumento = $data['tipo_documento'];
        $numeroDocumento = $data['numero_documento'];

        $existingCliente = $this->leadService->findExistingClienteByDocumento($tipoDocumento, $numeroDocumento);

        return DB::transaction(function () use ($lead, $data, $existingCliente, $tipoDocumento, $numeroDocumento) {
            if ($existingCliente) {
                $existingCliente->update([
                    'lead_origen_id' => $existingCliente->lead_origen_id ?? $lead->id,
                    'asesor_crm_id' => $existingCliente->asesor_crm_id ?? $lead->assigned_to,
                ]);
                $lead->update([
                    'cliente_id' => $existingCliente->id,
                    'estado' => 'convertido',
                    'tipo_documento' => $tipoDocumento,
                    'numero_documento' => $numeroDocumento,
                    'nombres' => $data['nombres'] ?? $lead->nombres,
                    'apellidos' => $data['apellidos'] ?? $lead->apellidos,
                    'converted_by' => auth()->id(),
                    'converted_at' => now(),
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
                    'lead_origen_id' => $lead->id,
                    'asesor_crm_id' => $lead->assigned_to,
                    'created_by' => auth()->id(),
                ]);
                $lead->update([
                    'cliente_id' => $cliente->id,
                    'estado' => 'convertido',
                    'tipo_documento' => $tipoDocumento,
                    'numero_documento' => $numeroDocumento,
                    'nombres' => $data['nombres'],
                    'apellidos' => $data['apellidos'],
                    'converted_by' => auth()->id(),
                    'converted_at' => now(),
                ]);
                $created = true;
            }

            $wonStage = CrmStage::query()->where('is_won', true)->orderBy('orden')->first();
            if ($wonStage) {
                $lead->update([
                    'stage_id' => $wonStage->id,
                    'estado' => 'convertido',
                ]);
            }

            if (! empty($data['activar_membresia']) && ! empty($data['membresia_id'])) {
                $this->activateMembresia($cliente, $data);
            }

            $this->finalizeDeals($lead, $cliente);

            $this->crmActivityService->create([
                'lead_id' => $lead->id,
                'cliente_id' => $cliente->id,
                'tipo' => 'note',
                'observaciones' => $created
                    ? 'Lead convertido: cliente creado.'
                    : 'Lead convertido: vinculado a cliente existente.',
                'fecha_hora' => now(),
                'user_id' => auth()->id(),
            ]);

            return [
                'lead' => $lead->fresh(['stage', 'cliente']),
                'cliente' => $cliente->fresh(),
                'cliente_created' => $created,
            ];
        });
    }

    protected function finalizeDeals(Lead $lead, Cliente $cliente): void
    {
        $openDeals = $lead->deals()->where('estado', 'open')->get();

        if ($openDeals->isEmpty()) {
            return;
        }

        foreach ($openDeals as $deal) {
            $this->dealService->markWon($deal, $cliente->id, syncLead: false);
        }
    }

    protected function activateMembresia(Cliente $cliente, array $data): void
    {
        $membresia = \App\Models\Core\Membresia::find($data['membresia_id']);
        if (! $membresia) {
            return;
        }
        $precioFinal = (float) $membresia->precio_base - (float) ($data['pago']['descuento'] ?? 0);
        $montoInicial = (float) ($data['pago']['monto'] ?? 0);

        $this->clienteMatriculaService->create([
            'cliente_id' => $cliente->id,
            'tipo' => 'membresia',
            'membresia_id' => $membresia->id,
            'fecha_matricula' => now()->toDateString(),
            'fecha_inicio' => now()->toDateString(),
            'estado' => 'activa',
            'precio_lista' => $membresia->precio_base,
            'descuento_monto' => $data['pago']['descuento'] ?? 0,
            'precio_final' => max(0, $precioFinal),
            'asesor_id' => auth()->id(),
            'canal_venta' => 'crm',
            'modalidad_pago' => 'contado',
            'monto_pago_inicial' => $montoInicial > 0 && $montoInicial < max(0.01, $precioFinal) ? $montoInicial : null,
        ]);
    }
}
