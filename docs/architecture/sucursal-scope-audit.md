# Auditoría alcance por sucursal

Generado como parte del plan de aislamiento multi-sucursal.

## Bypasses `withoutGlobalScope` conocidos (lista blanca)

| Archivo | Uso | Justificación |
|---------|-----|----------------|
| `AsistenciaService.php` | Cierre global asistencias | Comando/cron multi-sede |
| `ProductoService.php` | Unicidad código cross-sede | Validación al crear |
| `ClaseService.php` | Unicidad código cross-sede | Validación al crear |
| `ClienteCrossSucursalAlertService.php` | Buscar mismo documento en otras sedes | Aviso read-only |
| `SucursalScope.php` | Reportes consolidados super admin | Solo lectura reportes |

Verificación CI: `tests/Unit/Security/WithoutGlobalScopeAllowlistTest.php`.

## Capas de enforcement

1. **HTTP:** `EnsureSucursalContext` en rutas operativas; dashboard dentro del grupo.
2. **ORM:** `BelongsToSucursal` fail-closed (`WHERE 1=0` sin contexto autenticado).
3. **Route binding:** `SucursalScopedRouteBinding` para `{cliente}`, `{lead}`, `{coupon}`, `{deal}`.
4. **DB:** columnas `sucursal_id`, backfill, unique compuestos `(sucursal_id, codigo)`.
5. **Tests:** `tests/Feature/Services/SucursalIsolationTest.php`.

## Modelos con `BelongsToSucursal`

Core operativos, CRM (11), nutrición, cupones, cuotas, ejercicios/rutinas, empleados asistencias/deudas, imports, BioTime mappings.

## Rutas sin `sucursal.context` (permitidas)

- `/` welcome
- Login / Fortify
- `/seleccionar-sucursal` (auth only)
- `/reportes/evaluacion/descargar/{id}` (signed URL)

## Reportes multi-sede (super admin)

- **DTO:** `App\Data\Reporte\ReporteSucursalFilter` — modos `active`, `specific`, `consolidated`.
- **UI:** `resources/views/components/reportes/sucursal-scope-panel.blade.php` en todos los reportes Livewire + CRM reportes.
- **Trait Livewire:** `ScopesReporteBySucursal` expone `$reporteModoSucursal` y `$reporteSucursalId`.
- **Servicios:** `ReporteModuloService::applyReporteScope()`, `CrmReportService`, `FinanceAnalyticsService` (cuentas por cobrar).
- **Exportaciones:** query params `reporte_modo_sucursal` / `reporte_sucursal_id` vía `exportar-buttons` y `ExportReporteModuloJob`.
- **Comportamiento:**
  - Usuario normal → solo sucursal activa (global scope).
  - Super admin → selector: sede activa, otra sede asignada, o consolidado (todas sus sedes, solo lectura).
- **Tests:** `tests/Feature/Reportes/ReporteConsolidadoSuperAdminTest.php`.

## Aviso cross-sucursal (sin traspaso)

- Servicio: `ClienteCrossSucursalAlertService`
- Componente: `resources/views/components/cliente/cross-sucursal-alert.blade.php`
- UI: Dashboard, Checking, POS, perfil cliente, modal alta/edición cliente

## Migraciones clave

- `2026_07_24_180000_enforce_sucursal_scope_columns.php` — columnas + backfill cupones
- `2026_07_24_180100_bio_time_mappings_sucursal_unique.php` — unique BioTime por sede
- `2026_07_24_180200_enforce_sucursal_id_not_null.php` — NOT NULL post-backfill
- `2026_07_24_180300_catalog_sucursal_codigo_unique.php` — productos/servicios/membresías por sede
