<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Exportaciones en cola (Excel / PDF de módulo reportes)
    |--------------------------------------------------------------------------
    |
    | Si es true, las exportaciones desde ReporteModuloController se despachan
    | como Job. Requiere QUEUE_CONNECTION distinto de sync y un worker activo
    | (p. ej. php artisan queue:work). El usuario recibe una notificación en BD
    | con enlace de descarga.
    |
    */
    'queue_exports' => env('REPORTES_QUEUE_EXPORTS', false),

];
