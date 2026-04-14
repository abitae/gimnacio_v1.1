<?php

return [
    'max_upload_kb' => 10240,
    'allowed_extensions' => ['xlsx', 'xls', 'csv'],
    /** Límite de tiempo PHP (segundos) durante lectura Excel + procesamiento + persistencia de filas. 0 = sin cambiar el límite actual. */
    'time_limit_seconds' => 600,
    /** Inserción masiva de import_rows por lote. */
    'import_rows_chunk_size' => 250,
    /** Dominio del correo sintético para vendedores creados desde Excel. */
    'seller_email_domain' => 'empresa.test',
    /** Contraseña inicial (el modelo User la hashea al guardar) para usuarios creados solo por importación legacy. */
    'default_import_user_password' => 'user123',
    /** Rol Spatie asignado a vendedores creados por importación (debe existir en seeders/migraciones). */
    'seller_role' => 'vendedor',
    /** Si es true, la importación de membresías crea el paquete en catálogo cuando no exista. Si es false, se exige que exista. */
    'allow_create_membership_on_import' => true,
    /** Tolerancia al comparar montos entre filas de un mismo grupo o Excel de resumen vs deuda cuotificada. */
    'money_tolerance' => 0.02,
];
