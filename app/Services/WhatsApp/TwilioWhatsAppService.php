<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;

class TwilioWhatsAppService implements WhatsAppServiceInterface
{
    public function enviar(string $destino, string $contenido): array
    {
        $sid = config('services.whatsapp.twilio.account_sid');
        $token = config('services.whatsapp.twilio.auth_token');
        $from = config('services.whatsapp.twilio.from');

        if (! $sid || ! $token || ! $from) {
            return ['success' => false, 'error' => 'Twilio WhatsApp no configurado.'];
        }

        $to = str_starts_with($destino, 'whatsapp:') ? $destino : 'whatsapp:'.$destino;

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => str_starts_with($from, 'whatsapp:') ? $from : 'whatsapp:'.$from,
                'To' => $to,
                'Body' => $contenido,
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message_id' => $response->json('sid'),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? $response->body(),
        ];
    }

    public function enviarDocumento(string $destino, string $documentoBase64, string $nombreArchivo, string $caption = ''): array
    {
        return [
            'success' => false,
            'error' => 'Envío de documentos por Twilio no implementado en esta versión.',
        ];
    }
}
