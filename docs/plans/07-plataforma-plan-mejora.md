# Plan de mejora: Plataforma (Super administracion)

> **Referencia:** [module-consistency-matrix.md](../architecture/module-consistency-matrix.md)  
> **Prioridad:** Media (orden global #8)  
> **Ultima actualizacion:** 2026-06-24

---

## 1. Contexto y diagnostico

### Alcance funcional
Configuracion multi-empresa/sucursal, migracion masiva desde Excel legacy, historial importaciones, plantillas descargables, exportacion errores.

### Estado actual

| Elemento | Situacion |
| --- | --- |
| Servicios Imports | 27 archivos; modulo maduro |
| Acceso | Rol `super_administrador` exclusivo |
| Sidebar | Solo `importaciones.index`; historial y clientes-agrupados ocultos |
| Impacto | Datos importados alimentan clientes, matriculas, deudas |
| Origen datos | Sin etiquetado sistematico importado vs nativo |

### Riesgo principal
Coexistencia legacy + importado + nativo produce calculos divergentes en operacion y reportes.

### Fuente de verdad objetivo
`empresas`, `sucursales`, `gym_settings`, `imports`, `import_rows`; datos de negocio en tablas core con metadata origen

---

## 2. Objetivos

1. Etiquetar origen de registros (nativo, importado, legacy).
2. Checklist post-importacion operativo.
3. Documentar mapeo legacy → modelo nuevo.
4. Mejorar navegacion imports en sidebar.
5. Garantizar imports respetan sucursal y no corrompen fuentes de verdad.

---

## 3. Plan por fases

### Fase 1 — Trazabilidad de origen

**Objetivo de fase:** Sistema distingue de donde vino cada registro.

#### Paso 1.1 — Definir enum DataOrigin

- **Objetivo:** Valores estandar de origen.
- **Archivos nuevos:** `app/Enums/DataOrigin.php`.
- **Valores sugeridos:** `native`, `import`, `legacy`, `import_legacy`.
- **Criterios de aceptacion:**
  - Enum usado en servicios, no strings magicos.

#### Paso 1.2 — Columnas metadata en entidades core (donde falten)

- **Objetivo:** Persistir origen sin redisenar todo el schema.
- **Tablas candidatas:** `clientes`/equivalente, `cliente_matriculas`, `client_debts`, `enrollment_installments`, `pagos`.
- **Columnas sugeridas:**
  - `data_origin` (string/enum)
  - `import_id` (nullable FK imports)
  - `import_row_id` (nullable)
- **Tareas:**
  1. Migraciones incrementales nullable (no breaking).
  2. Default `native` para registros existentes.
  3. Backfill importados via `import_rows` si correlacion posible.
- **Criterios de aceptacion:**
  - Nuevos imports escriben metadata origen.

#### Paso 1.3 — ImportManagerService escribe metadata

- **Objetivo:** Cada fila importada trazable.
- **Archivos:** `ImportManagerService.php`, processors en `Services/Imports/`.
- **Tareas:**
  1. Pasar `import_id` a servicios creacion entidad.
  2. Al crear cliente/matricula/deuda: set origin + FK.
- **Criterios de aceptacion:**
  - Desde `Imports/Show` se navega a entidades creadas.

#### Paso 1.4 — Agregadores consumen origen

- **Objetivo:** UI y reportes muestran badge origen.
- **Dependencias:** [01-clientes-plan-mejora.md](./01-clientes-plan-mejora.md), [05-analitica-plan-mejora.md](./05-analitica-plan-mejora.md).
- **Tareas:**
  1. DTOs perfil cliente incluyen `data_origin`.
  2. Filtros reporte "excluir legacy importado" opcional.
- **Criterios de aceptacion:**
  - Ficha cliente muestra origen importacion si aplica.

---

### Fase 2 — Proceso post-importacion

**Objetivo de fase:** Operacion segura despues de carga masiva.

#### Paso 2.1 — Checklist post-importacion en Dashboard

- **Objetivo:** GUI guia validacion post-carga.
- **Archivos:** `Imports/Dashboard.php`, vista Blade.
- **Items checklist sugeridos:**
  - [ ] Revisar filas con error (`importaciones/{id}`)
  - [ ] Validar conteo clientes vs Excel
  - [ ] Reconciliar deudas totales vs legacy
  - [ ] Verificar matriculas activas muestra
  - [ ] Ejecutar reporte clientes filtro importados
  - [ ] Confirmar sucursal destino correcta
- **Criterios de aceptacion:**
  - Checklist visible tras import completado; estado persistido opcional.

#### Paso 2.2 — Reporte reconciliacion import

- **Objetivo:** Comparar totales import vs BD post-proceso.
- **Archivos nuevos:** `app/Services/Imports/ImportReconciliationService.php`.
- **Metricas:** clientes creados, duplicados omitidos, deuda total, cuotas creadas, errores.
- **Criterios de aceptacion:**
  - Show import muestra panel reconciliacion.

#### Paso 2.3 — Idempotencia y re-ejecucion segura

- **Objetivo:** Re-importar no duplica clientes.
- **Tareas:**
  1. Claves naturales: documento, codigo socio, email.
  2. Modo upsert vs skip documentado por tipo import.
  3. Log decisiones en `import_rows`.
- **Criterios de aceptacion:**
  - Test re-import mismo archivo no duplica.

#### Paso 2.4 — Rollback acotado (opcional avanzado)

- **Objetivo:** Revertir import por `import_id` donde sea seguro.
- **Tareas:**
  1. Soft-delete o flag `reverted_at` en entidades creadas por import.
  2. Solo super admin; confirmacion destructiva.
- **Criterios de aceptacion:**
  - Documentado que pagos/ventas post-import no se revierten automaticamente.

---

### Fase 3 — Documentacion y navegacion

**Objetivo de fase:** Operabilidad y conocimiento del mapeo legacy.

#### Paso 3.1 — Documento mapeo legacy → modelo nuevo

- **Archivo nuevo:** `docs/imports/legacy-mapping.md`.
- **Contenido por entidad:**
  - Columna Excel → campo modelo
  - Transformaciones (fechas, montos, estados)
  - Reglas default cuando celda vacia
  - Equivalencia `cliente_membresias` → `cliente_matriculas`
- **Criterios de aceptacion:**
  - Documento revisado con processors reales en codigo.

#### Paso 3.2 — Ampliar sidebar Super administracion

- **Archivos:** `sidebar.blade.php`.
- **Items sugeridos:**
  - Carga inicial Excel (`importaciones.index`) — existente
  - Historial importaciones (`importaciones.historial`)
  - Clientes agrupados (`importaciones.clientes-agrupados`) si flujo activo
  - Empresa y sucursales — existente
  - Respaldos BD — ver [06-administracion-plan-mejora.md](./06-administracion-plan-mejora.md)
- **Criterios de aceptacion:**
  - Flujos import accesibles sin URL.

#### Paso 3.3 — CompanyBranches cohesion

- **Archivos:** `Settings/CompanyBranches/Index.php`, `SucursalContext`.
- **Tareas:**
  1. Validar cambio sucursal no rompe contexto import.
  2. Import siempre asocia sucursal activa al procesar.
  3. Documentar multi-sucursal en guia import.
- **Criterios de aceptacion:**
  - Import rechaza procesar sin sucursal activa seleccionada.

#### Paso 3.4 — Plantillas Excel versionadas

- **Archivos:** `ImportacionPlantillaController`, storage plantillas.
- **Tareas:**
  1. Version en nombre plantilla (`clientes_v2.xlsx`).
  2. Dashboard muestra version compatible por tipo.
  3. Changelog plantillas en `docs/imports/`.
- **Criterios de aceptacion:**
  - Usuario descarga plantilla correcta para version app.

#### Paso 3.5 — Errores import exportables

- **Estado:** Ruta `importaciones.errores.excel` existe.
- **Tareas:**
  1. Verificar formato util para correccion re-upload.
  2. Enlace prominente desde Show import.
- **Criterios de aceptacion:**
  - Excel errores incluye numero fila + mensaje + datos origen.

---

## 4. Riesgos y mitigaciones

| Riesgo | Mitigacion |
| --- | --- |
| Migraciones metadata en tablas grandes | Columnas nullable; backfill batch |
| Import corrupto en produccion | Checklist + reconciliacion + backup previo |
| Rollback incompleto | Documentar limites; backup obligatorio |

---

## 5. Criterios de cierre del modulo

- [ ] DataOrigin en entidades core nuevas/importadas
- [ ] Checklist post-importacion en dashboard
- [ ] ImportReconciliationService operativo
- [ ] legacy-mapping.md publicado
- [ ] Sidebar Super admin completo
- [ ] Idempotencia imports verificada

---

## 6. Dependencias con otros modulos

| Modulo | Dependencia |
| --- | --- |
| Clientes | Origen en ficha y agregadores |
| Operaciones | Deudas importadas vs nativas |
| Analitica | Filtros origen en reportes |
| Administracion | Backups antes import masivo |

---

## 7. Avance de implementación (2026-06-24)

### Completado (Fase 1 parcial)
- `DataOrigin` enum
- `ImportReconciliationService`
- `docs/imports/legacy-mapping.md`
- Sidebar: historial importaciones + respaldos

### Pendiente
- Migraciones `data_origin` en tablas core
- Checklist post-import en Dashboard
- Idempotencia verificada en tests
