<?php

/**
 * Verifica permisos del catalogo contra la base de datos.
 *
 * Uso:
 *   php scripts/verify-permissions.php
 *   php scripts/verify-permissions.php --roles
 *   php scripts/verify-permissions.php --sync
 *   php scripts/verify-permissions.php --json
 */

$root = dirname(__DIR__);

if (! is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "Ejecuta composer install en {$root} primero.\n");
    exit(1);
}

$args = array_slice($argv, 1);
$escaped = array_map(static fn (string $arg) => escapeshellarg($arg), $args);
$command = 'php '.escapeshellarg($root.'/artisan').' permissions:audit';

if ($args !== []) {
    $command .= ' '.implode(' ', $escaped);
}

passthru($command, $exitCode);

exit($exitCode);
