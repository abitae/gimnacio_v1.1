<?php

/**
 * Sincroniza y verifica permisos/roles del catalogo contra la base de datos.
 *
 * Uso:
 *   php scripts/verify-permissions.php              # sincroniza + audita roles
 *   php scripts/verify-permissions.php --audit-only # solo audita, sin escribir en BD
 *   php scripts/verify-permissions.php --json
 */

$root = dirname(__DIR__);

if (! is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "Ejecuta composer install en {$root} primero.\n");
    exit(1);
}

$args = array_slice($argv, 1);
$auditOnly = in_array('--audit-only', $args, true) || in_array('--check', $args, true);
$args = array_values(array_filter(
    $args,
    static fn (string $arg) => ! in_array($arg, ['--audit-only', '--check'], true)
));

$flags = ['--roles'];
if (! $auditOnly) {
    $flags[] = '--sync';
}

foreach ($flags as $flag) {
    if (! in_array($flag, $args, true)) {
        $args[] = $flag;
    }
}

$escaped = array_map(static fn (string $arg) => escapeshellarg($arg), $args);
$command = 'php '.escapeshellarg($root.'/artisan').' permissions:audit';

if ($args !== []) {
    $command .= ' '.implode(' ', $escaped);
}

passthru($command, $exitCode);

exit($exitCode);
