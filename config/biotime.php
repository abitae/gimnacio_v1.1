<?php

return [
    'queue' => env('BIOTIME_SYNC_QUEUE', true),

    /** Intervalo del schedule de reconcile de acceso (minutos). Default 60 = cada hora. */
    'access_reconcile_minutes' => (int) env('BIOTIME_ACCESS_RECONCILE_MINUTES', 60),

    /** Cupo maximo de empleados por instancia BioTime (sede). */
    'employee_limit_default' => (int) env('BIOTIME_EMPLOYEE_LIMIT', 500),

    /** Umbral de alerta de ocupacion (0-100). */
    'employee_limit_alert_percent' => (int) env('BIOTIME_EMPLOYEE_LIMIT_ALERT_PERCENT', 90),

    /** Ejecutable Windows del puente (PyInstaller). */
    'bridge_exe_path' => env('BIOTIME_BRIDGE_EXE_PATH', public_path('dist/dist/BioTimeBridge.exe')),

    /** Plantilla YAML empaquetada como config.yaml en la descarga. */
    'bridge_config_path' => env('BIOTIME_BRIDGE_CONFIG_PATH', public_path('dist/dist/config.yaml.example')),

    /** Rutas alternativas si los archivos publicos no existen (desarrollo local). */
    'bridge_exe_fallback_paths' => [
        base_path('tools/biotime-bridge/releases/BioTimeBridge.exe'),
        base_path('tools/biotime-bridge/dist/BioTimeBridge.exe'),
        base_path('tools/biotime-bridge/BioTimeBridge.exe'),
    ],

    'bridge_config_fallback_paths' => [
        base_path('tools/biotime-bridge/config.yaml.example'),
        base_path('tools/biotime-bridge/dist/config.yaml.example'),
    ],
];
