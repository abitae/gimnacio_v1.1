<?php

return [
    'queue' => env('BIOTIME_SYNC_QUEUE', true),

    /** Intervalo del schedule de reconcile de acceso (minutos). Default 60 = cada hora. */
    'access_reconcile_minutes' => (int) env('BIOTIME_ACCESS_RECONCILE_MINUTES', 60),

    /** Cupo maximo de empleados por instancia BioTime (sede). */
    'employee_limit_default' => (int) env('BIOTIME_EMPLOYEE_LIMIT', 500),

    /** Umbral de alerta de ocupacion (0-100). */
    'employee_limit_alert_percent' => (int) env('BIOTIME_EMPLOYEE_LIMIT_ALERT_PERCENT', 90),
];
