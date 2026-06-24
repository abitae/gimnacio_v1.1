<?php

$backupRestoreMaxKb = max(1, (int) env('BACKUP_RESTORE_MAX_MB', 1024)) * 1024;

return [
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TMP_DISK', env('FILESYSTEM_DISK', 'public')),
        'rules' => ['required', 'file', 'mimes:zip,sql,txt,xlsx,xls,csv', 'max:'.$backupRestoreMaxKb],
        'directory' => 'livewire-tmp',
        'middleware' => null,
        'max_upload_time' => (int) env('LIVEWIRE_MAX_UPLOAD_TIME', 60),
        'cleanup' => true,
    ],

    /*
    |---------------------------------------------------------------------------
    | Payload guards
    |---------------------------------------------------------------------------
    | max_size: tamaño máximo del cuerpo de la petición Livewire (en bytes).
    | Subir importaciones Excel o vistas previa grandes puede superar 1 MB.
    */
    'payload' => [
        'max_size' => 10 * 1024 * 1024,
        'max_nesting_depth' => 10,
        'max_calls' => 50,
        'max_components' => 200,
    ],
];
