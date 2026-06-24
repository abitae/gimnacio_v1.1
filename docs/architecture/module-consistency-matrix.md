# Matriz y Plan Maestro de Consistencia por Modulos

> **Ultima revision:** 2026-06-24  
> **Fuente de navegacion:** `resources/views/components/layouts/app/sidebar.blade.php` (unica fuente; no hay config externa)  
> **Rutas:** `routes/web.php` (monolitico, ~264 lineas)  
> **Inventario:** ~95 componentes Livewire · ~74 servicios · ~80 modelos  
> **Planes de mejora detallados:** [docs/plans/README.md](../plans/README.md)

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

| Indicador | Valor | Interpretacion |
| --- | --- | --- |
| Grupos sidebar | 8 (+ Inicio, Perfil) | Cobertura funcional amplia; nomenclatura interna inconsistente |
| Mega-componentes (>800 LOC) | 3 | `POSLive` (~1.202), `ClientePerfilLive` (~1.153), `GestionNutricionalUnificadoLive` (~875) |
| Agregadores planificados | 0/3 implementados | Perfil comercial/bienestar/CRM siguen como propuesta |
| Agregadores parciales activos | 2 | `DailyOperationsDebtService`, `ClientWellnessService` |
| Duplicidad operativo/analitico | 1 caso critico | `CustomerDebts` compartido entre POS y reportes |
| Rutas sin item sidebar | ~15+ | Reportes secundarios, backups, CRM reportes, cuotas, importaciones parcial |

**Nivel de consistencia global estimado:** medio-bajo. La capa de servicios avanza en operacion diaria y reportes modulares, pero la UI concentra demasiada logica en pocos componentes y el sidebar no refleja por completo el catalogo de rutas.

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

| Servicio planificado | Estado | Sustituto actual |
| --- | --- | --- |
| `ClienteCommercialProfileService` | No implementado | consultas directas en `ClientePerfilLive` + `ClienteMatriculaService` |
| `ClienteWellnessProfileService` | No implementado | `ClientWellnessService` (parcial, mezcla dominios) |
| `ClienteCrmProfileService` | No implementado | consultas CRM en ficha/perfil |
| `SalesAnalyticsService` / `FinanceAnalyticsService` | No implementado | `ReporteModuloService` (~732 LOC) centraliza todo |
| `DailyOperationsDebtService` | **Implementado** | usado en checking, POS y ficha cliente |
| `ClientDebtService` | **Implementado** | cobranza operativa en POS/creditos |

---

## Registro de inconsistencias detectadas

| ID | Tipo | Descripcion | Impacto | Accion recomendada |
| --- | --- | --- | --- | --- |
| INC-01 | Nomenclatura | Sidebar dice "Operaciones"; breadcrumbs/docs dicen "Operacion diaria" | Confusion en documentacion y permisos | Unificar label en sidebar, breadcrumbs y docs |
| INC-02 | Permisos | `checking.index` sin middleware de permiso | Cualquier usuario autenticado accede | Definir permiso `checking.ver` y alinear semilla |
| INC-03 | Operativo/Analitico | `reportes.cuentas-por-cobrar` = `POS\CustomerDebts` | Permisos distintos (`reporte.ver` vs `punto_venta.ver`); misma UI transaccional | Crear `ReporteCuentasPorCobrarLive` analitico |
| INC-04 | Navegacion | Cobros pendientes en Operaciones Y Cuentas por cobrar en Analitica apuntan al mismo componente | Duplicidad de entrada, expectativas distintas | Separar bandeja operativa de vista analitica |
| INC-05 | Clasificacion | BioTime en grupo Administracion; breadcrumbs en Operacion diaria | Dominio mal ubicado conceptualmente | Decidir si es integracion operativa o admin de dispositivos |
| INC-06 | Navegacion | `administracion.backups.index` sin enlace sidebar | Funcionalidad oculta para super admin | Agregar item o mover a Super administracion |
| INC-07 | Navegacion | Analitica sidebar: 5 items vs centro de reportes: 11 | Usuario no descubre reportes completos | Ampliar sidebar o eliminar duplicados del sidebar |
| INC-08 | Dominio | Congelamiento de planes en `ClientWellnessService` / bienestar | Frontera comercial/bienestar difusa | Extraer a servicio comercial compartido |
| INC-09 | Legacy | `cliente_membresias` sigue en ficha, bienestar y reportes | Riesgo de calculos divergentes | Marcar origen legacy en servicios y UI |
| INC-10 | Permisos CRM | `crm.mensajes` usa `crm_mensaje.ver` fuera del grupo `crm.ver` | Gestion de permisos fragmentada | Documentar matriz CRM unificada |

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

### Hallazgos
- `DailyOperationsDebtService` ya unifica resumen de deuda para checking, POS y ficha cliente: avance positivo de consistencia.
- `ClientDebtService` concentra la logica transaccional de cobro de deudas; separacion correcta respecto al agregador de resumen.
- `POSLive` es hoy el componente mas grande del sistema y concentra demasiados flujos de venta.
- Checking no tiene permiso granular; cualquier usuario autenticado con contexto de sucursal puede acceder.
- Cobros pendientes (`pos.cuentas-por-cobrar`) comparte componente con analitica (`reportes.cuentas-por-cobrar`).

### Riesgos actuales
- Cambios en POS pueden afectar ventas, credito, reservas y cupones simultaneamente.
- Divergencia futura entre saldos mostrados en POS, ficha cliente y reportes si no comparten agregadores.
- BioTime (integracion de acceso biometrico) vive fuera de este grupo pero impacta checking.

### Plan de mejora
1. Fragmentar `POSLive` en sub-componentes o servicios de orquestacion por tipo de venta.
2. Mantener `DailyOperationsDebtService` como unica fuente de resumen de deuda operativa.
3. Reservar `CustomerDebts` exclusivamente para operacion; crear vista analitica separada.
4. Agregar permiso `checking.ver` y alinear middleware con sidebar.
5. Evaluar mover BioTime al grupo Operaciones o documentar como integracion transversal.

### Prioridad recomendada
- Fase 1: desacoplar `POSLive` y separar reporte de cuentas por cobrar.
- Fase 2: permisos de checking y trazabilidad de caja/ventas por sucursal.
- Fase 3: integracion coherente BioTime ↔ checking.

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

### Hallazgos
- **Avance navegacion:** sidebar ya separa "Perfil de cliente" y "Listado de clientes" (Fase 3 parcialmente lograda en UI).
- `ClienteLive` esta bien acotado como listado CRUD y usa `ClienteService`.
- `ClientePerfilLive` funciona como ficha 360 real, pero concentra comercial, cobranza, bienestar, reservas, fidelizacion y checking.
- La ficha consume `DailyOperationsDebtService` para deudas: correcto.
- Los agregadores planificados (`ClienteCommercialProfileService`, etc.) **no estan implementados**.
- El perfil mezcla acciones operativas de otros modulos: cobrar matriculas, cuotas, reservas, congelar planes, fidelizacion, ingreso/salida checking.
- Trait `ManagesClienteCrudAndPhoto` (~497 LOC) indica extraccion parcial previa, pero el componente principal sigue creciendo.

### Riesgos actuales
- La ficha del cliente puede seguir creciendo hasta convertirse en un "super modulo".
- Si cada tab calcula estado comercial o bienestar por su cuenta, se rompe la consistencia.
- `ClienteService::checkRelations()` confirma que `Cliente` es el nodo mas conectado del sistema.

### Plan de mejora
1. Mantener `ClienteLive` como modulo de listado y alta/edicion basica.
2. Consolidar `ClientePerfilLive` como ficha 360 shell, extrayendo subdominios a servicios:
   - `ClienteCommercialProfileService` *(pendiente)*
   - `ClienteWellnessProfileService` *(pendiente)*
   - `ClienteCrmProfileService` *(pendiente)*
3. Dejar que la ficha muestre estado y accesos rapidos, pero no concentre toda la logica transaccional.
4. Mover acciones de cobranza pesada hacia `Operaciones > Cobros pendientes` y dejar en ficha atajos contextuales.
5. Separar visualmente catalogo comercial (`membresias`, `clases`, `matriculas`) del bloque de ficha — parcialmente hecho.
6. Una sola carga de contexto por cliente via agregador, no consultas independientes por tab.

### Prioridad recomendada
- Fase 1: implementar agregadores de contexto y reducir consultas directas.
- Fase 2: convertir ficha 360 en shell con tabs respaldadas por servicios especificos.
- Fase 3: completar separacion navegacion ficha vs catalogo comercial.

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

### Hallazgos
- Modulo CRM maduro: 18 componentes Livewire con formularios dedicados.
- `CrmPipelineLive` bien enfocado como vista Kanban.
- `LeadService` concentra filtros, stages y asignacion.
- Conversion lead → cliente separada en `ConvertLeadToClientService`.
- Dos ramas: CRM relacional y activacion/promocion (mensajes, cupones, renovacion).
- `crm.mensajes` usa permiso `crm_mensaje.ver` separado del grupo principal.
- `crm.reportes` existe como ruta pero no aparece en sidebar.

### Riesgos actuales
- Incoherencia potencial entre estado del lead, etapa del pipeline y actividad comercial real.
- Desalineacion posible entre request, middleware y permiso en creacion/edicion de leads.
- Conversion lead → cliente puede perder trazabilidad si no se estandariza.
- Cupones integrados en POS pero con trazabilidad limitada fuera de venta.

### Plan de mejora
1. Formalizar embudo: captacion → contacto → oportunidad → conversion → renovacion.
2. Unificar permisos CRM: ver, crear, editar, convertir, mensajeria.
3. Crear `CrmOperationalSummaryService` para KPIs consistentes.
4. Asegurar trazabilidad completa en `ConvertLeadToClientService`.
5. Separar en UI: CRM / Retencion / Promociones.
6. Integrar cupones con POS/comercial de forma trazable.

### Prioridad recomendada
- Fase 1: alinear permisos y trazabilidad de conversion.
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

### Hallazgos
- `ProductoLive` + `ProductoService` razonablemente encapsulados.
- Inventario existe (`InventarioService`, `MovimientoInventario`) pero es delgado (~101 LOC).
- Alquileres forman subdominio coherente con calendario, reservas y reporte.
- Reservas se crean tambien desde POS (`PosAlquilerReservaService`), bienestar y ficha cliente: triple punto de entrada.
- Servicios externos sin la misma profundidad operativa que productos/alquileres.

### Riesgos actuales
- POS vende productos sin capa fuerte de inventario → stock fragil.
- Alquileres duplican logica entre cliente, bienestar, POS y recursos.
- Tres puntos de creacion de reservas aumentan riesgo de inconsistencia.

### Plan de mejora
1. Dividir `Recursos` en: catalogo comercial, inventario, espacios/alquileres.
2. Fortalecer `InventarioService` como fuente de movimientos.
3. Bandeja operativa de alquileres: reservas del dia, confirmaciones, pagos pendientes.
4. Reservas creadas principalmente desde `Recursos`; demas modulos solo atajos.
5. Revisar trazabilidad comercial de `ServicioExterno`.

### Prioridad recomendada
- Fase 1: consolidar alquileres y unificar puntos de reserva.
- Fase 2: robustecer inventario y movimientos.
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

### Hallazgos
- `ReporteModuloService` centraliza agregacion: buena base arquitectonica (~732 LOC).
- Reportes modulares dedicados existen para la mayoria de dominios.
- **Problema persistente:** `reportes.cuentas-por-cobrar` reutiliza `POS\CustomerDebts` con permiso `reporte.ver` en lugar de vista analitica.
- Sidebar de Analitica muestra solo 5 items; el centro expone 11 — descubrimiento incompleto desde navegacion lateral.
- Compatibilidad legacy `cliente_membresias` vs `cliente_matriculas` sigue presente en reportes de clientes.

### Riesgos actuales
- Analitica puede devolver resultados distintos a operacion si agregan saldos por separado.
- Reutilizar pantalla operativa mezcla permisos, filtros y expectativas de uso.
- Usuario con solo `reporte.ver` accede a UI transaccional de cobro via reporte.

### Plan de mejora
1. Separar estrictamente `Operacion` de `Analitica`.
2. Crear servicios agregadores dedicados:
   - `SalesAnalyticsService`
   - `ClientAnalyticsService`
   - `FinanceAnalyticsService`
   - `CajaAnalyticsService`
3. Reemplazar `CustomerDebts` en reportes por `ReporteCuentasPorCobrarLive` analitico (solo lectura + export).
4. Reportes financieros deben consumir los mismos agregadores que operacion diaria.
5. Compatibilidad legacy solo a nivel de servicio con etiquetas de origen.
6. Alinear sidebar con centro de reportes o eliminar items redundantes del sidebar.

### Prioridad recomendada
- Fase 1: desacoplar `CustomerDebts` del modulo analitico.
- Fase 2: unificar agregadores de saldos, clientes y ventas con operacion.
- Fase 3: enriquecer exportaciones y trazabilidad por sucursal.

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

### Hallazgos
- **Avance:** `company-branches` ya expuesto en Super administracion (antes oculto).
- `UsuarioLive` restringe por sucursal y bloquea gestion de super admins especiales.
- Administracion fragmentada en: seguridad, personal, integraciones, configuracion financiera, soporte tecnico.
- BioTime clasificado en Administracion pero breadcrumbs lo ubican en Operacion diaria.
- Backups con servicios robustos (~1.600 LOC combinados) pero sin acceso sidebar.
- Modulo Imports (27 servicios) es critico para migracion legacy pero aislado a super admin.

### Riesgos actuales
- Seguridad depende de permisos + sucursal activa + roles; UX no refleja permisos efectivos claramente.
- Backups sin marco visible de auditoria/restauracion en navegacion.
- BioTime mal clasificado genera confusion operativa vs administrativa.

### Plan de mejora
1. Dividir `Administracion` en: seguridad/accesos, personal, integraciones, config financiera, soporte tecnico.
2. Pantalla de lectura: usuario → rol → sucursales → permisos efectivos.
3. Exponer backups en Super administracion con auditoria.
4. Reclasificar BioTime (Operaciones vs Administracion) y alinear breadcrumbs.
5. Auditar permisos sembrados vs middleware real.
6. Fortalecer `AuditLog` en cambios de usuarios, roles, metodos de pago y backups.

### Prioridad recomendada
- Fase 1: ordenar navegacion y exponer backups; reclasificar BioTime.
- Fase 2: pantalla permisos efectivos y config empresa/sucursales.
- Fase 3: auditoria y administracion avanzada.

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
- Datos importados pueden coexistir con legacy activo si no se marca origen.
- Operaciones post-importacion pueden calcular estados distintos segun origen del registro.

### Plan de mejora
1. Etiquetar registros importados vs nativos en servicios agregadores.
2. Checklist post-importacion en dashboard.
3. Documentar mapeo legacy → modelo nuevo por entidad.

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

### Fase 1. Consistencia de dominio (en curso)
- [x] `DailyOperationsDebtService` para resumen de deuda operativa
- [x] Separacion sidebar Perfil vs Listado de clientes
- [x] `company-branches` en Super administracion
- [x] Reportes modulares con `ReporteModuloService`
- [ ] Agregadores de perfil cliente (comercial, bienestar, CRM)
- [ ] Vista analitica de cuentas por cobrar separada de POS
- [ ] Permiso `checking.ver`
- [ ] Unificacion nomenclatura Operaciones / Operacion diaria

### Fase 2. Desacople de componentes grandes
- [ ] Fragmentar `POSLive` (~1.202 LOC)
- [ ] Reducir `ClientePerfilLive` (~1.153 LOC) con agregadores
- [ ] Reducir `GestionNutricionalUnificadoLive` (~875 LOC)
- [ ] Unificar puntos de reserva de alquileres

### Fase 3. Navegacion y permisos
- [ ] Reordenar sidebar por tareas reales
- [ ] Alinear sidebar analitica con centro de reportes
- [ ] Exponer backups; reclasificar BioTime
- [ ] Matriz unificada permisos CRM
- [ ] Permisos efectivos visibles en administracion

### Fase 4. Observabilidad y reportabilidad
- [ ] Resumen operativo por modulo
- [ ] Servicios analiticos por dominio
- [ ] Trazabilidad integraciones, cambios admin y procesos comerciales
- [ ] Etiquetado origen legacy/importado en UI y reportes

---

## Orden sugerido de implementacion
1. **Operaciones** — desacoplar POS y separar cobranza analitica (impacto transversal inmediato)
2. **Clientes** — agregadores de ficha 360
3. **Analitica** — eliminar dependencia de `CustomerDebts`
4. **Bienestar** — dividir shell unificado
5. **Comercial** — permisos y trazabilidad conversion
6. **Recursos** — inventario y reservas unificadas
7. **Administracion / Plataforma** — navegacion, BioTime, backups, auditoria

---

## Criterios de exito
- Un mismo cliente muestra el mismo estado comercial, de bienestar y de deuda en cualquier modulo.
- Ningun reporte depende de un componente Livewire pensado para transaccion diaria.
- Ningun componente principal supera ~400 LOC sin justificacion; orquestan servicios, no consultan modelos directamente.
- Sidebar, breadcrumbs y documentacion usan la misma nomenclatura y reflejan el catalogo completo de rutas o derivan claramente a un hub.
- Legacy visible solo donde aporta compatibilidad, con indicador de origen, no como fuente activa de nuevas operaciones.
- Tres capas de deuda alineadas: resumen (`DailyOperationsDebtService`), transaccion (`ClientDebtService`), analitica (servicios dedicados).
