# Plan de mejora: Analitica

> **Referencia:** [module-consistency-matrix.md](../architecture/module-consistency-matrix.md)  
> **Prioridad:** Alta (orden global #3)  
> **Inconsistencias vinculadas:** INC-03, INC-04, INC-07, INC-09  
> **Ultima actualizacion:** 2026-06-24

---

## 1. Contexto y diagnostico

### Alcance funcional
Centro de reportes, reportes modulares por dominio, export PDF/Excel, cuentas por cobrar, cuotas vencidas, reportes nutricionales legacy.

### Estado actual

| Elemento | Situacion |
| --- | --- |
| `ReporteModuloService` | ~732 LOC; centraliza agregacion |
| Reportes dedicados | Existencia mayoritaria por dominio |
| `reportes.cuentas-por-cobrar` | Reutiliza `POS\CustomerDebts` (critico) |
| Sidebar Analitica | 5 items vs 11 en centro reportes |
| Legacy | `cliente_membresias` en reportes clientes |

### Riesgo principal
Usuario con `reporte.ver` accede a UI transaccional de cobro; saldos pueden divergir de operacion.

### Fuente de verdad objetivo
Servicios agregadores de lectura; mismos contratos numéricos que `DailyOperationsDebtService` (operacion) y servicios analiticos dedicados

---

## 2. Objetivos

1. Separacion estricta Operacion vs Analitica.
2. Reemplazar `CustomerDebts` en reportes por vista solo lectura.
3. Servicios analiticos por dominio (extraccion gradual de ReporteModuloService).
4. Sidebar alineado con centro de reportes.
5. Legacy etiquetado en exportaciones.

---

## 3. Plan por fases

### Fase 1 — Desacople CustomerDebts (critico)

**Objetivo de fase:** Eliminar INC-03 e INC-04.

#### Paso 1.1 — Crear ReporteCuentasPorCobrarLive

- **Objetivo:** Vista analitica solo lectura de cuentas por cobrar.
- **Archivos nuevos:**
  - `app/Livewire/Reportes/ReporteCuentasPorCobrarLive.php`
  - `resources/views/livewire/reportes/reporte-cuentas-por-cobrar-live.blade.php`
- **Tareas:**
  1. Listado paginado clientes/deudas con filtros: sucursal, rango fechas, estado vencido, responsable.
  2. **Sin** modales de cobro ni acciones transaccionales.
  3. Botones: export PDF/Excel, enlace "Ir a cobrar" → `pos.cuentas-por-cobrar` (si tiene permiso POS).
  4. Consumir datos via servicio analitico (Paso 1.2).
- **Criterios de aceptacion:**
  - Usuario solo `reporte.ver` no puede registrar pagos.
  - Totales coinciden con `DailyOperationsDebtService` en fixture test.

#### Paso 1.2 — Crear FinanceAnalyticsService (cuentas por cobrar)

- **Objetivo:** Capa analitica de deudas separada de `ClientDebtService`.
- **Archivos nuevos:** `app/Services/Analytics/FinanceAnalyticsService.php`.
- **Metodos sugeridos:**
  ```text
  getAccountsReceivableSummary(filters): array
  getAccountsReceivableRows(filters): LengthAwarePaginator
  exportAccountsReceivable(filters, format): ExportRef
  ```
- **Tareas:**
  1. Reutilizar queries base alineadas con `DailyOperationsDebtService`.
  2. Agregar dimensiones analiticas: antiguedad deuda, por vendedor, por tipo origen.
  3. Incluir columna `origen`: matricula, venta_credito, legacy_membresia.
- **Criterios de aceptacion:**
  - Test paridad totales vs DailyOperationsDebtService.

#### Paso 1.3 — Cambiar ruta reportes.cuentas-por-cobrar

- **Objetivo:** Apuntar al nuevo componente.
- **Archivos:** `routes/web.php`, `reporte-index-live.blade.php`, sidebar.
- **Tareas:**
  1. Reemplazar binding Livewire en ruta reportes.
  2. Mantener `pos.cuentas-por-cobrar` → `CustomerDebts` sin cambios.
- **Criterios de aceptacion:**
  - Rutas operativas y analiticas usan componentes distintos.
- **Dependencias:** Pasos 1.1, 1.2.

#### Paso 1.4 — Tests y permisos

- **Objetivo:** Verificar frontera permisos.
- **Tareas:**
  1. Feature test: rol reportes puede ver listado, no cobrar.
  2. Feature test: rol POS puede cobrar en ruta pos.*
  3. Actualizar `CustomerDebtsLivewireTest` si aplica.
- **Criterios de aceptacion:**
  - Suite verde; INC-03 cerrado.

---

### Fase 2 — Servicios analiticos por dominio

**Objetivo de fase:** Extraer de ReporteModuloService gradualmente.

#### Paso 2.1 — Definir interfaces y contratos

- **Objetivo:** Estructura namespace `App\Services\Analytics\`.
- **Servicios:**
  - `SalesAnalyticsService` — ventas, items, metodos pago
  - `ClientAnalyticsService` — clientes activos/inactivos, membresia/clases
  - `FinanceAnalyticsService` — ingresos, pagos, cuentas por cobrar
  - `CajaAnalyticsService` — aperturas, cierres, arqueos
- **Tareas:**
  1. Documentar metodos publicos por servicio.
  2. `ReporteModuloService` delega inicialmente (facade temporal).
- **Criterios de aceptacion:**
  - Servicios creados con tests smoke.

#### Paso 2.2 — Migrar reporte ventas

- **Archivos:** `ReporteVentasLive.php`, `SalesAnalyticsService`, `ReporteModuloController`.
- **Tareas:**
  1. Mover queries ventas a SalesAnalyticsService.
  2. Export PDF/Excel usa mismo servicio.
- **Criterios de aceptacion:**
  - Resultados identicos pre/post migracion en test snapshot.

#### Paso 2.3 — Migrar reporte clientes y membresia-clases

- **Objetivo:** Resolver INC-09 en reportes.
- **Archivos:** `ReporteClientesLive.php`, `ReporteClientesMembresiaClasesLive.php`, `ClientAnalyticsService`.
- **Tareas:**
  1. Separar filas matricula vs legacy membresia.
  2. Columna `tipo_plan` en export.
  3. Filtro "solo matriculas" / "incluir legacy".
- **Criterios de aceptacion:**
  - Export indica origen legacy.

#### Paso 2.4 — Migrar reporte financiero y cajas

- **Servicios:** `FinanceAnalyticsService`, `CajaAnalyticsService`.
- **Tareas:**
  1. Alinear definiciones ingreso vs pago vs movimiento caja.
  2. Documentar formulas en comentarios servicio.
- **Criterios de aceptacion:**
  - Totales financiero = suma ventas + pagos matricula ± ajustes (documentado).

#### Paso 2.5 — Migrar reporte gimnasio (ejecutivo)

- **Objetivo:** Dashboard ejecutivo compone otros servicios.
- **Tareas:**
  1. `ReporteGimnasioLive` solo compone KPIs de servicios analytics.
  2. Sin queries propias.
- **Criterios de aceptacion:**
  - KPIs trazables a servicios fuente.

#### Paso 2.6 — Reducir ReporteModuloService

- **Objetivo:** Facade delgado o eliminacion.
- **Tareas:**
  1. Deprecar metodos migrados.
  2. Target final < 200 LOC o eliminar.
- **Criterios de aceptacion:**
  - Cada reporte Livewire inyecta servicio analytics directo.

---

### Fase 3 — Navegacion y exportaciones

**Objetivo de fase:** Descubrimiento completo y exports robustos.

#### Paso 3.1 — Alinear sidebar Analitica

- **Objetivo:** Resolver INC-07.
- **Opciones:**
  - **A)** Sidebar solo "Centro de reportes" (eliminar 4 items duplicados).
  - **B)** Sidebar lista completa de 11 reportes agrupados.
- **Recomendacion:** Opcion A + centro reportes mejorado con categorias.
- **Archivos:** `sidebar.blade.php`, `reporte-index-live.blade.php`.
- **Tareas:**
  1. Agrupar cards: Ejecutivo, Finanzas, Clientes, Operaciones, Inventario.
  2. Reducir sidebar a Centro + atajos finanzas (opcional).
- **Criterios de aceptacion:**
  - Usuario encuentra los 11 reportes sin URL manual.

#### Paso 3.2 — Exportaciones async unificadas

- **Objetivo:** `ReporteExportDownloadController` para todos los modulos.
- **Tareas:**
  1. Verificar cola/jobs para exports pesados.
  2. Feedback UI progreso en Livewire.
- **Criterios de aceptacion:**
  - Export >10k filas no timeout HTTP.

#### Paso 3.3 — Filtros sucursal consistentes

- **Objetivo:** Todo reporte respeta `SucursalContext`.
- **Tareas:**
  1. Auditar reportes sin filtro sucursal.
  2. Super admin: selector sucursal en filtros reporte.
- **Criterios de aceptacion:**
  - Test multi-sucursal en al menos 3 reportes clave.

#### Paso 3.4 — Reportes nutricionales legacy

- **Objetivo:** `ReporteService` convivencia o migracion.
- **Tareas:**
  1. Inventariar rutas evaluacion PDF firmadas.
  2. Decidir: mantener como sub-sistema wellness o migrar a Analytics wellness.
  3. Documentar en plan Bienestar si aplica.
- **Criterios de aceptacion:**
  - Rutas legacy documentadas; sin duplicacion nueva.

#### Paso 3.5 — Reportes bienestar (progreso/cumplimiento)

- **Nota:** Rutas bajo `ejercicios-rutinas.*`, no `reportes.*`.
- **Tareas:**
  1. Enlace desde centro reportes hacia progreso/cumplimiento.
  2. Categoria "Bienestar" en hub reportes.
- **Criterios de aceptacion:**
  - Descubrimiento desde centro reportes.

---

## 4. Riesgos y mitigaciones

| Riesgo | Mitigacion |
| --- | --- |
| Totales reporte ≠ operacion | Tests paridad con DailyOperationsDebtService |
| Regresion exports PDF | Snapshot tests por reporte |
| ReporteModuloService monolitico | Migracion incremental por dominio |

---

## 5. Criterios de cierre del modulo

- [ ] `ReporteCuentasPorCobrarLive` reemplaza CustomerDebts en reportes
- [ ] Cuatro servicios Analytics implementados y en uso
- [ ] ReporteModuloService reducido o eliminado
- [ ] Sidebar/hub alineados (INC-07)
- [ ] Legacy etiquetado en reportes clientes
- [ ] Filtros sucursal en todos los reportes modulares

---

## 6. Dependencias con otros modulos

| Modulo | Dependencia |
| --- | --- |
| Operaciones | Paridad saldos; CustomerDebts solo POS |
| Clientes | Origen legacy en analytics clientes |
| Recursos | Stock bajo, ingresos alquiler |
| Comercial | Conversion, cupones en ventas (futuro) |

---

## 7. Avance de implementación (2026-06-24)

### Completado (Fase 1)
- `ReporteCuentasPorCobrarLive` + `FinanceAnalyticsService`
- Rutas operativas vs analíticas separadas

### Completado (Fase 2 parcial)
- `SalesAnalyticsService`, `ClientAnalyticsService`, `CajaAnalyticsService` (delegan a `ReporteModuloService`)

### Completado (Fase 3 parcial)
- Sidebar Analítica: solo Centro de reportes (INC-07)

### Pendiente
- Migrar reportes Livewire a servicios directos
- Reducir `ReporteModuloService`
- Legacy en export clientes
