<?php

/**
 * Crea la base de datos de tests si no existe. Lee credenciales de .env.testing.
 *
 * Uso: php scripts/ensure-test-database.php
 */
$root = dirname(__DIR__);
$envFile = $root.'/.env.testing';

if (! is_file($envFile)) {
    fwrite(STDERR, "Falta .env.testing. Copie .env.testing.example y ajuste DB_PASSWORD.\n");
    exit(1);
}

$vars = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $vars[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
}

$host = $vars['DB_HOST'] ?? '127.0.0.1';
$port = $vars['DB_PORT'] ?? '3306';
$user = $vars['DB_USERNAME'] ?? 'root';
$password = $vars['DB_PASSWORD'] ?? '';
$database = $vars['DB_DATABASE'] ?? 'gimnacio_v1_test';

$dsn = sprintf('mysql:host=%s;port=%s', $host, $port);

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        str_replace('`', '``', $database)
    ));
    echo "Base de datos de tests lista: {$database}\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'Error al crear la BD de tests: '.$e->getMessage()."\n");
    exit(1);
}
