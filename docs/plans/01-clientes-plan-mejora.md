# Plan de mejora: Clientes

> **Referencia:** [module-consistency-matrix.md](../architecture/module-consistency-matrix.md)  
> **Prioridad:** Alta (orden global #2)  
> **Inconsistencias vinculadas:** INC-08 (parcial), INC-09 (vigente)  
> **Ultima actualizacion:** 2026-08-27 (refresco de 2026-06-24) · **2026-08-27 (2):** agregadas Fases 4-8 sobre integridad referencial, seguridad y rendimiento tras revisión profunda de código — ver abajo. Corrección de cifra: `ClientePerfilLive` está en **1159 LOC** (no ~934 como se estimó antes; el componente creció desde la última medición).

---

## 1. Contexto y diagnostico

### Alcance funcional
Listado CRUD, ficha 360, catalogo de membresias/clases, matriculas, cuotas e historial comercial del cliente.

### Estado actual

| Elemento | Situacion |
| --- | --- |
| `ClienteLive` | ~63 LOC; bien acotado |
| `ClientePerfilLive` | ~1.153 LOC, ~46 metodos; super-modulo |
| `ManagesClienteCrudAndPhoto` | ~497 LOC; extraccion parcial previa |
| Agregadores planificados | 0/3 implementados |
| `DailyOperationsDebtService` | Ya consumido en ficha |
| Sidebar | Perfil y listado ya separados |

### Riesgo principal
La ficha 360 concentra comercial, cobranza, bienestar, reservas, fidelizacion y checking; cada tab puede recalcular estados distintos.

### Fuente de verdad objetivo
`clientes` + `cliente_matriculas` + `enrollment_installments` (comercial activo); `cliente_membresias` solo lectura legacy

---

## 2. Objetivos

1. Convertir `ClientePerfilLive` en shell delgado con tabs delegadas.
2. Implementar agregadores de contexto por subdominio.
3. Reducir acciones transaccionales pesadas a atajos hacia Operaciones.
4. Una sola carga de contexto por cliente al abrir ficha.
5. Marcar origen legacy en datos comerciales.

---

## 3. Plan por fases

### Fase 1 — Agregadores de contexto

**Objetivo de fase:** Centralizar lectura de datos de ficha en servicios dedicados.

#### Paso 1.1 — Definir DTO `ClienteProfileContext`

- **Objetivo:** Contrato unico de datos para la ficha.
- **Archivos nuevos:** `app/Data/Cliente/ClienteProfileContext.php` (o array tipado en PHP 8.2+).
- **Estructura sugerida:**
  ```text
  ClienteProfileContext
  ├── cliente (datos base)
  ├── commercial (ClienteCommercialSummary)
  ├── wellness (ClienteWellnessSummary)
  ├── crm (ClienteCrmSummary)
  ├── operations (deuda, asistencia hoy)
  └── meta (origen legacy flags, sucursal)
  ```
- **Criterios de aceptacion:**
  - DTO documentado con propiedades y tipos.
  - Sin dependencias Livewire.

#### Paso 1.2 — Implementar `ClienteProfileContextService`

- **Objetivo:** Orquestador que compone el DTO en una sola llamada.
- **Archivos nuevos:** `app/Services/Cliente/ClienteProfileContextService.php`.
- **Tareas:**
  1. Metodo `build(int $clienteId): ClienteProfileContext`.
  2. Lazy-load opcional por seccion si performance lo requiere.
  3. Cache request-scoped para evitar N+1 entre tabs.
- **Criterios de aceptacion:**
  - Una llamada carga datos minimos para header de ficha.
  - Test de integracion con cliente fixture completo.

#### Paso 1.3 — Implementar `ClienteCommercialProfileService`

- **Objetivo:** Estado comercial unificado (matriculas, cuotas, legacy membresias).
- **Archivos nuevos:** `app/Services/Cliente/ClienteCommercialProfileService.php`.
- **Tareas:**
  1. Metodo `getSummary(Cliente $cliente): ClienteCommercialSummary`.
  2. Incluir: matriculas activas, cuotas pendientes, planes legacy solo lectura.
  3. Flag `is_legacy` por registro de `cliente_membresias`.
  4. Reutilizar `ClienteMatriculaService`, `EnrollmentInstallmentService`.
- **Criterios de aceptacion:**
  - Mismos totales que `DailyOperationsDebtService` para deuda comercial.
  - Legacy claramente etiquetado en DTO.
- **Dependencias:** Paso 1.1.

#### Paso 1.4 — Implementar `ClienteWellnessProfileService`

- **Objetivo:** Resumen bienestar sin logica de congelamiento comercial.
- **Archivos nuevos:** `app/Services/Cliente/ClienteWellnessProfileService.php`.
- **Tareas:**
  1. Extraer de `ClientWellnessService` solo lectura: citas proximas, evaluaciones, rutinas activas.
  2. **No** incluir congelamiento (mover a servicio comercial compartido en Fase 2 Bienestar).
  3. Delegar detalle profundo a servicios existentes.
- **Criterios de aceptacion:**
  - Servicio < 200 LOC inicial.
  - Sin referencias a `ClienteMembresia` para escritura.
- **Dependencias:** Paso 1.1; coordinacion [02-bienestar-plan-mejora.md](./02-bienestar-plan-mejora.md).

#### Paso 1.5 — Implementar `ClienteCrmProfileService`

- **Objetivo:** Tags, tareas abiertas, ultima actividad CRM.
- **Archivos nuevos:** `app/Services/Cliente/ClienteCrmProfileService.php`.
- **Tareas:**
  1. Agregar tags, tasks pendientes, ultima actividad, lead vinculado si existe.
  2. Reutilizar `LeadService`, `CrmTaskService`, `CrmActivityService`.
- **Criterios de aceptacion:**
  - Ficha muestra CRM sin queries directas en Livewire.
- **Dependencias:** Paso 1.1.

#### Paso 1.6 — Integrar agregadores en ClientePerfilLive (lectura)

- **Objetivo:** Reemplazar consultas directas en `mount`/`refresh` por `ClienteProfileContextService`.
- **Archivos:** `ClientePerfilLive.php`.
- **Tareas:**
  1. Inyectar `ClienteProfileContextService`.
  2. Reemplazar bloques de carga en `refreshSelectedClienteContext` y `refreshPerfilData`.
  3. Mantener acciones transaccionales sin mover aun (Fase 2).
- **Criterios de aceptacion:**
  - Reduccion medible de queries en render (debugbar o test).
  - LOC de metodos de refresh reducido >50%.

---

### Fase 2 — Ficha 360 como shell

**Objetivo de fase:** Separar tabs en componentes o traits por dominio.

#### Paso 2.1 — Mapa de tabs y responsabilidades

- **Objetivo:** Definir que tab es solo lectura vs transaccional.
- **Tabs tipicas:** General, Comercial, Asistencias, Deudas, Bienestar, CRM, Fidelizacion, Historial.
- **Tareas:**
  1. Clasificar cada tab: **R** (resumen), **T** (transaccional), **L** (enlace externo).
  2. Objetivo: maximizar **R** y **L**; minimizar **T** en ficha.
- **Criterios de aceptacion:**
  - Tabla tabs documentada en plan o comentario de componente.

#### Paso 2.2 — Extraer `ManagesClienteCommercialTab`

- **Objetivo:** Trait o Livewire anidado para tab comercial.
- **Archivos nuevos:** `app/Livewire/Clientes/Concerns/ManagesClienteCommercialTab.php`.
- **Tareas:**
  1. Mover modales de cobro matricula, cuotas, plan cuotas al trait.
  2. Tab consume `ClienteCommercialProfileService` para display.
  3. Acciones de cobro delegan a servicios existentes sin logica duplicada.
- **Criterios de aceptacion:**
  - Tab comercial funcional con trait aislado.
- **Dependencias:** Fase 1 completa.

#### Paso 2.3 — Extraer tabs Bienestar, CRM y Fidelizacion

- **Objetivo:** Misma estrategia que 2.2 para otros dominios.
- **Archivos:** traits `ManagesClienteWellnessTab`, `ManagesClienteCrmTab`, `ManagesClienteFidelizacionTab`.
- **Criterios de aceptacion:**
  - `ClientePerfilLive` < 600 LOC al cerrar paso.
- **Dependencias:** Paso 2.2.

#### Paso 2.4 — Convertir acciones pesadas en atajos

- **Objetivo:** Cobranza compleja redirige a Operaciones.
- **Tareas:**
  1. Boton "Cobrar deuda" → `pos.cuentas-por-cobrar?cliente_id=X`.
  2. Cobro matricula rapido puede permanecer como atajo contextual.
  3. Checking ingreso/salida: mantener atajo o enlace a `checking.index` con cliente preseleccionado.
- **Criterios de aceptacion:**
  - Flujos documentados en UI (tooltip o texto ayuda).
- **Dependencias:** [00-operaciones-plan-mejora.md](./00-operaciones-plan-mejora.md).

#### Paso 2.5 — Lazy load de tabs

- **Objetivo:** No cargar datos de tab inactiva.
- **Tareas:**
  1. Al cambiar tab (`setTab`), cargar seccion via metodo dedicado del agregador.
  2. Livewire `wire:init` o evento tab-changed.
- **Criterios de aceptacion:**
  - Apertura ficha carga solo tab activa + header context.

---

### Fase 3 — Navegacion y catalogo comercial

**Objetivo de fase:** Separacion clara ficha vs catalogos.

#### Paso 3.1 — Agrupar catalogo comercial en sidebar (opcional)

- **Objetivo:** Sub-grupo visual Membresias / Matriculas / Clases.
- **Archivos:** `sidebar.blade.php`.
- **Tareas:**
  1. Evaluar sub-heading o separador Flux dentro de grupo Clientes.
  2. No cambiar rutas; solo UX.
- **Criterios de aceptacion:**
  - Usuario distingue ficha vs catalogo sin confusion.

#### Paso 3.2 — Mantener ClienteLive minimalista

- **Objetivo:** Listado no crece en scope.
- **Tareas:**
  1. Prohibir nuevas features en `ClienteLive` excepto busqueda/filtros CRUD.
  2. Alta cliente redirige a ficha o mantiene modal simple.
- **Criterios de aceptacion:**
  - `ClienteLive` permanece < 150 LOC.

#### Paso 3.3 — Cuotas como rutas contextuales documentadas

- **Objetivo:** `clientes.cuotas`, `cuotas.pagar` accesibles desde ficha/comercial.
- **Tareas:**
  1. Enlaces desde tab comercial con breadcrumbs claros.
  2. No agregar al sidebar principal.
- **Criterios de aceptacion:**
  - Navegacion ficha → cuotas → volver funcional.

#### Paso 3.4 — Legacy cliente_membresias en UI

- **Objetivo:** Resolver INC-09 en ficha.
- **Tareas:**
  1. Badge "Legacy" en filas de membresia antigua.
  2. Deshabilitar alta nueva de `cliente_membresias` desde UI (solo matriculas).
  3. Servicio comercial documenta reglas de cobranza legacy.
- **Criterios de aceptacion:**
  - Usuario distingue plan legacy vs matricula nueva.

---

## 4. Riesgos y mitigaciones

| Riesgo | Mitigacion |
| --- | --- |
| Regresion en cobros desde ficha | Tests Livewire por modal de cobro antes/después |
| Performance al cargar contexto | Eager loading selectivo; lazy tabs |
| Duplicacion con ClientWellnessService | Frontera clara en Paso 1.4 |

---

## 5. Criterios de cierre del modulo

- [ ] `ClienteCommercialProfileService`, `ClienteWellnessProfileService`, `ClienteCrmProfileService` implementados
- [ ] `ClienteProfileContextService` unifica carga inicial
- [ ] `ClientePerfilLive` < 400 LOC (shell + traits)
- [ ] Legacy visible con badge en UI
- [ ] Cobranza pesada accesible via Operaciones
- [ ] Tests de paridad de saldos con Operaciones y Analitica

---

## 6. Dependencias con otros modulos

| Modulo | Dependencia |
| --- | --- |
| Operaciones | Resumen deuda, atajos cobranza |
| Bienestar | Wellness summary; congelamiento fuera de wellness |
| Comercial | CRM summary, lead vinculado |
| Analitica | Mismos totales comerciales en reportes |
| Recursos | Reservas como atajo, no logica duplicada |

---

## 7. Avance de implementacion (2026-06-24)

### Fase 1 — Completada

- DTOs en `app/Data/Cliente/`: `ClienteProfileContext`, summaries comercial/wellness/CRM/operations/fidelity, `ClienteProfileMeta`.
- Servicios en `app/Services/Cliente/`:
  - `ClienteProfileContextService` (orquestador + cache request-scoped)
  - `ClienteCommercialProfileService`
  - `ClienteOperationsProfileService`
  - `ClienteWellnessProfileService`
  - `ClienteCrmProfileService`
  - `ClienteFidelityProfileService`
- `ClientePerfilLive` integrado: `refreshSelectedClienteContext` delega a agregadores; métodos `buildFinancialMatriculaRow` eliminados del Livewire.

### Fase 2 — Parcial

- Trait `ManagesClienteCommercialTab` con modales de cobro/cuotas/plan.
- Lazy load tab comercial (`loadCommercialTabData`, `wire:init`).
- Atajo `pos.cuentas-por-cobrar?cliente=X` + prefiltrado en `CustomerDebts`.
- Mapa de tabs documentado en docblock del componente.

### Fase 3 — Parcial

- Separador visual «Catálogo comercial» en sidebar.
- Badge **Legacy** en filas `cliente_membresias` + aviso en tab membresías.
- Enlace contextual a `clientes.cuotas` desde ficha.
- Resumen CRM (tags, tareas) en acciones rápidas.

### Pendiente

- Traits `ManagesClienteWellnessTab`, `ManagesClienteCrmTab`, `ManagesClienteFidelizacionTab`.
- `ClientePerfilLive` aún >400 LOC (shell + traits pendientes de extraer) — **actualización 2026-08-27: el componente ahora está en 1159 LOC, más que la medición anterior (~934); ver Fase 6 abajo sobre las causas.**
- Tests feature de paridad saldos con Operaciones (requiere DB test funcional).
- Tests Livewire modales de cobro.

---

## 8. Fases nuevas (2026-08-27): integridad referencial, seguridad y rendimiento

> Origen: revisión profunda de código (no solo arquitectura/consistencia) solicitada específicamente para el módulo Clientes. Estas fases **complementan** las Fases 1-3 de arriba (desacople de `ClientePerfilLive`); no las reemplazan. Verificado directamente en código: `ClienteService.php:846-886` (`checkRelations`), migraciones de `client_debts`, `crm_mensajes` (`onDelete('restrict')`), `cliente_app_accounts`, `cliente_fidelizacion_mensajes`, `cliente_plan_traspasos` (`cascadeOnDelete()`).
>
> **Decisión de producto ya tomada por el usuario:** al eliminar un cliente con cuenta de app, mensajes de fidelización o traspasos de plan asociados, el borrado debe **bloquearse** — igual criterio que las 16 relaciones ya protegidas hoy. Hoy esos 3 casos se borran **en silencio** (bug real, no comportamiento documentado).
>
> **Fuera de alcance por decisión del usuario:** encriptar `datos_salud`/`datos_emergencia`/`consentimientos` (columnas `json` en `clientes`) y los campos de `health_records`. Es un hallazgo real (esos datos hoy no están cifrados) pero requiere una migración de columnas más invasiva (`json`→`text`, ventana de transición) que queda fuera de este ciclo — solo se deja documentado aquí, sin fase de implementación.

### Fase 4 — Integridad referencial en borrado de clientes

**Objetivo de fase:** que `checkRelations()` cubra todas las relaciones con impacto real en el borrado, bloqueando de forma consistente.

#### Paso 4.1 — Completar `checkRelations()`

- **Archivos:** `app/Services/ClienteService.php` (líneas 846-886).
- **Tareas:**
  1. Agregar `$cliente->clientDebts()->exists()` y `$cliente->crmMensajes()->exists()` al bloque de chequeo — son las dos omisiones con FK `restrict` (`client_debts.cliente_id`, `crm_mensajes.cliente_id`), las más urgentes porque hoy producen `QueryException` cruda al usuario en vez de un mensaje amigable.
  2. Agregar `clienteAppAccount`, `clientePlanTraspaso`, `clienteFidelizacionMensajes` al mismo chequeo (FK `cascadeOnDelete` — hoy se borran en silencio). Verificar/crear los métodos de relación correspondientes en `Cliente.php` si falta alguno.
  3. Mantener el mismo mensaje de excepción amigable ya usado ("No se puede eliminar el cliente porque tiene historial u operaciones asociadas..."), extendiendo la lista de condiciones.
- **Criterios de aceptación:**
  - Intentar borrar un cliente con una deuda (`ClientDebt`) o mensaje CRM asociado da el mismo mensaje amigable que las demás relaciones, nunca una excepción SQL cruda.
  - Intentar borrar un cliente con cuenta de app / mensajes de fidelización / traspasos de plan ahora también se bloquea (cambio de comportamiento intencional, ya decidido).

#### Paso 4.2 — Tests de regresión

- **Archivos:** `tests/Feature/Services/ClienteServiceTest.php`.
- **Tareas:** un test por cada relación antes omitida (`ClientDebt`, `CrmMensaje`, `ClienteAppAccount`, `ClientePlanTraspaso`, `ClienteFidelizacionMensaje`), verificando que el borrado se bloquea con mensaje amigable y no con `QueryException`.

---

### Fase 5 — Limpieza de validación de documento

**Objetivo de fase:** una sola fuente de verdad para la validación de DNI/CE y unicidad.

#### Paso 5.1 — Eliminar `ClienteRequest.php`

- **Archivos:** `app/Http/Requests/ClienteRequest.php` (eliminar).
- **Contexto:** es un `FormRequest` confirmado como código muerto (grep solo encuentra su propia autorreferencia, ninguna ruta ni controlador lo usa) que además contradice la validación real: valida unicidad de documento sin filtrar por sucursal y sin regex de formato DNI/CE, mientras que `ClienteService::validate()` (líneas 733-763) sí exige DNI=8 dígitos exactos, CE=9-20 alfanumérico, y unicidad por `sucursal_id`.
- **Criterios de aceptación:** suite de tests completa sigue en verde tras la eliminación (confirma que era código muerto).

#### Paso 5.2 — Documentar `ClienteService::validate()` como única fuente de verdad

- **Archivos:** `app/Services/ClienteService.php` (docblock).
- **Tareas:** dejar explícito en el código que es el único validador de documento; si en el futuro se necesita un FormRequest (ej. API pública), debe delegar a esta lógica, no reimplementarla en paralelo.
- **Criterios de aceptación:** ningún cambio funcional, solo blindaje documental.

#### Paso 5.3 — Documentar la decisión sobre duplicado cross-sucursal

- **Tareas:** dejar constancia en este plan de que `ClienteCrossSucursalAlertService::findMatches()` es intencionalmente informativo (no bloquea un mismo DNI en dos sucursales) — es una decisión de negocio conocida (permite que un mismo cliente se inscriba legítimamente en dos sedes de la misma cadena), no una omisión.

---

### Fase 6 — Rendimiento en apertura de ficha

**Objetivo de fase:** reducir queries redundantes al abrir `ClientePerfilLive` sin cambiar los datos mostrados.

#### Paso 6.1 — Eliminar `find()` duplicado del cliente

- **Archivos:** `ClientePerfilLive.php` (`selectCliente`), `app/Services/Cliente/ClienteProfileContextService.php` (`build()`).
- **Tareas:** pasar la instancia de `Cliente` ya cargada a `build()` en vez de que el servicio la vuelva a buscar por id.
- **Criterios de aceptación:** reducción medible de queries (`assertQueryCount` o `DB::listen` antes/después).

#### Paso 6.2 — Reutilizar resultado de matrículas entre resumen y tab comercial

- **Archivos:** `app/Services/Cliente/ClienteCommercialProfileService.php`, trait `ManagesClienteCommercialTab`.
- **Tareas:** cachear (request-scoped, mismo patrón que `ClienteProfileContextService`) el resultado de matrículas con relaciones anidadas, para que `resolveCommercialSummary(withHistory:false)` (usado para deuda) y `loadCommercialTabData()→loadCommercialHistory(withHistory:true)` no repitan la consulta completa.
- **Criterios de aceptación:** conteo de queries al abrir una ficha de cliente baja del ~20-30 actual a un número documentado menor, sin cambiar los datos mostrados.
- **Riesgo:** el cache request-scoped debe invalidarse tras cualquier escritura (pago, cuota, matrícula) dentro del mismo request para no mostrar datos obsoletos entre tabs.
- **Dependencias:** Paso 6.1.

---

### Fase 7 — Higiene de archivo de fotos

#### Paso 7.1 — Reordenar borrado/reemplazo de foto

- **Archivos:** trait `ManagesClienteCrudAndPhoto` (líneas ~303-305).
- **Tareas:** mover el borrado del archivo anterior a después de confirmar que `update()` fue exitoso (o envolver en try/catch que revierta el archivo nuevo si `update()` falla).
- **Criterios de aceptación:** test que simule fallo de `update()` y verifique que la foto anterior sigue existiendo.

#### Paso 7.2 — Job de limpieza de fotos temporales huérfanas

- **Archivos nuevos:** comando/job programado que borre archivos en `clientes/fotos/temp` más antiguos que N días sin cliente asociado.
- **Criterios de aceptación:** job idempotente, no borra archivos recientes en uso.

---

### Fase 8 — Cobertura de tests faltante (transversal, cierre)

- **Archivos nuevos:** `tests/Feature/Livewire/ClienteLiveTest.php` (hoy no existe ningún test del listado principal, pese a que `ClientePerfilLiveTest.php` tiene 34 casos).
- **Tareas:** tests de `saveCliente`/`uploadClientePhoto`/`deleteCliente` vía Livewire, formato DNI/CE, duplicado por sucursal.
- **Criterios de aceptación:** cobertura Livewire del listado equivalente a la ya existente para el perfil.

---

## 9. Riesgos y mitigaciones (fases 4-8)

| Riesgo | Mitigacion |
| --- | --- |
| Bloquear borrado por relaciones `cascade` (Fase 4) cambia comportamiento actual visible | Ya es decisión tomada por el usuario; comunicar en el changelog del módulo que ahora se debe inactivar en vez de eliminar en esos casos |
| Eliminar `ClienteRequest.php` rompe algo no detectado por grep | Correr suite completa de tests tras la eliminación antes de confirmar el paso cerrado |
| Cache request-scoped de matrículas (Fase 6) queda desactualizado entre tabs si algo muta el estado a mitad de sesión | Invalidar el cache tras cualquier escritura (pago, cuota, matrícula) dentro del mismo request |

## 10. Criterios de cierre (fases 4-8)

- [ ] `checkRelations()` cubre las 21 relaciones (16 actuales + 5 nuevas: `ClientDebt`, `CrmMensaje`, `ClienteAppAccount`, `ClientePlanTraspaso`, `ClienteFidelizacionMensaje`).
- [ ] `ClienteRequest.php` eliminado, suite en verde.
- [ ] Reducción medible de queries al abrir ficha de cliente.
- [ ] Foto anterior no se pierde ante fallo de `update()`.
- [ ] Tests nuevos: listado `ClienteLive`, CRUD completo, formato de documento, duplicado por sucursal, las 5 relaciones nuevas en `checkRelations()`.
