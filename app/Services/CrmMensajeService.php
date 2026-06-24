<?php

namespace App\Services;

use App\Models\Core\CrmMensaje;
use App\Models\Crm\Lead;
use App\Services\WhatsApp\WhatsAppServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

class CrmMensajeService
{
    public function __construct(
        protected WhatsAppServiceInterface $whatsAppService
    ) {}

    public function getByCliente(?int $clienteId = null, array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CrmMensaje::query()
            ->with(['cliente', 'lead', 'creadoPor'])
            ->orderBy('created_at', 'desc');

        if ($clienteId) {
            $query->where('cliente_id', $clienteId);
        }
        if (! empty($filtros['canal'])) {
            $query->where('canal', $filtros['canal']);
        }
        if (! empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        return $query->paginate($perPage);
    }

    public function getByLead(?int $leadId = null, array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CrmMensaje::query()
            ->with(['cliente', 'lead', 'creadoPor'])
            ->orderBy('created_at', 'desc');

        if ($leadId) {
            $query->where('lead_id', $leadId);
        }
        if (! empty($filtros['canal'])) {
            $query->where('canal', $filtros['canal']);
        }
        if (! empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?CrmMensaje
    {
        return CrmMensaje::with(['cliente', 'lead', 'creadoPor'])->find($id);
    }

    public function enviarWhatsApp(int $clienteId, string $contenido, ?int $createdBy = null): CrmMensaje
    {
        $cliente = \App\Models\Core\Cliente::findOrFail($clienteId);
        $destino = $this->normalizePhone($cliente->telefono);

        return $this->dispatchWhatsApp([
            'cliente_id' => $clienteId,
            'lead_id' => null,
            'destino' => $destino,
            'contenido' => $contenido,
            'created_by' => $createdBy,
        ]);
    }

    public function enviarWhatsAppLead(int $leadId, string $contenido, ?int $createdBy = null): CrmMensaje
    {
        $lead = Lead::findOrFail($leadId);
        $destino = $this->normalizePhone($lead->whatsapp ?: $lead->telefono);

        return $this->dispatchWhatsApp([
            'cliente_id' => $lead->cliente_id,
            'lead_id' => $leadId,
            'destino' => $destino,
            'contenido' => $contenido,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * @param  array{cliente_id: ?int, lead_id: ?int, destino: string, contenido: string, created_by: ?int}  $payload
     */
    protected function dispatchWhatsApp(array $payload): CrmMensaje
    {
        if (empty($payload['destino'])) {
            throw new \Exception('No hay teléfono registrado para enviar WhatsApp.');
        }

        $validated = Validator::make([
            'cliente_id' => $payload['cliente_id'],
            'lead_id' => $payload['lead_id'],
            'canal' => 'whatsapp',
            'destino' => $payload['destino'],
            'contenido' => $payload['contenido'],
            'created_by' => $payload['created_by'],
        ], [
            'cliente_id' => 'nullable|exists:clientes,id',
            'lead_id' => 'nullable|exists:crm_leads,id',
            'canal' => 'required|in:whatsapp,email,sms',
            'destino' => 'required|string|max:100',
            'contenido' => 'required|string',
            'created_by' => 'nullable|exists:users,id',
        ])->validate();

        if (empty($validated['cliente_id']) && empty($validated['lead_id'])) {
            throw new \Exception('Debe indicar cliente o lead.');
        }

        $mensaje = CrmMensaje::create([
            ...$validated,
            'estado' => 'pendiente',
        ]);

        $result = $this->whatsAppService->enviar($mensaje->destino, $mensaje->contenido);

        if ($result['success']) {
            $mensaje->update([
                'estado' => 'enviado',
                'enviado_at' => now(),
                'error_mensaje' => null,
            ]);
        } else {
            $mensaje->update([
                'estado' => 'fallido',
                'error_mensaje' => $result['error'] ?? 'Error desconocido',
            ]);
        }

        return $mensaje->fresh(['cliente', 'lead', 'creadoPor']);
    }

    protected function normalizePhone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        $destino = $phone;
        if (! str_starts_with($destino, '+')) {
            $destino = preg_replace('/^0/', '', $destino);
            $destino = (str_starts_with($destino, '51') ? '+' : '+51').$destino;
        }

        return $destino;
    }

    public function listarMensajes(?int $clienteId = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getByCliente($clienteId, [], $perPage);
    }
}
