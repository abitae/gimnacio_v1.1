# Orden de migraciones y seeders

## Fases de migración

La secuencia actual de migraciones queda organizada por capas de dependencia:

1. Infraestructura base
   `users`, `cache`, `jobs`, columnas de autenticación y apariencia.
2. Núcleo legacy del gimnasio
   `clientes`, `membresias`, `cliente_membresias`, `pagos`, `asistencias`, `gym_settings`, `audit_logs`.
3. Seguridad y caja
   permisos, `cajas`, relación `pagos.caja_id`.
4. Catálogos y ventas
   categorías, productos, servicios, clases, ventas, items, inventario, comprobantes y movimientos de caja.
5. Matrículas y bienestar
   `cliente_matriculas`, nutrición, citas, seguimientos, metas y salud.
6. CRM y entrenamiento
   stages, motivos, etiquetas, leads, deals, tareas, campañas, ejercicios y rutinas.
7. Operación extendida
   métodos de pago, deudas, cupones, alquileres, empleados y ajustes funcionales.
8. Multiempresa / sucursales
   `empresas`, `sucursales`, pivote `sucursal_user` y `sucursal_id` en módulos operativos.

## Secuencia correcta de seeders

### Producción limpia

Usar:

```bash
php artisan db:seed --class=ProductionBootstrapSeeder
```

Orden:

1. `BaseCatalogSeeder`
2. `CompanyBranchSeeder`
3. `AdminUserSeeder`

Motivo:

- `CompanyBranchSeeder` reutiliza `gym_settings`, por eso debe ir después de `BaseCatalogSeeder`.
- `AdminUserSeeder` necesita que exista al menos una sucursal para asignar `default_sucursal_id`.

### Producción con dump

Usar:

```bash
php artisan db:seed --class=BundledSqlBackupSeeder
```

Orden interno:

1. Restaurar `backup_part*.sql`
2. `CompanyBranchSeeder`
3. `AdminUserSeeder`

Motivo:

- El dump legacy no garantiza las nuevas tablas de multiempresa/sucursal.
- Después de restaurar, se recompone el bootstrap actual sin mezclar datos demo.

### Desarrollo / demo

Usar:

```bash
php artisan db:seed --class=DevelopmentSeeder
```

Orden:

1. `ProductionBootstrapSeeder`
2. `DemoDataSeeder`

### QA / pruebas / estrés

Usar:

```bash
php artisan db:seed --class=TestDataSeeder
```

Orden:

1. `ProductionBootstrapSeeder`
2. `ScenarioSeeder`
3. `EdgeCaseSeeder`
4. `MassiveRootSeeder`

## Separación por intención

- Seeders productivos:
  `ProductionBootstrapSeeder`, `BaseCatalogSeeder`, `CompanyBranchSeeder`, `AdminUserSeeder`, `BundledSqlBackupSeeder`
- Seeders de desarrollo/demo:
  `DevelopmentSeeder`, `DemoDataSeeder`
- Seeders de prueba/QA:
  `TestDataSeeder`, `ScenarioSeeder`, `EdgeCaseSeeder`, `MassiveRootSeeder`
- Seeders puntuales por módulo:
  clientes, membresías, clases, cajas, CRM, nutrición, ejercicios, etc.

## Seeder por defecto

`DatabaseSeeder` ahora ejecuta solo `ProductionBootstrapSeeder`.

Esto evita que una instalación limpia cargue datos demo o escenarios de prueba por accidente.
