<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tamaño máximo de archivo para restauración (MB)
    |--------------------------------------------------------------------------
    |
    | Aplica a la subida Livewire y a la validación del componente de backups.
    | Asegúrate de que PHP (upload_max_filesize, post_max_size) permita al menos
    | este tamaño en el servidor web.
    |
    */
    'restore_max_mb' => (int) env('BACKUP_RESTORE_MAX_MB', 1024),

];
