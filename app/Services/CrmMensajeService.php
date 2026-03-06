<?php

namespace App\Services;

use App\Models\Core\CrmMensaje;
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
            ->with(['cliente', 'creadoPor'])
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

    public function find(int $id): ?CrmMensaje
    {
        return CrmMensaje::with(['cliente', 'creadoPor'])->find($id);
    }

    /**
     * Crear registro y enviar por el canal indicado (por ahora solo WhatsApp).
     */
    public function enviarWhatsApp(int $clienteId, string $contenido, ?int $createdBy = null): CrmMensaje
    {
        $cliente = \App\Models\Core\Cliente::findOrFail($clienteId);
        $destino = $cliente->telefono;
        if (empty($destino)) {
            throw new \Exception('El cliente no tiene teléfono registrado.');
        }
        // Asegurar formato E.164 si no tiene +
        if (! str_starts_with($destino, '+')) {
            $destino = preg_replace('/^0/', '', $destino);
            $destino = (str_starts_with($destino, '51') ? '+' : '+51') . $destino;
        }

        $validated = Validator::make([
            'cliente_id' => $clienteId,
            'canal' => 'whatsapp',
            'destino' => $destino,
            'contenido' => $contenido,
            'created_by' => $createdBy,
        ], [
            'cliente_id' => 'required|exists:clientes,id',
            'canal' => 'required|in:whatsapp,email,sms',
            'destino' => 'required|string|max:100',
            'contenido' => 'required|string',
            'created_by' => 'nullable|exists:users,id',
        ])->validate();

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

        return $mensaje->fresh(['cliente', 'creadoPor']);
    }

    public function listarMensajes(?int $clienteId = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getByCliente($clienteId, [], $perPage);
    }
}
