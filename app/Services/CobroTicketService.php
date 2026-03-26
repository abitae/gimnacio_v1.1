<?php

namespace App\Services;

use App\Models\System\CobroTicketSecuencia;
use Illuminate\Support\Facades\DB;

class CobroTicketService
{
    private string $prefijo = 'C';

    /**
     * Genera el siguiente número de ticket para cobros, con lock para evitar duplicados.
     *
     * @return array{tipo:string,numero:string}
     */
    public function siguienteComprobanteTicket(): array
    {
        $numero = DB::transaction(function () {
            $seq = CobroTicketSecuencia::query()
                ->where('id', 1)
                ->lockForUpdate()
                ->first();

            // Debería existir por la migración, pero lo cubrimos por seguridad.
            if (! $seq) {
                $seq = CobroTicketSecuencia::query()->create([
                    'id' => 1,
                    'ultimo_numero' => 0,
                ]);
                $seq = CobroTicketSecuencia::query()->where('id', 1)->lockForUpdate()->first();
            }

            $nuevo = ((int) $seq->ultimo_numero) + 1;
            $seq->ultimo_numero = $nuevo;
            $seq->save();

            return $nuevo;
        });

        return [
            'tipo' => 'ticket',
            'numero' => $this->prefijo.str_pad((string) $numero, 6, '0', STR_PAD_LEFT),
        ];
    }

    /**
     * Resuelve comprobante para un Pago: autogenera si llega vacío.
     *
     * @return array{tipo:string,numero:string}
     */
    public function resolverComprobantePago(array $data): array
    {
        $tipo = $data['comprobante_tipo'] ?? null;
        $numero = $data['comprobante_numero'] ?? null;

        if (empty(trim((string) $tipo)) || empty(trim((string) $numero))) {
            return $this->siguienteComprobanteTicket();
        }

        return [
            'tipo' => (string) $tipo,
            'numero' => (string) $numero,
        ];
    }
}
