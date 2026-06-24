# Configuración de depuración — gimnacio_v1.1

Guía para establecer un baseline reproducible antes de depurar o refactorizar.

## Variables de entorno recomendadas (local)

```env
APP_DEBUG=true
LOG_LEVEL=debug
DB_SLOW_QUERY_LOG_MS=200
QUEUE_CONNECTION=database
```

Si `REPORTES_QUEUE_EXPORTS=true` o `BIOTIME_SYNC_QUEUE=true`, ejecutar `php artisan queue:listen`.

## Base de datos de tests

1. Crear la BD: `CREATE DATABASE gimnacio_v1_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
2. Copiar credenciales: `cp .env.testing.example .env.testing` y alinear `DB_PASSWORD` con `.env`.
3. PHPUnit usa `.env.testing`; si `DB_PASSWORD` en `phpunit.xml` no coincide, eliminar esa línea y confiar en `.env.testing`.

## Baseline de tests

```bash
composer test
```

Registrar localmente el resultado (no commitear): `storage/debug-baseline.txt` está en `.gitignore`.

Tests omitidos conocidos (6): 2FA deshabilitado en Fortify, restore en Windows.

## Seeders por escenario

| Escenario | Comando |
|-----------|---------|
| Demo UI | `php artisan db:seed --class=DevelopmentSeeder` |
| Estrés | `php artisan db:seed --class=TestDataSeeder` |
| Legacy / deudas | `php artisan db:seed --class=BundledSqlBackupSeeder` |
| Producción limpia | `php artisan db:seed --class=ProductionBootstrapSeeder` |

## Checklist de salud

- [ ] `GET /up` responde OK
- [ ] Login con sucursal activa
- [ ] Caja abierta antes de probar POS
- [ ] `storage/logs/laravel.log` escribible
- [ ] `composer test` en verde

## Logs operativos

Errores de POS, caja, checking y BioTime se escriben en `storage/logs/operations.log` (canal `operations`).

## Telescope (solo local)

Con `APP_ENV=local`, Telescope se registra automáticamente. Acceso: `/telescope`. En tests y CI está deshabilitado (`TELESCOPE_ENABLED=false` en `phpunit.xml`).

## Playbook operaciones (tests automatizados)

```bash
php artisan test tests/Feature/Consistency
php artisan test tests/Feature/Services/DailyOperationsDebtServiceTest.php
php artisan test tests/Feature/Services/ClientDebtServiceTest.php
php artisan test tests/Feature/Livewire/CheckingLiveTest.php
php artisan test tests/Feature/Livewire/CajaLiveTest.php
```

### Baseline conocido (2026-06-24)

| Suite | Estado |
|-------|--------|
| `DebtParityTest` | Verde (3 tests) |
| `DailyOperationsDebtServiceTest` | Verde |
| `ClientDebtServiceTest` | Verde |
| `CheckingLiveTest` / `CajaLiveTest` | Verde |
| `CheckingPermissionTest` | Requiere usuario con sucursal asignada (middleware) |
| `VentaPagosMixtosTest` | Requiere `numero_operacion` en método Efectivo del seeder |
| Suite completa | Ejecutar `composer test` (puede tardar >5 min) |
