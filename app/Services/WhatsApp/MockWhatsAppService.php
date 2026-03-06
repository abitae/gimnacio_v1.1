<?php

namespace App\Services\WhatsApp;

class MockWhatsAppService implements WhatsAppServiceInterface
{
    public function enviar(string $destino, string $contenido): array
    {
        // Simula envío exitoso; en producción reemplazar por Twilio/WhatsApp Business API
        return [
            'success' => true,
            'message_id' => 'mock_' . uniqid(),
        ];
    }

    public function enviarDocumento(string $destino, string $documentoBase64, string $nombreArchivo, string $caption = ''): array
    {
        // Simula envío de documento exitoso; en producción subir PDF y enviar vía API
        return [
            'success' => true,
            'message_id' => 'mock_doc_' . uniqid(),
        ];
    }
}
