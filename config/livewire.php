<?php

return [
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TMP_DISK', env('FILESYSTEM_DISK', 'public')),
        'rules' => ['required', 'file', 'mimes:zip,sql,txt,xlsx,xls,csv', 'max:512000'],
        'directory' => 'livewire-tmp',
        'middleware' => null,
        'max_upload_time' => 15,
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
