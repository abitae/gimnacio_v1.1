<?php

return [
    'temporary_file_upload' => [
        'disk' => 'local',
        'rules' => ['required', 'file', 'mimes:sql,txt', 'max:512000'],
        'directory' => 'livewire-tmp',
        'middleware' => null,
        'max_upload_time' => 15,
        'cleanup' => true,
    ],
];
