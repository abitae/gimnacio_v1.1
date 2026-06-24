<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;

class HttpWebhookWhatsAppService implements WhatsAppServiceInterface
{
    public function enviar(string $destino, string $contenido): array
    {
        $url = config('services.whatsapp.http.url');
        $token = config('services.whatsapp.http.token');

        if (! $url) {
            return ['success' => false, 'error' => 'Webhook WhatsApp HTTP no configurado.'];
        }

        $request = Http::timeout(30);
        if ($token) {
            $request = $request->withToken($token);
        }

        $response = $request->post($url, [
            'destino' => $destino,
            'contenido' => $contenido,
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message_id' => $response->json('message_id') ?? $response->json('id'),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? $response->body(),
        ];
    }

    public function enviarDocumento(string $destino, string $documentoBase64, string $nombreArchivo, string $caption = ''): array
    {
        $url = config('services.whatsapp.http.url');
        $token = config('services.whatsapp.http.token');

        if (! $url) {
            return ['success' => false, 'error' => 'Webhook WhatsApp HTTP no configurado.'];
        }

        $request = Http::timeout(60);
        if ($token) {
            $request = $request->withToken($token);
        }

        $response = $request->post($url, [
            'destino' => $destino,
            'contenido' => $caption,
            'documento_base64' => $documentoBase64,
            'nombre_archivo' => $nombreArchivo,
            'tipo' => 'documento',
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message_id' => $response->json('message_id') ?? $response->json('id'),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? $response->body(),
        ];
    }
}
