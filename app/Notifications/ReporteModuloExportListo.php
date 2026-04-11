<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ReporteModuloExportListo extends Notification
{
    use Queueable;

    public function __construct(
        public string $exportRef,
        public string $storagePath,
        public string $downloadFilename,
        public string $modulo,
        public string $format
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'export_ref' => $this->exportRef,
            'message' => 'Tu exportación de reporte está lista para descargar.',
            'modulo' => $this->modulo,
            'format' => $this->format,
            'filename' => $this->downloadFilename,
            'storage_path' => $this->storagePath,
            'download_url' => URL::route('reportes.exportaciones.descargar', ['exportRef' => $this->exportRef]),
        ];
    }
}
