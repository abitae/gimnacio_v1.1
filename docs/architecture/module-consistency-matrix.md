# Matriz y Plan Maestro de Consistencia por Modulos

> **Ultima revision:** 2026-08-27 (refresco de la revision 2026-06-24 contra el codigo actual)  
> **Fuente de navegacion:** `resources/views/components/layouts/app/sidebar.blade.php` (unica fuente; no hay config externa)  
> **Rutas:** `routes/web.php` (monolitico, ~264 lineas)  
> **Inventario:** ~95 componentes Livewire · ~74 servicios · ~80 modelos  
> **Planes de mejora detallados:** [docs/plans/README.md](../plans/README.md)  
> **Nota de refresco:** el desarrollo siguio avanzando entre el 2026-06-24 y el 2026-08-20. La mayoria de las inconsistencias detectadas en junio ya se resolvieron en codigo (INC-01, 02, 03, 04, 06, 07); INC-05 se resolvio de forma distinta a la decidida en el ADR original; INC-08 quedo parcial; INC-09 e INC-10 siguen vigentes. Se agrega INC-11 (hallazgo nuevo: `AuditLog` inerte). El riesgo real que NO avanzo es la fragmentacion de `POSLive` y `GestionNutricionalUnificadoLive`.

## Objetivo
Centralizar un analisis profundo de todos los modulos visibles desde el sidebar para alinear:

- navegacion y agrupacion funcional,
- permisos y middleware,
- componentes Livewire y servicios eje,
- modelos y relaciones dominantes,
- fuentes de verdad,
- riesgos de acoplamiento,
- plan de mejora por utilidad operativa y consistencia del sistema.

## Resumen ejecutivo del estado actual

| Indicador | Valor (2026-08-27) | Interpretacion |
| --- | --- | --- |
| Grupos sidebar | 9 (+ Inicio, Perfil) — BioTime ahora es grupo propio | Nomenclatura ya unificada (Operaciones/breadcrumbs coinciden) |
| Mega-componentes (>800 LOC) | 3, con avance desigual | `POSLive` (~1.142, -60 vs junio), `ClientePerfilLive` (~934, -220 vs junio), `GestionNutricionalUnificadoLive` (~876, sin cambio) |
| Agregadores planificados | 3/3 implementados | `ClienteCommercialProfileService`, `ClienteWellnessProfileService`, `ClienteCrmProfileService` ya existen en `app/Services/Cliente/` |
| Agregadores parciales activos | 3 | `DailyOperationsDebtService`, `ClientWellnessService` (delega congelamiento a `PlanFreezeService`), `ReporteModuloService` |
| Duplicidad operativo/analitico | Resuelta | `ReporteCuentasPorCobrarLive` (permiso `reporte.cuentas_por_cobrar`) separado de `POS\CustomerDebts` (permiso `punto_venta.ver`) |
| Rutas sin item sidebar | Reducidas | Backups y `crm.reportes` ya tienen item; sidebar Analitica ahora genera sus items dinamicamente desde `ReporteCatalog` |
| Auditoria (`AuditLog`) | 0 usos en `app/` | Modelo completo pero inerte — ningun cambio critico queda auditado (INC-11, hallazgo nuevo) |

**Nivel de consistencia global estimado:** medio. La navegacion, permisos y capa de reportes avanzaron notablemente desde junio (la mayoria de inconsistencias de navegacion/permisos ya cerraron). El riesgo que persiste es puramente de tamano/acoplamiento de componentes (`POSLive`, `GestionNutricionalUnificadoLive`) y de trazabilidad (`AuditLog` sin usar).

---

## Mapa funcional del sidebar

| Grupo sidebar (UI) | Nombre documental | Alcance funcional real | Rutas eje | Permisos eje | Observacion |
| --- | --- | --- | --- | --- | --- |
| Operaciones | Operacion diaria | acceso, caja, ventas y cobranza | `checking.*`, `cajas.*`, `pos.*` | `caja.ver`, `punto_venta.ver`; checking sin permiso explicito | Label UI distinto a breadcrumbs/docs |
| Clientes | Clientes | ficha, listado, planes, clases, cuotas | `clientes.*`, `membresias.*`, `cliente-matriculas.*`, `clases.*` | `cliente.ver`, `membresia.ver`, `matricula_cliente.ver`, `clase.ver` | Perfil y listado ya separados en sidebar |
| Bienestar | Bienestar | salud, nutricion, citas, objetivos, rutinas, progreso | `gestion-nutricional.*`, `ejercicios.*`, `rutinas-base.*`, `clientes.rutinas.*`, `ejercicios-rutinas.*` | `gestion_nutricional.ver`, `ejercicio_rutina.ver` | Conviven bienestar clinico y entrenamiento |
| Comercial | Comercial | pipeline CRM, oportunidades, campanas, mensajes, cupones | `crm.*`, `cupones.*` | `crm.ver`, `crm_mensaje.ver`, `cupon.ver` | Mensajes WhatsApp con permiso separado |
| Recursos | Recursos | catalogos y alquileres | `categorias-productos.*`, `productos.*`, `servicios.*`, `rentals.*` | `categoria_producto.ver`, `producto.ver`, `servicio.ver`, `alquiler.ver` | Mezcla inventario, servicios y espacios |
| Analitica | Analitica | centro de reportes y exportaciones | `reportes.*` | `reporte.ver` | Sidebar muestra 5 items; centro de reportes expone 11 |
| Administracion | Administracion | usuarios, roles, metodos de pago, empleados, BioTime | `employees.*`, `payment-methods.*`, `usuarios.*`, `roles.*`, `biotime.*` | `empleado.ver`, `metodo_pago.ver`, `usuario.ver`, `rol.ver`, `biotime.ver` | BioTime aqui; breadcrumbs lo clasifican como operacion |
| Super administracion | Plataforma | empresa/sucursales, migracion Excel | `company-branches.*`, `importaciones.*` | rol `super_administrador` | Backups (`administracion.backups.*`) con ruta pero sin item sidebar |

### Rutas relevantes fuera del sidebar principal

| Ruta | Componente | Motivo de exclusion |
| --- | --- | --- |
| `administracion.backups.index` | `DatabaseBackupLive` | Ruta activa; grupo Super admin expandible pero sin enlace |
| `reportes.ventas`, `reportes.matriculas`, `reportes.cajas`, etc. | Reportes modulares | Acceso via centro de reportes, no sidebar |
| `crm.reportes`, `crm.leads.show` | CRM secundario | Flujos de detalle / analitica CRM |
| `clientes.cuotas`, `cuotas.pagar` | Cuotas de matricula | Flujo contextual desde cliente/matrícula |
| `importaciones.clientes-agrupados`, `importaciones.historial` | Imports | Solo `importaciones.index` en sidebar |

---

## Principios transversales recomendados
1. Cada modulo debe tener una fuente de verdad principal y solo consumir legado en compatibilidad de lectura o migracion.
2. Los componentes Livewire deben orquestar; la logica de negocio y agregacion debe vivir en servicios.
3. La ficha del cliente debe consumir agregadores compartidos, no recalcular estados segun modulo.
4. Reportes no deben apuntar a componentes operativos reutilizados como si fueran analitica.
5. Permisos de sidebar, middleware, policies y requests deben expresar el mismo contrato funcional.
6. Los modulos deben separar claramente: operacion, configuracion, historial y analitica.
7. Sidebar, breadcrumbs y documentacion deben usar la misma nomenclatura de dominio.

## Fuentes de verdad recomendadas
- Clientes: `clientes`
- Comercial de planes: `cliente_matriculas`, `enrollment_installments`
- Legacy comercial: `cliente_membresias` solo lectura y cobranza controlada
- Caja y ventas: `cajas`, `caja_movimientos`, `ventas`, `venta_items`, `pagos`, `client_debts`
- Bienestar clinico: `health_records`, `evaluacion_medidas_nutricion`, `seguimiento_nutricion`, `citas`, `nutrition_goals`
- Entrenamiento: `routine_templates`, `client_routines`, `workout_sessions`
- CRM: `crm_leads`, `deals`, `crm_tasks`, `crm_activities`, `campaigns`, `tags`
- Recursos: `productos`, `movimientos_inventario`, `servicios_externos`, `rentable_spaces`, `rentals`
- Administracion: `users`, `roles`, `payment_methods`, `employees`, `employee_attendances`
- Plataforma: `empresas`, `sucursales`, `gym_settings`, `imports`, `import_rows`
- Integracion: `integration_error_logs`, `biotime_*`

## Matriz ejecutiva

| Modulo | Livewire eje | Servicio eje | Modelos eje | Fuente de verdad | Riesgo principal | Prioridad | Avance |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Operaciones | `CheckingLive`, `CajaLive`, `POSLive`, `CustomerDebts` | `DailyOperationsDebtService`, `ClientDebtService`, `VentaService`, `CajaService` | `Asistencia`, `Caja`, `Venta`, `Pago`, `ClientDebt` | caja + ventas + deudas | `POSLive` concentra venta, credito, reservas y cupones (~1.202 LOC) | Alta | Parcial |
| Clientes | `ClienteLive`, `ClientePerfilLive` | `ClienteService`, `ClientEnrollmentService`, `ClienteMatriculaService`, `DailyOperationsDebtService` | `Cliente`, `ClienteMatricula`, `Pago`, `Asistencia` | `clientes` + agregadores | `ClientePerfilLive` super-modulo (~1.153 LOC, ~46 metodos) | Alta | Parcial |
| Bienestar | `GestionNutricionalUnificadoLive` | `ClientWellnessService`, `SeguimientoNutricionService`, `EvaluacionMedidasNutricionService`, `CitaService` | `HealthRecord`, `EvaluacionMedidasNutricion`, `SeguimientoNutricion`, `Cita`, `ClientRoutine`, `Rental` | bienestar + entrenamiento | componente unificado grande (~875 LOC) y consultas directas | Alta | Bajo |
| Comercial | `CrmPipelineLive`, `LeadsListLive`, `LeadDetailLive`, `RenewalReactivacionLive` | `LeadService`, `DealService`, `ConvertLeadToClientService`, `RenewalReactivationService` | `Lead`, `Deal`, `CrmTask`, `CrmActivity`, `Campaign` | `crm_leads` | conversion, permisos y trazabilidad dispersa | Alta | Medio |
| Recursos | `ProductoLive`, `ServicioExternoLive`, `Rentals/*` | `ProductoService`, `ServicioExternoService`, `RentalService`, `InventarioService` | `Producto`, `CategoriaProducto`, `ServicioExterno`, `RentableSpace`, `Rental` | catalogos y alquileres | inventario debil; alquileres duplicados desde bienestar/POS | Media | Medio |
| Analitica | `ReporteIndexLive`, reportes modulares | `ReporteModuloService`, `ReporteService` | agregaciones multi-modulo | servicios agregadores | `CustomerDebts` reutilizado como reporte; sidebar incompleto | Alta | Parcial |
| Administracion | `UsuarioLive`, `RolLive`, `Employees/*`, `PaymentMethods/Index`, `BioTimeDashboard` | `SucursalContext`, `BioTimeSyncService`, servicios admin | `User`, `Role`, `PaymentMethod`, `Employee`, `BioTime*` | administracion del sistema | fragmentacion seguridad/personal/integraciones; backups ocultos | Media | Medio |
| Plataforma | `CompanyBranches/Index`, `Imports/*` | `ImportManagerService`, `DatabaseBackupService` | `Empresa`, `Sucursal`, `Import` | configuracion multi-sucursal | migracion legacy acoplada a datos productivos | Media | Operativo |

### Componentes de mayor deuda tecnica

| Componente | LOC aprox. | Servicios inyectados | Dominios mezclados |
| --- | --- | --- | --- |
| `POSLive` | ~1.202 | `VentaService`, `DailyOperationsDebtService`, `ClientDebtService`, `PosAlquilerReservaService`, etc. | POS, credito, cupones, reservas, tickets |
| `ClientePerfilLive` | ~1.153 | 7 servicios + 4 traits | comercial, cobranza, bienestar, reservas, fidelizacion, checking |
| `GestionNutricionalUnificadoLive` | ~875 | 6+ servicios | salud, nutricion, citas, rutinas, congelamiento, reservas |

### Agregadores: planificado vs implementado

| Servicio planificado | Estado | Notas |
| --- | --- | --- |
| `ClienteCommercialProfileService` | **Implementado** | `app/Services/Cliente/ClienteCommercialProfileService.php` |
| `ClienteWellnessProfileService` | **Implementado** | `app/Services/Cliente/ClienteWellnessProfileService.php` |
| `ClienteCrmProfileService` | **Implementado** | `app/Services/Cliente/ClienteCrmProfileService.php` |
| `SalesAnalyticsService` / `FinanceAnalyticsService` | No implementado | `ReporteModuloService` (~732 LOC) sigue centralizando todo; sigue siendo deuda pendiente |
| `DailyOperationsDebtService` | **Implementado** | usado en checking, POS y ficha cliente |
| `ClientDebtService` | **Implementado** | cobranza operativa en POS/creditos |
| `PlanFreezeService` | **Implementado (nuevo)** | `app/Services/Cliente/PlanFreezeService.php`; `ClientWellnessService::freezePlan*` ahora delega aqui (INC-08 parcial) |
| `EffectivePermissionsService` | **Implementado a medias** | `app/Services/Admin/EffectivePermissionsService.php` existe (forUser/explains) pero sin ruta ni componente Livewire que lo consuma |

---

## Registro de inconsistencias detectadas

### Resueltas (verificado en codigo, 2026-08-27)

| ID | Tipo | Descripcion original | Evidencia de resolucion |
| --- | --- | --- | --- |
| INC-01 | Nomenclatura | Sidebar dice "Operaciones"; breadcrumbs/docs dicen "Operacion diaria" | `breadcrumbs.blade.php` usa `__('Operaciones')` para `checking.*`/`cajas.*`/`pos.*`, igual que el heading del sidebar |
| INC-02 | Permisos | `checking.index` sin middleware de permiso | Permiso `checking.ver` en `PermissionCatalog.php` y `routes/web.php` (`middleware('permission:checking.ver')`) |
| INC-03 | Operativo/Analitico | `reportes.cuentas-por-cobrar` = `POS\CustomerDebts` | Ruta apunta a `Reportes\ReporteCuentasPorCobrarLive` con permiso propio `reporte.cuentas_por_cobrar`; `pos.cuentas-por-cobrar` operativo quedo separado con `punto_venta.ver` |
| INC-04 | Navegacion | Cobros pendientes en Operaciones Y Cuentas por cobrar en Analitica apuntaban al mismo componente | Mismo commit que INC-03: componentes ya separados |
| INC-06 | Navegacion | `administracion.backups.index` sin enlace sidebar | Item "Respaldos BD" agregado en grupo "Super administracion" |
| INC-07 | Navegacion | Analitica sidebar: 5 items vs centro de reportes: 11 | Sidebar itera dinamicamente `ReporteCatalog::visibleFor()`; sincronizado por diseno con el centro de reportes (11 rutas `reportes.*`) |

### Resuelta con decision distinta a la documentada

| ID | Tipo | Descripcion original | Estado real |
| --- | --- | --- | --- |
| INC-05 | Clasificacion | BioTime en grupo Administracion; breadcrumbs en Operacion diaria | El ADR (`adr-biotime-clasificacion.md`, 2026-06-24) decidio moverlo a "Operaciones". En codigo, BioTime terminó como **grupo de sidebar propio de nivel superior** (`heading="BioTime"`), no dentro de Operaciones ni de Administracion. Ver ADR corregido. |

### Parciales

| ID | Tipo | Descripcion | Estado | Accion recomendada |
| --- | --- | --- | --- | --- |
| INC-08 | Dominio | Congelamiento de planes en `ClientWellnessService` / bienestar | `PlanFreezeService` extraido (`app/Services/Cliente/PlanFreezeService.php`); `ClientWellnessService::freezePlan*` ya delega ahi. Vive en namespace `Cliente`, no en una capa "comercial" formal compartida | Evaluar si basta como esta o se mueve a `app/Services/Comercial/` |

### Vigentes (deuda técnica real, sin avance)

| ID | Tipo | Descripcion | Impacto | Accion recomendada |
| --- | --- | --- | --- | --- |
| INC-09 | Legacy | `cliente_membresias` sigue en ficha, bienestar y reportes | Riesgo de calculos divergentes | `LegacyMembresiaReadService` ya existe como unico punto de lectura (T.5 completado); falta migrar consultas directas restantes |
| INC-10 | Permisos CRM | `crm.mensajes` usa `crm_mensaje.ver` fuera del grupo `crm.ver` | Gestion de permisos fragmentada | Ver `03-comercial-permisos-matriz.md`; confirmado vigente en `routes/web.php` y `PermissionCatalog.php` |
| INC-11 | Auditoria (nuevo, 2026-08-27) | Modelo `AuditLog` completo (`payload_before/after`, relacion con `User`) pero **sin un solo `AuditLog::create()` en todo `app/`** | Cambios criticos en usuarios, roles, metodos de pago y backups no quedan auditados | Instrumentar `AuditLog::create()` en los servicios/Livewire de Usuarios, Roles, PaymentMethods y `DatabaseBackupLive` |

---

## 0. Operaciones (Operacion diaria)

### Alcance actual
El modulo agrupa la operacion transaccional diaria del centro:

- control de acceso / checking manual,
- apertura, movimientos y cierre de caja,
- punto de venta (productos, servicios, matriculas, alquileres),
- ventas a credito,
- cobros pendientes / cuentas por cobrar operativas,
- comprobantes y tickets PDF.

### Componentes y servicios eje
- Livewire:
  - `app/Livewire/Checking/CheckingLive.php` (~294 LOC)
  - `app/Livewire/Cajas/CajaLive.php` (~434 LOC)
  - `app/Livewire/POS/POSLive.php` (~1.202 LOC)
  - `app/Livewire/POS/CreditSales.php` (~188 LOC)
  - `app/Livewire/POS/CustomerDebts.php` (~198 LOC)
  - `app/Livewire/Enrollments/Installments/PaymentForm.php` (cobro cuotas contextual)
- Servicios:
  - `app/Services/VentaService.php` (~507 LOC)
  - `app/Services/CajaService.php` (~481 LOC)
  - `app/Services/DailyOperationsDebtService.php` (~168 LOC)
  - `app/Services/ClientDebtService.php` (~151 LOC)
  - `app/Services/CobroTicketService.php`
  - `app/Services/PosAlquilerReservaService.php` (puente POS ↔ alquileres)

### Modelos y relaciones dominantes
- `Asistencia -> cliente, sucursal`
- `Caja -> movimientos, ventas, sucursal`
- `Venta -> items, pagos, cliente, caja`
- `Pago -> cliente, clienteMatricula|clienteMembresia|clientDebt`
- `ClientDebt -> cliente, venta`

### Hallazgos (refrescado 2026-08-27)
- `DailyOperationsDebtService` ya unifica resumen de deuda para checking, POS y ficha cliente: avance positivo de consistencia.
- `ClientDebtService` concentra la logica transaccional de cobro de deudas; separacion correcta respecto al agregador de resumen.
- **INC-02 resuelto:** `checking.ver` existe y protege la ruta.
- **INC-03/INC-04 resueltos:** `reportes.cuentas-por-cobrar` ahora usa `ReporteCuentasPorCobrarLive` (permiso `reporte.cuentas_por_cobrar`), separado de `pos.cuentas-por-cobrar` (`POS\CustomerDebts`, permiso `punto_venta.ver`).
- `POSLive` sigue siendo el componente mas grande del sistema (~1.142 LOC, bajo desde ~1.202). Solo se extrajo un trait pequeno, `app/Livewire/POS/Concerns/ManagesPosCartTotals.php` (54 LOC). La fragmentacion real por tipo de venta (credito/reservas/cupones) **no se ha hecho**.
- BioTime ya no vive en Administracion: es grupo de sidebar propio (ver seccion BioTime mas abajo y ADR actualizado).

### Riesgos actuales
- Cambios en POS pueden afectar ventas, credito, reservas y cupones simultaneamente — este riesgo sigue intacto pese a la extraccion de `CustomerDebts` y del trait de totales.
- Divergencia futura entre saldos mostrados en POS, ficha cliente y reportes si no comparten agregadores (mitigado en parte por `DailyOperationsDebtService`, pero `SalesAnalyticsService`/`FinanceAnalyticsService` dedicados siguen sin implementar).

### Plan de mejora (pendiente real)
1. Fragmentar `POSLive` en sub-componentes o servicios de orquestacion por tipo de venta — **sigue siendo el paso 1, sin avance sustancial**.
2. Mantener `DailyOperationsDebtService` como unica fuente de resumen de deuda operativa — cumplido, mantener como regla.
3. ~~Reservar `CustomerDebts` exclusivamente para operacion; crear vista analitica separada~~ — **hecho** (`ReporteCuentasPorCobrarLive`).
4. ~~Agregar permiso `checking.ver`~~ — **hecho**.
5. ~~Evaluar mover BioTime al grupo Operaciones~~ — **resuelto de otra forma**: BioTime quedo como grupo propio, no dentro de Operaciones (ver ADR).

### Prioridad recomendada
- Fase 1 (unica pendiente real): desacoplar `POSLive` por tipo de venta.
- ~~Fase 2: permisos de checking~~ — cerrada.
- ~~Fase 3: integracion coherente BioTime ↔ checking~~ — cerrada via ADR (grupo propio + widget en Checking).

---

## 1. Clientes

### Alcance actual
El modulo `Clientes` agrupa:

- listado y busqueda general de clientes,
- ficha 360 del cliente,
- catalogo de membresias,
- matriculas de clientes,
- clases,
- acceso indirecto a cuotas (`clientes.cuotas`, `cuotas.pagar`).

### Componentes y servicios eje
- Livewire:
  - `app/Livewire/Clientes/ClienteLive.php` (~63 LOC)
  - `app/Livewire/Clientes/ClientePerfilLive.php` (~1.153 LOC)
  - `app/Livewire/Clientes/Concerns/ManagesClienteCrudAndPhoto.php` (~497 LOC, trait)
  - `app/Livewire/Membresias/MembresiaLive.php`
  - `app/Livewire/ClienteMatriculas/ClienteMatriculaLive.php`
  - `app/Livewire/Clases/ClaseLive.php`
  - `app/Livewire/Enrollments/Installments/*` (cuotas)
- Servicios:
  - `app/Services/ClienteService.php` (~394 LOC)
  - `app/Services/ClientEnrollmentService.php`
  - `app/Services/ClienteMatriculaService.php` (~596 LOC)
  - `app/Services/EnrollmentInstallmentService.php` (~529 LOC)
  - `app/Services/DailyOperationsDebtService.php`
  - `app/Services/ClientWellnessService.php` (consumido desde ficha)
  - `app/Services/AsistenciaService.php`

### Modelos y relaciones dominantes
- `Cliente -> clienteMatriculas, clienteMembresias, pagos, asistencias, healthRecord, citas, seguimientosNutricion, clientRoutines, rentals, crmTags, crmTasks, crmActivities, clientDebts`
- `ClienteMatricula -> cliente, membresia|clase, pagos, enrollmentInstallments`
- `Pago -> cliente, clienteMatricula|clienteMembresia|clientDebt`

### Hallazgos (refrescado 2026-08-27)
- **Avance navegacion:** sidebar ya separa "Perfil de cliente" y "Listado de clientes" (Fase 3 lograda en UI).
- `ClienteLive` esta bien acotado como listado CRUD y usa `ClienteService`.
- `ClientePerfilLive` bajo de ~1.153 a **~934 LOC** gracias a la extraccion de los 3 agregadores. Sigue siendo grande pero la tendencia es positiva.
- La ficha consume `DailyOperationsDebtService` para deudas: correcto.
- Los 3 agregadores planificados **ya estan implementados**: `ClienteCommercialProfileService`, `ClienteWellnessProfileService`, `ClienteCrmProfileService` (`app/Services/Cliente/`).
- El perfil aun combina reservas, checking y otras acciones operativas puntuales, pero la logica de negocio de cada dominio ya vive en su servicio agregador correspondiente.
- Trait `ManagesClienteCrudAndPhoto` (~497 LOC) sigue como extraccion parcial previa.

### Riesgos actuales
- La ficha aun puede seguir creciendo; sin los 3 agregadores el riesgo era mayor, ahora es moderado.
- `ClienteService::checkRelations()` confirma que `Cliente` sigue siendo el nodo mas conectado del sistema.

### Plan de mejora (pendiente real)
1. Mantener `ClienteLive` como modulo de listado y alta/edicion basica — cumplido.
2. ~~Consolidar `ClientePerfilLive` extrayendo subdominios a servicios~~ — **hecho**: los 3 `Cliente*ProfileService` ya existen y la ficha bajo a ~934 LOC.
3. Seguir reduciendo la ficha: mover acciones operativas puntuales (reservas, checking) a los modulos duenos, dejando solo atajos.
4. Mover acciones de cobranza pesada hacia `Operaciones > Cobros pendientes` y dejar en ficha atajos contextuales.
5. Separar visualmente catalogo comercial (`membresias`, `clases`, `matriculas`) del bloque de ficha — hecho.
6. Una sola carga de contexto por cliente via agregador, no consultas independientes por tab — logrado con los 3 agregadores.

### Prioridad recomendada
- ~~Fase 1: implementar agregadores de contexto~~ — **cerrada**.
- Fase 2 (pendiente): terminar de convertir la ficha en shell delgado, moviendo las ultimas acciones operativas (reservas/checking) fuera del componente principal.
- ~~Fase 3: separacion navegacion ficha vs catalogo comercial~~ — **cerrada**.

---

## 2. Bienestar

### Alcance actual
El modulo `Bienestar` mezcla:

- ficha de salud,
- evaluaciones corporales,
- seguimientos nutricionales,
- citas y calendario,
- objetivos nutricionales,
- ejercicios y rutinas base,
- rutinas asignadas y sesiones,
- progreso y cumplimiento,
- congelamiento de planes (via `ClientWellnessService`),
- reservas de espacios (atajo desde gestion unificada).

### Componentes y servicios eje
- Livewire:
  - `app/Livewire/GestionNutricional/GestionNutricionalUnificadoLive.php` (~875 LOC)
  - `app/Livewire/GestionNutricional/CalendarioCitasLive.php`
  - `app/Livewire/MedidasNutricion/MedidasNutricionLive.php` (~592 LOC)
  - `app/Livewire/Nutrition/*` (Goals, HealthRecord, Progress)
  - `app/Livewire/Exercises/*`
  - `app/Livewire/Routines/Templates/*`
  - `app/Livewire/Clients/Routines/*`, `Clients/Workouts/*`
  - `app/Livewire/Reports/ProgressByExercise.php`, `Compliance.php`
- Servicios:
  - `app/Services/ClientWellnessService.php` (~359 LOC)
  - `app/Services/EvaluacionMedidasNutricionService.php`
  - `app/Services/SeguimientoNutricionService.php`
  - `app/Services/CitaService.php` (~270 LOC)
  - `app/Services/ClientRoutineService.php`
  - `app/Services/RoutineTemplateService.php`

### Modelos y relaciones dominantes
- `Cliente -> healthRecord, evaluacionesMedidasNutricion, seguimientosNutricion, citas, clientRoutines, nutritionGoals`
- `ClientRoutine -> cliente, trainer, routineTemplate, days`
- `WorkoutSession -> clientRoutine, exercises, sets`
- `Rental -> cliente, rentableSpace` (desde bienestar como atajo)
- `ClienteMatricula|ClienteMembresia` por congelamiento

### Hallazgos
- `GestionNutricionalUnificadoLive` sigue siendo uno de los componentes mas cargados (~875 LOC, ~39 metodos, 10 modales).
- Mezcla bienestar clinico, entrenamiento y operaciones anexas (reservas, congelamientos).
- `ClientWellnessService` actua como agregador parcial pero mezcla congelamiento comercial, reservas, timeline y overview de rutinas.
- Sidebar separa correctamente permisos `gestion_nutricional.ver` vs `ejercicio_rutina.ver`.
- Rutas de sesiones (`clientes.sesiones.*`) existen pero no tienen item propio en sidebar.

### Riesgos actuales
- Cualquier cambio en bienestar puede romper entrenamiento o reservas.
- Congelamiento de planes vive en bienestar aunque afecta dominio comercial.
- Progreso/cumplimiento estan en sidebar; sesiones de entrenamiento solo por ruta contextual.

### Plan de mejora
1. Separar conceptualmente `Bienestar` en:
   - `Salud y nutricion`
   - `Entrenamiento`
2. Reducir `GestionNutricionalUnificadoLive` extrayendo:
   - `ClienteHealthHubService`
   - `ClienteNutritionService`
   - `ClienteTrainingOverviewService`
3. Mover congelamiento a capa compartida comercial/bienestar.
4. Reubicar reservas bajo `Recursos` como operacion principal; bienestar solo atajo.
5. Centralizar timeline del cliente como servicio transversal.
6. Definir `health_records` y tablas de seguimiento como fuente principal del subdominio de salud.

### Prioridad recomendada
- Fase 1: dividir servicios de `GestionNutricionalUnificadoLive`.
- Fase 2: separar salud/nutricion de entrenamiento en UI y rutas.
- Fase 3: mover reservas y congelamientos a dominios mas coherentes.

---

## 3. Comercial

### Alcance actual
El modulo `Comercial` agrupa:

- pipeline Kanban,
- leads y detalle,
- tareas CRM,
- oportunidades (deals),
- campanas,
- etiquetas,
- renovacion/reactivacion,
- mensajes WhatsApp,
- cupones de descuento.

### Componentes y servicios eje
- Livewire (18 componentes en `Crm/` + `Coupons/`):
  - `CrmPipelineLive`, `LeadsListLive`, `LeadDetailLive`, `ConvertLeadLive`
  - `CrmTasksLive`, `CrmDealsLive`, `DealFormLive`, `CrmCampaignsLive`, `CampaignDetailLive`
  - `CrmTagsLive`, `ClienteTagsLive`, `TagPickerLive`
  - `RenewalReactivacionLive`, `MensajesLive`, `CrmReportesLive`
  - `Coupons/Index`, `Form`, `Show`
- Servicios:
  - `app/Services/Crm/LeadService.php` (~188 LOC)
  - `app/Services/Crm/DealService.php`, `CrmTaskService.php`, `CrmActivityService.php`
  - `app/Services/Crm/CampaignService.php`, `CrmReportService.php`
  - `app/Services/Crm/ConvertLeadToClientService.php`
  - `app/Services/Crm/RenewalReactivationService.php`
  - `app/Services/CrmMensajeService.php`
  - `app/Services/WhatsApp/*` (mock)

### Modelos y relaciones dominantes
- `Lead -> stage, assignedTo, tags, deals, activities, tasks, cliente`
- `Deal -> lead, cliente, membresia`
- `CrmTask -> lead|cliente`
- `Campaign -> targets, activities`
- `DiscountCoupon -> usages`

### Hallazgos (refrescado 2026-08-27)
- **Nuevo:** existe un plan dedicado de UI/UX para este modulo — [`03-comercial-ui-ux-plan-mejora.md`](../plans/03-comercial-ui-ux-plan-mejora.md) (2026-08-26). Cubre drag-and-drop real del Kanban, componentes Blade compartidos (badges, tag-pills), accesibilidad de formularios y graficos en Reportes CRM. Es complementario a este documento: no toca permisos ni reglas de negocio.
- Modulo CRM maduro: 18+ componentes Livewire con formularios dedicados; ahora incluye tambien `CrmStageService` (nuevo, sin documentar previamente).
- `CrmPipelineLive` bien enfocado como vista Kanban.
- `LeadService` concentra filtros, stages y asignacion.
- Conversion lead → cliente separada en `ConvertLeadToClientService`.
- **INC-10 vigente:** `crm.mensajes` sigue usando permiso `crm_mensaje.ver` separado del grupo `crm.ver` (confirmado en `routes/web.php` y `PermissionCatalog.php`). Ver matriz detallada en `03-comercial-permisos-matriz.md`.
- **Resuelto:** `crm.reportes` ya tiene item visible en el sidebar, dentro del grupo Comercial.

### Riesgos actuales
- Incoherencia potencial entre estado del lead, etapa del pipeline y actividad comercial real.
- Permisos CRM fragmentados (INC-10) siguen dificultando administrar accesos de forma consistente.
- Conversion lead → cliente puede perder trazabilidad si no se estandariza.
- Cupones integrados en POS pero con trazabilidad limitada fuera de venta.

### Plan de mejora (pendiente real)
1. Formalizar embudo: captacion → contacto → oportunidad → conversion → renovacion.
2. **Unificar permisos CRM (INC-10)** — sigue siendo el paso mas concreto y accionable de este modulo.
3. Crear `CrmOperationalSummaryService` para KPIs consistentes.
4. Asegurar trazabilidad completa en `ConvertLeadToClientService`.
5. ~~Separar en UI: CRM / Retencion / Promociones~~ — reportes CRM ya visibles; revisar si falta separar mensajes/cupones.
6. Integrar cupones con POS/comercial de forma trazable.

### Prioridad recomendada
- Fase 1 (pendiente): unificar permisos CRM (INC-10) y trazabilidad de conversion.
- Fase 2: consolidar retencion/reactivacion con CRM.
- Fase 3: integrar promociones/cupones con ventas y campanas.

---

## 4. Recursos

### Alcance actual
El modulo `Recursos` contiene:

- categorias de productos,
- productos e inventario,
- servicios externos,
- espacios alquilables,
- calendario de alquileres,
- reservas (crear/editar/ver),
- ingresos por alquiler.

### Componentes y servicios eje
- Livewire:
  - `app/Livewire/Categorias/CategoriaProductoLive.php`
  - `app/Livewire/Productos/ProductoLive.php`
  - `app/Livewire/Servicios/ServicioExternoLive.php`
  - `app/Livewire/Rentals/Spaces/Index.php`, `Spaces/Form.php`
  - `app/Livewire/Rentals/Calendar/Index.php`
  - `app/Livewire/Rentals/Bookings/*`
  - `app/Livewire/Rentals/Report.php`
- Servicios:
  - `app/Services/ProductoService.php` (~220 LOC)
  - `app/Services/ServicioExternoService.php`
  - `app/Services/InventarioService.php` (~101 LOC)
  - `app/Services/RentalService.php`, `RentableSpaceService.php`
  - `app/Services/PosAlquilerReservaService.php` (reservas desde POS)
  - soporte parcial desde `ClientWellnessService` y `ClientePerfilLive`

### Modelos y relaciones dominantes
- `Producto -> categoria, movimientosInventario`
- `MovimientoInventario -> producto`
- `ServicioExterno`
- `RentableSpace -> rates, rentals`
- `Rental -> rentableSpace, cliente, rentalPayments`

### Hallazgos (refrescado 2026-08-27)
- `ProductoLive` + `ProductoService` razonablemente encapsulados.
- Inventario existe (`InventarioService`, `MovimientoInventario`) pero sigue delgado (~101 LOC, sin cambios desde junio). `VentaService::registrarSalidaVenta` ya lo invoca al vender por POS.
- Alquileres forman subdominio coherente con calendario, reservas y reporte.
- **Escritura de reservas ya consolidada:** POS (`PosAlquilerReservaService`), bienestar/ficha (`ClientWellnessService`) y Recursos (`Rentals/Bookings/Form`) delegan todos en `RentalService::create()`/`createBooking()`. No hay `Rental::create` directo fuera de `RentalService`. Persiste la triple **UI** de entrada, pero la fuente de escritura ya es unica.
- Servicios externos sin la misma profundidad operativa que productos/alquileres.

### Riesgos actuales
- POS vende productos sin capa fuerte de inventario → stock fragil (sin cambios).
- La triple UI de entrada de reservas (aunque ya escriben al mismo servicio) sigue generando UX inconsistente entre modulos.

### Plan de mejora (pendiente real)
1. Dividir `Recursos` en: catalogo comercial, inventario, espacios/alquileres.
2. Fortalecer `InventarioService` como fuente de movimientos — **sin avance desde junio, sigue pendiente**.
3. Bandeja operativa de alquileres: reservas del dia, confirmaciones, pagos pendientes.
4. ~~Reservas creadas principalmente desde `Recursos`~~ — la **escritura** ya es unica via `RentalService`; falta solo consolidar la experiencia de UI.
5. Revisar trazabilidad comercial de `ServicioExterno`.

### Prioridad recomendada
- Fase 1 (pendiente real): robustecer `InventarioService`.
- Fase 2: consolidar la UI de reservas sobre la escritura ya unificada.
- Fase 3: alinear catalogos vendibles bajo experiencia unificada.

---

## 5. Analitica

### Alcance actual
El modulo `Analitica` agrupa:

- centro de reportes (`ReporteIndexLive` — hub con 11 destinos),
- reportes modulares dedicados (clientes, ventas, matriculas, financiero, cajas, etc.),
- cuentas por cobrar *(reutiliza componente operativo)*,
- cuotas vencidas,
- exportaciones PDF/Excel por modulo,
- reportes nutricionales legacy (`ReporteService`).

### Componentes y servicios eje
- Livewire:
  - `app/Livewire/Reportes/ReporteIndexLive.php` (~14 LOC, hub de navegacion)
  - `app/Livewire/Reportes/ReporteVentasLive.php`, `ReporteMatriculasLive.php`, `ReporteFinancieroLive.php`
  - `app/Livewire/Reportes/ReporteClientesLive.php`, `ReporteClientesMembresiaClasesLive.php`
  - `app/Livewire/Reportes/ReporteUsuariosLive.php`, `ReporteCajasLive.php`
  - `app/Livewire/Reportes/ReporteProductosServiciosLive.php`, `ReporteGimnasioLive.php`
  - `app/Livewire/Reportes/ReporteCuotasVencidasLive.php`
  - **Reutilizado:** `app/Livewire/POS/CustomerDebts.php` en `reportes.cuentas-por-cobrar`
  - `app/Livewire/Reports/ProgressByExercise.php`, `Compliance.php` (bienestar, rutas bajo `ejercicios-rutinas.*`)
- Servicios:
  - `app/Services/ReporteModuloService.php` (~732 LOC)
  - `app/Services/ReporteModuloPdfService.php`
  - `app/Services/ReporteService.php` (~259 LOC, nutricion/legacy)
- Controladores: `ReporteModuloController` (export PDF/Excel), `ReporteExportDownloadController`

### Modelos y relaciones dominantes
- Multi-modulo: `Venta`, `VentaItem`, `Pago`, `Cliente`, `ClienteMatricula`, `ClienteMembresia`, `Caja`, `Producto`, `ServicioExterno`, `Asistencia`, `Cita`, `EnrollmentInstallment`

### Hallazgos (refrescado 2026-08-27)
- `ReporteModuloService` centraliza agregacion: buena base arquitectonica (~732 LOC).
- Reportes modulares dedicados existen para la mayoria de dominios.
- **Resuelto:** `reportes.cuentas-por-cobrar` ya usa `ReporteCuentasPorCobrarLive` con permiso dedicado `reporte.cuentas_por_cobrar`, separado del operativo `pos.cuentas-por-cobrar` (`punto_venta.ver`).
- **Resuelto:** el sidebar de Analitica ya genera sus items dinamicamente desde `ReporteCatalog::visibleFor()`, igual que el centro de reportes (11 rutas `reportes.*`) — ya no hay descubrimiento incompleto por diseno.
- Nuevos traits reutilizables agregados a los componentes de reportes: `AuthorizesReportAccess` y `ScopesReporteBySucursal` (`app/Livewire/Reportes/Concerns/`) — buen ejemplo del patron shell+concerns recomendado en el plan Transversal (T.13).
- Compatibilidad legacy `cliente_membresias` vs `cliente_matriculas` sigue presente en reportes de clientes (sin cambios; mitigado parcialmente por `LegacyMembresiaReadService`).
- `SalesAnalyticsService` / `ClientAnalyticsService` / `FinanceAnalyticsService` / `CajaAnalyticsService` dedicados **siguen sin implementarse**; `ReporteModuloService` sigue concentrando todo.

### Riesgos actuales
- Analitica puede seguir devolviendo resultados distintos a operacion mientras no existan agregadores analiticos dedicados (el riesgo de fondo de este modulo no cambio, solo se corrigio el sintoma mas visible de `CustomerDebts`).
- Compatibilidad legacy en reportes de clientes sigue siendo una fuente potencial de divergencia.

### Plan de mejora (pendiente real)
1. Separar estrictamente `Operacion` de `Analitica` — logrado para cuentas por cobrar; extender el mismo patron a otros reportes que aun reutilicen componentes operativos si los hay.
2. Crear servicios agregadores dedicados (`SalesAnalyticsService`, `ClientAnalyticsService`, `FinanceAnalyticsService`, `CajaAnalyticsService`) — **sigue pendiente, sin avance**.
3. ~~Reemplazar `CustomerDebts` en reportes por `ReporteCuentasPorCobrarLive`~~ — **hecho**.
4. Reportes financieros deben consumir los mismos agregadores que operacion diaria.
5. Compatibilidad legacy solo a nivel de servicio con etiquetas de origen.
6. ~~Alinear sidebar con centro de reportes~~ — **hecho** (fuente de datos compartida `ReporteCatalog`).

### Prioridad recomendada
- ~~Fase 1: desacoplar `CustomerDebts` del modulo analitico~~ — **cerrada**.
- Fase 2 (pendiente real): unificar agregadores de saldos, clientes y ventas con operacion via los servicios analiticos dedicados.
- Fase 3: enriquecer exportaciones y trazabilidad por sucursal (ya hay avance via `ScopesReporteBySucursal` y `sucursal-scope-panel.blade.php`).

---

## 6. Administracion

### Alcance actual
El modulo `Administracion` agrupa:

- empleados y ficha,
- asistencia del personal,
- BioTime (integracion biometrica),
- metodos de pago,
- usuarios,
- roles.

**Fuera del sidebar pero relacionado:**
- backups (`administracion.backups.index`) — ruta activa, sin enlace visible,
- empresa/sucursales e importaciones — grupo Super administracion.

### Componentes y servicios eje
- Livewire:
  - `app/Livewire/Usuarios/UsuarioLive.php` (~232 LOC)
  - `app/Livewire/Roles/RolLive.php`
  - `app/Livewire/Employees/*` (7 componentes)
  - `app/Livewire/Settings/PaymentMethods/Index.php`
  - `app/Livewire/BioTime/BioTimeDashboard.php` (~206 LOC)
  - `app/Livewire/Administracion/DatabaseBackupLive.php` (~152 LOC)
  - `app/Livewire/Settings/CompanyBranches/Index.php` (~273 LOC) — Super admin
  - `app/Livewire/Imports/*` (4 componentes) — Super admin
- Servicios:
  - `SucursalContext` (~142 LOC)
  - `BioTimeSyncService` (~403 LOC)
  - `DatabaseBackupService` (~841 LOC), `DatabaseRestoreService` (~763 LOC)
  - `ImportManagerService` + 26 servicios en `Imports/`

### Modelos y relaciones dominantes
- `User -> roles, sucursales`
- `Employee -> attendances, debts`
- `PaymentMethod`
- `Role`
- `Sucursal`, `Empresa`, `GymSetting`
- `BioTime*` (devices, mappings, transactions, sync logs)
- `Import`, `ImportRow`
- `AuditLog`

### Hallazgos (refrescado 2026-08-27)
- **Avance:** `company-branches` ya expuesto en Super administracion (antes oculto).
- `UsuarioLive` restringe por sucursal y bloquea gestion de super admins especiales.
- Administracion sigue fragmentada en: seguridad, personal, integraciones, configuracion financiera, soporte tecnico (sin cambios estructurales).
- **Resuelto de forma distinta:** BioTime ya no esta clasificado en Administracion — es su propio grupo de nivel superior en el sidebar (ver seccion BioTime). Breadcrumbs coinciden con esa clasificacion.
- **Resuelto:** backups (`administracion.backups.index`) ya tiene item visible en "Super administracion".
- `EffectivePermissionsService` (`app/Services/Admin/`) ya existe con la logica usuario → rol → permisos efectivos, pero **no tiene ruta ni componente Livewire** que lo exponga — el paso 2 del plan esta a medio camino, no en cero.
- **Hallazgo nuevo (INC-11):** `AuditLog` esta completamente modelado (`payload_before/after`, relacion `User`) pero no se usa: cero llamadas `AuditLog::create()` en `app/`. Ningun cambio de usuarios, roles, metodos de pago o backups queda auditado hoy.
- Modulo Imports (27 servicios) es critico para migracion legacy pero aislado a super admin (sin cambios).

### Riesgos actuales
- Seguridad depende de permisos + sucursal activa + roles; UX no refleja permisos efectivos claramente (mitigado a medias: el servicio ya existe, falta UI).
- **Trazabilidad nula de cambios criticos** (INC-11): si un rol o metodo de pago se modifica o un backup se restaura, no queda registro de quien lo hizo — riesgo de seguridad real que no estaba contemplado en la revision de junio.

### Plan de mejora (pendiente real)
1. Dividir `Administracion` en: seguridad/accesos, personal, integraciones, config financiera, soporte tecnico.
2. Exponer una pantalla (ruta + componente Livewire) para `EffectivePermissionsService`, que ya existe — falta solo la capa UI.
3. ~~Exponer backups en Super administracion~~ — **hecho**; falta la auditoria (ver punto 6).
4. ~~Reclasificar BioTime~~ — **hecho**, como grupo propio (no como se planteo originalmente, pero el objetivo de sacarlo de Administracion se cumplio).
5. Auditar permisos sembrados vs middleware real.
6. **Instrumentar `AuditLog::create()`** en cambios de usuarios, roles, metodos de pago y backups — maxima prioridad nueva de este modulo.

### Prioridad recomendada
- ~~Fase 1: ordenar navegacion y exponer backups; reclasificar BioTime~~ — **cerrada**.
- Fase 2 (pendiente, reordenada por prioridad): activar `AuditLog` en acciones criticas (INC-11) y exponer la UI de permisos efectivos (el servicio ya existe).
- Fase 3: administracion avanzada / auditoria de permisos sembrados.

---

## 7. Plataforma (Super administracion)

### Alcance actual
Modulo transversal para super administradores:

- configuracion empresa y sucursales,
- carga inicial y migracion desde Excel legacy,
- historial de importaciones y exportacion de errores.

### Componentes y servicios eje
- Livewire: `Imports/Dashboard`, `ClientesAgrupados`, `History`, `Show`
- Livewire: `Settings/CompanyBranches/Index`
- Servicios: 27 archivos en `app/Services/Imports/` + `ImportManagerService`

### Hallazgos
- Modulo maduro para migracion legacy (socios, cuotas, deudas, matriculas).
- Acceso restringido por rol `super_administrador`.
- Impacta directamente fuentes de verdad de clientes, matriculas y deudas post-migracion.
- Sidebar solo enlaza `importaciones.index`; rutas `clientes-agrupados` e `historial` son secundarias.

### Riesgos
- Datos importados pueden coexistir con legacy activo si no se marca origen — **sigue vigente, sin avance** (no se encontro campo `origen`/`is_imported` en modelos ni migraciones de `Imports/`).
- Operaciones post-importacion pueden calcular estados distintos segun origen del registro.

### Plan de mejora (sin avance desde junio)
1. Etiquetar registros importados vs nativos en servicios agregadores.
2. Checklist post-importacion en dashboard.
3. Documentar mapeo legacy → modelo nuevo por entidad.

---

## 8. BioTime (acceso biometrico)

> Modulo no cubierto como seccion propia en la revision original de esta matriz (2026-06-24), aunque el README de planes ya lo priorizaba. Se agrega ahora porque el desarrollo reciente (commits de julio-agosto) lo convirtio en uno de los modulos mas maduros del sistema.

### Alcance actual
Integracion biometrica de acceso fisico, con configuracion, sincronizacion y control de acceso por sede. Plan detallado: [`08-biotime-integracion-plan.md`](../plans/08-biotime-integracion-plan.md).

### Estado
- **Clasificacion UI (INC-05):** grupo de sidebar propio (`heading="BioTime"`), separado de Operaciones y Administracion — ver [`adr-biotime-clasificacion.md`](./adr-biotime-clasificacion.md) (actualizado).
- **Fases 0-5 del plan de integracion:** todas marcadas como hechas (config por sede, API commands/ack/roster, elegibilidad por matricula vigente, puente Python, panel operacional, ADR aceptado).
- **Pendiente real:** solo 2 items de checklist que requieren validacion de campo (PoC area↔dispositivo en sede piloto; runbook usado por recepcion al menos una vez) — no son deuda de codigo, son validacion operativa.

### Riesgos actuales
- Ninguno arquitectonico relevante; el riesgo restante es puramente operativo (validacion en campo por sede).

### Plan de mejora
- Sin pasos de codigo pendientes. Dar seguimiento a los 2 checklist de campo del plan 08.

---

## Dependencias y relaciones transversales

### Cliente como entidad pivote
`Cliente` conecta comercial, operacion diaria, bienestar, CRM, alquileres y reportes. Obliga a usar agregadores de contexto en vez de consultas sueltas por modulo.

### Operacion diaria ↔ Analitica
- `DailyOperationsDebtService`: resumen operativo (checking, POS, ficha).
- `ClientDebtService`: cobro transaccional.
- `ReporteModuloService`: agregacion analitica.
- **Gap / riesgo:** estos tres deben converger en definiciones de saldo; hoy `CustomerDebts` rompe la frontera.

### Comercial y bienestar
`ClientWellnessService` congela `ClienteMatricula|ClienteMembresia`. Frontera comercial/bienestar debe hacerse explicita con servicio compartido.

### Recursos ↔ Operaciones
`PosAlquilerReservaService` conecta POS con alquileres. Tres puntos de reserva (Recursos, POS, Bienestar/Ficha) requieren `RentalService` como unica fuente de escritura.

### Cliente legacy vs nuevo comercial
`cliente_membresias` aparece en ficha, bienestar y reportes. Debe quedar como legado de lectura/cobranza controlada con indicador de origen en UI.

### Multi-sucursal
`SucursalContext` filtra operacion, reportes y administracion. Todo agregador nuevo debe respetar sucursal activa.

---

## Roadmap global recomendado

> Actualizado 2026-08-27. Los items marcados `[x]` se verificaron directamente contra el codigo actual, no solo contra la intencion documentada.

### Fase 1. Consistencia de dominio
- [x] `DailyOperationsDebtService` para resumen de deuda operativa
- [x] Separacion sidebar Perfil vs Listado de clientes
- [x] `company-branches` en Super administracion
- [x] Reportes modulares con `ReporteModuloService`
- [x] Agregadores de perfil cliente (comercial, bienestar, CRM) — `ClienteCommercialProfileService`, `ClienteWellnessProfileService`, `ClienteCrmProfileService`
- [x] Vista analitica de cuentas por cobrar separada de POS — `ReporteCuentasPorCobrarLive`
- [x] Permiso `checking.ver`
- [x] Unificacion nomenclatura Operaciones / Operacion diaria

### Fase 2. Desacople de componentes grandes
- [ ] Fragmentar `POSLive` (~1.142 LOC) — **unico item de esta fase sin avance real; maxima prioridad de codigo actual**
- [x] Reducir `ClientePerfilLive` (bajo de ~1.153 a ~934 LOC) con agregadores
- [ ] Reducir `GestionNutricionalUnificadoLive` (~876 LOC, sin cambio) — **segunda prioridad de codigo**
- [x] Unificar puntos de **escritura** de reserva de alquileres (via `RentalService`); falta unificar la UI de entrada

### Fase 3. Navegacion y permisos
- [x] Reordenar sidebar por tareas reales (BioTime como grupo propio, backups visibles, `crm.reportes` visible)
- [x] Alinear sidebar analitica con centro de reportes (fuente compartida `ReporteCatalog`)
- [x] Exponer backups
- [x] Reclasificar BioTime (resuelto como grupo de sidebar propio, no como se planteo originalmente)
- [ ] Matriz unificada permisos CRM (INC-10) — pendiente
- [ ] Permisos efectivos visibles en administracion — servicio (`EffectivePermissionsService`) ya existe, falta la UI

### Fase 4. Observabilidad y reportabilidad
- [ ] Resumen operativo por modulo
- [ ] Servicios analiticos por dominio (`SalesAnalyticsService`, `ClientAnalyticsService`, `FinanceAnalyticsService`, `CajaAnalyticsService`)
- [ ] **Trazabilidad de cambios admin (`AuditLog` inerte, INC-11) — nuevo hallazgo, prioridad alta**
- [ ] Etiquetado origen legacy/importado en UI y reportes

---

## Orden sugerido de implementacion (re-priorizado 2026-08-27)
1. **Operaciones** — fragmentar `POSLive` (unico pendiente real de este modulo; el resto de Fase 1-3 de Operaciones ya cerro)
2. **Administracion** — instrumentar `AuditLog` en acciones criticas (INC-11, hallazgo nuevo) y exponer UI de permisos efectivos
3. **Comercial** — unificar permisos CRM (INC-10)
4. **Bienestar** — dividir `GestionNutricionalUnificadoLive` (sin avance desde junio)
5. **Transversal** — escribir `DebtParityTest.php` (T.2), unica pieza pendiente de la capa de deuda
6. **Recursos** — fortalecer `InventarioService`; consolidar UI de reservas (la escritura ya es unica)
7. **Plataforma** — etiquetado de registros importados vs nativos
8. **Analitica** — servicios analiticos dedicados (`SalesAnalyticsService` y afines)

---

## Criterios de exito
- Un mismo cliente muestra el mismo estado comercial, de bienestar y de deuda en cualquier modulo.
- Ningun reporte depende de un componente Livewire pensado para transaccion diaria — **logrado para cuentas por cobrar**.
- Ningun componente principal supera ~400 LOC sin justificacion; orquestan servicios, no consultan modelos directamente — **pendiente en `POSLive` y `GestionNutricionalUnificadoLive`**.
- Sidebar, breadcrumbs y documentacion usan la misma nomenclatura y reflejan el catalogo completo de rutas o derivan claramente a un hub — **logrado**.
- Legacy visible solo donde aporta compatibilidad, con indicador de origen, no como fuente activa de nuevas operaciones — **logrado para `cliente_membresias`; pendiente para datos importados**.
- Tres capas de deuda alineadas: resumen (`DailyOperationsDebtService`), transaccion (`ClientDebtService`), analitica (servicios dedicados) — **alineadas; falta el test de paridad (`DebtParityTest.php`)**.
- Toda accion critica administrativa queda auditada — **no logrado (INC-11): `AuditLog` existe pero no se usa.**
