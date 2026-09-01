# Plan de mejora: Transversal

> **Referencia:** [module-consistency-matrix.md](../architecture/module-consistency-matrix.md)  
> **Alcance:** Iniciativas que cruzan todos los modulos  
> **Ultima actualizacion:** 2026-06-24

---

## 1. Proposito

Este documento coordina trabajo que no pertenece a un solo modulo pero es prerequisito o desbloqueador de varios planes:

- Agregadores compartidos y capas de deuda (resumen / transaccion / analitica)
- Legacy `cliente_membresias` vs `cliente_matriculas`
- Multi-sucursal (`SucursalContext`)
- Nomenclatura y navegacion global
- Criterios de calidad arquitectonica

---

## 2. Capas de deuda alineadas

### Modelo objetivo

```text
┌─────────────────────────────────────────────────────────┐
│  UI (Livewire) — solo orquestacion                      │
├─────────────────────────────────────────────────────────┤
│  Resumen operativo    │ DailyOperationsDebtService      │
│  Transaccion cobro    │ ClientDebtService, VentaService │
│  Analitica            │ FinanceAnalyticsService, etc.   │
├─────────────────────────────────────────────────────────┤
│  Fuentes de verdad      │ pagos, client_debts, ventas…  │
└─────────────────────────────────────────────────────────┘
```

### Paso T.1 — Contrato numerico unificado de saldo

- **Objetivo:** Una definicion documentada de "deuda total", "vencida", "por vencer".
- **Entregable:** `docs/architecture/debt-definitions.md`.
- **Contenido:**
  - Formulas por tipo: cuota matricula, venta credito, legacy membresia
  - Que incluye / excluye cada capa
  - Redondeo y moneda
- **Consumidores:** Operaciones, Clientes, Analitica.
- **Criterios de aceptacion:**
  - Test cross-modulo: mismo cliente, mismos numeros en checking, ficha, reporte.

### Paso T.2 — Tests de paridad cross-modulo

- **Archivos:** `tests/Feature/Consistency/DebtParityTest.php` (nuevo).
- **Escenarios:**
  1. Cliente con cuota vencida + venta credito
  2. Cliente solo legacy membresia
  3. Cliente importado con deuda
- **Criterios de aceptacion:**
  - CI falla si divergen totales.

### Paso T.3 — Prohibicion arquitectonica en code review

- **Regla:** Nuevo codigo no calcula saldo inline en Livewire.
- **Accion:** Agregar nota en `docs/architecture/module-consistency-matrix.md` y opcional regla Cursor.

---

## 3. Legacy comercial (cliente_membresias)

### Paso T.4 — Politica legacy documentada

- **Entregable:** `docs/architecture/legacy-membresias-policy.md`.
- **Reglas:**
  - Lectura: permitida en ficha, reportes, cobranza controlada
  - Escritura nuevas altas: prohibida desde UI; solo matriculas
  - Cobranza: solo via servicios autorizados con flag legacy
  - Sunset: criterio para migracion masiva a matriculas

### Paso T.5 — Servicio LegacyMembresiaReadService

- **Objetivo:** Unico punto lectura legacy.
- **Archivos nuevos:** `app/Services/Legacy/LegacyMembresiaReadService.php`.
- **Tareas:**
  1. Metodos list, summary, eligible-for-payment.
  2. Migrar consultas directas desde ficha, bienestar, reportes.
- **Criterios de aceptacion:**
  - Cero queries `ClienteMembresia` en Livewire tras migracion.

### Paso T.6 — Script migracion membresia → matricula (opcional)

- **Objetivo:** Herramienta one-off super admin.
- **Tareas:**
  1. Artisan command dry-run + execute.
  2. Mapeo estado, fechas, pagos pendientes.
- **Criterios de aceptacion:**
  - Dry-run reporta impacto sin escribir.

---

## 4. Multi-sucursal

### Paso T.7 — Auditar scope sucursal en servicios

- **Objetivo:** Ningun agregador ignora sucursal activa.
- **Tareas:**
  1. Checklist servicios en `app/Services/`.
  2. Usar trait `BelongsToSucursal` o scope global consistente.
  3. Super admin: comportamiento explicito (todas vs activa).
- **Criterios de aceptacion:**
  - Informe audit servicios criticos limpio.

### Paso T.8 — Sucursal en exports y reportes

- **Dependencias:** Planes Analitica, Plataforma.
- **Criterios de aceptacion:**
  - PDF/Excel incluyen nombre sucursal en header.

---

## 5. Nomenclatura y navegacion global

### Paso T.9 — Glosario dominio UI

- **Entregable:** `docs/architecture/domain-glossary.md`.
- **Terminos minimos:**

| Termino UI canonico | Uso |
| --- | --- |
| Operaciones | Grupo sidebar (ex "Operacion diaria" en docs) |
| Clientes | Ficha + listado + catalogos planes |
| Bienestar | Salud + entrenamiento |
| Comercial | CRM + promociones |
| Analitica | Reportes (no "Reportes" en navbar mobile si difiere) |

### Paso T.10 — Sincronizar breadcrumbs

- **Archivo:** `resources/views/components/breadcrumbs.blade.php`.
- **Tareas:** Aplicar glosario; rutas faltantes.
- **Criterios de aceptacion:** INC-01 cerrado.

### Paso T.11 — Hub vs sidebar strategy

- **Decision documentada:**
  - Modulos con muchas rutas → hub central (Reportes, Imports)
  - Modulos operativos → sidebar directo
- **Aplicar a:** Analitica, CRM reportes, Importaciones.

---

## 6. Calidad de componentes Livewire

### Paso T.12 — Limite LOC y complejidad

- **Regla:** Componente > 400 LOC requiere plan de desacople antes de nuevas features.
- **Watchlist actual:**
  - `POSLive` (~1.202)
  - `ClientePerfilLive` (~1.153)
  - `GestionNutricionalUnificadoLive` (~875)

### Paso T.13 — Patron shell + traits + servicios

- **Plantilla recomendada:**
  ```text
  ModuloShellLive (tabs, layout)
    ├── Concerns/ManagesXTab
    ├── Services/XService (negocio)
    └── Child Livewire opcional (pickers)
  ```
- **Referencia exitosa parcial:** `ManagesClienteCrudAndPhoto`, `ClienteLive`.

---

## 7. Registro inconsistencias — seguimiento

| ID | Responsable plan | Paso cierre |
| --- | --- | --- |
| INC-01 | Transversal T.9, T.10 | Glosario + breadcrumbs |
| INC-02 | Operaciones 2.1 | checking.ver |
| INC-03 | Analitica 1.1–1.3 | ReporteCuentasPorCobrarLive |
| INC-04 | Operaciones 1.5 + Analitica 1.3 | Componentes separados |
| INC-05 | Administracion 1.1–1.2 | ADR BioTime |
| INC-06 | Administracion 1.3 | Sidebar backups |
| INC-07 | Analitica 3.1 | Hub/sidebar reportes |
| INC-08 | Bienestar 1.5 | PlanFreezeService |
| INC-09 | Transversal T.4–T.5 + Clientes 3.4 | Legacy policy |
| INC-10 | Comercial 1.1–1.2 | Matriz permisos CRM |

---

## 8. Cronograma sugerido por oleadas

### Oleada 1 (impacto inmediato — 2-4 sprints)
1. Analitica Fase 1 (CustomerDebts)
2. Operaciones Fase 1 pasos 1.5, 1.6
3. Transversal T.1, T.2
4. Administracion Fase 1 (BioTime, backups sidebar)

### Oleada 2 (ficha y POS — 3-5 sprints)
1. Clientes Fase 1 agregadores
2. Operaciones desacople POSLive
3. Recursos Fase 1 RentalService

### Oleada 3 (dominios secundarios — 3-5 sprints)
1. Bienestar Fase 1–2
2. Comercial Fase 1–2
3. Analitica Fase 2 servicios analytics

### Oleada 4 (madurez — continuo)
1. Plataforma trazabilidad origen
2. Administracion auditoria
3. Transversal legacy sunset

---

## 9. Criterios de exito globales

Referencia desde matriz maestra:

- [ ] Mismo cliente → mismo estado comercial, bienestar y deuda en cualquier modulo
- [ ] Ningun reporte usa componente transaccional operativo
- [ ] Componentes principales < 400 LOC o descompuestos
- [ ] Sidebar, breadcrumbs y docs nomenclatura unificada
- [ ] Legacy con indicador origen; no fuente de nuevas altas
- [ ] Tres capas deuda alineadas y testeadas
- [ ] INC-01 a INC-10 cerrados o explicitamente diferidos con fecha

---

## 10. Indice de planes por modulo

| Plan | Archivo |
| --- | --- |
| Operaciones | [00-operaciones-plan-mejora.md](./00-operaciones-plan-mejora.md) |
| Clientes | [01-clientes-plan-mejora.md](./01-clientes-plan-mejora.md) |
| Bienestar | [02-bienestar-plan-mejora.md](./02-bienestar-plan-mejora.md) |
| Comercial | [03-comercial-plan-mejora.md](./03-comercial-plan-mejora.md) |
| Recursos | [04-recursos-plan-mejora.md](./04-recursos-plan-mejora.md) |
| Analitica | [05-analitica-plan-mejora.md](./05-analitica-plan-mejora.md) |
| Administracion | [06-administracion-plan-mejora.md](./06-administracion-plan-mejora.md) |
| Plataforma | [07-plataforma-plan-mejora.md](./07-plataforma-plan-mejora.md) |

---

## 11. Avance de implementación (refresco 2026-08-27, snapshot original 2026-06-24)

### Completado
- `docs/architecture/debt-definitions.md`
- `docs/architecture/legacy-membresias-policy.md`
- `docs/architecture/domain-glossary.md`
- `LegacyMembresiaReadService`
- Auditoría multi-sucursal (T.7/T.8): sustancialmente implementada — ver `docs/architecture/sucursal-scope-audit.md` (allowlist de `withoutGlobalScope`, `BelongsToSucursal` fail-closed, `SucursalScopedRouteBinding`, tests de aislamiento y de reportes consolidados para super admin).
- INC-01 (nomenclatura Operaciones), INC-02 (`checking.ver`), INC-03/04 (`CustomerDebts` vs `ReporteCuentasPorCobrarLive`), INC-06 (backups sidebar), INC-07 (sidebar/centro reportes) — cerrados en código.
- INC-05 (BioTime) — resuelto de forma distinta a la decidida en el ADR original: quedó como grupo de sidebar propio, no dentro de Operaciones. ADR actualizado.
- INC-08 (congelamiento de planes) — parcial: extraído a `PlanFreezeService` (`app/Services/Cliente/`), aún no en una capa "comercial" formal.

### Pendiente
- `tests/Feature/Consistency/DebtParityTest.php` — la carpeta `tests/Feature/Consistency/` existe pero sigue vacía; el test nunca se escribió.
- Migración sunset legacy masiva.
- Regla Cursor anti-saldo en Livewire.
- INC-09 (legacy `cliente_membresias` sin indicador de origen consistente en UI) e INC-10 (permisos CRM fragmentados) — vigentes.
- **Nuevo hallazgo (INC-11):** `AuditLog` está completamente modelado pero no se usa (`0` llamadas `AuditLog::create()` en `app/`). Ninguna capa transversal de auditoría de cambios críticos está activa hoy.
- `POSLive` (~1.142 LOC) y `GestionNutricionalUnificadoLive` (~876 LOC) — la regla de "componente > 400 LOC requiere plan de desacople" (paso T.12) sigue sin aplicarse en la práctica para estos dos.

### Nuevo (2026-08-26): iniciativa transversal de CSS/colores no cubierta anteriormente

[`99-css-colores-plan-mejora.md`](./99-css-colores-plan-mejora.md) es un plan transversal adicional, fuera del alcance original de este documento: pipeline de build CSS, eliminación completa de dark mode (UI, guardas server-side y barrido de clases `dark:`), consolidación de los 4 componentes de personalización de tema en uno, y un componente de badge de estado compartido fuera de CRM (piloto: calendario de citas, que ya tenía drift de color confirmado). Se referencia aquí porque toca convenciones de nomenclatura/consistencia visual similares a las de este plan, aunque su ejecución es independiente.
