<?php

namespace App\Services\WhatsApp;

interface WhatsAppServiceInterface
{
    /**
     * Enviar mensaje por WhatsApp al número dado.
     * Número en formato E.164 (ej. +51999999999).
     *
     * @return array{ success: bool, message_id?: string, error?: string }
     */
    public function enviar(string $destino, string $contenido): array;

    /**
     * Enviar documento (ej. PDF) por WhatsApp al número dado.
     * Número en formato E.164. Documento en base64.
     *
     * @return array{ success: bool, message_id?: string, error?: string }
     */
    public function enviarDocumento(string $destino, string $documentoBase64, string $nombreArchivo, string $caption = ''): array;
}
