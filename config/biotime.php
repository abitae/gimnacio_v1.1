<?php

return [
    'queue' => env('BIOTIME_SYNC_QUEUE', true),

    /** Intervalo del schedule de reconcile de acceso (minutos). Default 60 = cada hora. */
    'access_reconcile_minutes' => (int) env('BIOTIME_ACCESS_RECONCILE_MINUTES', 60),
];
