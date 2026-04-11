# Matriz y Plan Maestro de Consistencia por Modulos

## Objetivo
Centralizar un analisis profundo de todos los modulos visibles desde el sidebar para alinear:

- navegacion y agrupacion funcional,
- permisos y middleware,
- componentes Livewire y servicios eje,
- modelos y relaciones dominantes,
- fuentes de verdad,
- riesgos de acoplamiento,
- plan de mejora por utilidad operativa y consistencia del sistema.

## Mapa funcional del sidebar
| Grupo sidebar | Alcance funcional real | Rutas eje | Observacion |
| --- | --- | --- | --- |
| Operacion diaria | acceso, caja, ventas y cobranza | `checking.*`, `cajas.*`, `pos.*` | Ya intervenido en fase operativa inicial |
| Clientes | ficha, listado, planes, clases, cuotas | `clientes.*`, `membresias.*`, `cliente-matriculas.*`, `clases.*` | Mezcla catalogo comercial y vista 360 del cliente |
| Bienestar | salud, nutricion, citas, objetivos, rutinas, progreso | `gestion-nutricional.*`, `ejercicios.*`, `rutinas-base.*`, `clientes.rutinas.*`, `clientes.sesiones.*`, `ejercicios-rutinas.*` | Hoy conviven bienestar clinico y entrenamiento |
| Comercial | pipeline CRM, oportunidades, campañas, mensajes, cupones | `crm.*`, `cupones.*` | Dominio correcto, pero con riesgo en conversion y permisos |
| Recursos | catalogos y alquileres | `categorias-productos.*`, `productos.*`, `servicios.*`, `rentals.*` | Mezcla inventario, servicios y espacios |
| Analitica | centro de reportes y exportaciones | `reportes.*` | Todavia reutiliza pantallas operativas como destinos |
| Administracion | usuarios, roles, metodos de pago, empleados, backups | `employees.*`, `payment-methods.*`, `usuarios.*`, `roles.*`, `administracion.*` | Faltan bloques de configuracion mas claros |
| Biotime | integracion externa de control | `biotime.*` | Modulo de soporte tecnico, no de uso operativo diario |

## Principios transversales recomendados
1. Cada modulo debe tener una fuente de verdad principal y solo consumir legado en compatibilidad de lectura o migracion.
2. Los componentes Livewire deben orquestar; la logica de negocio y agregacion debe vivir en servicios.
3. La ficha del cliente debe consumir agregadores compartidos, no recalcular estados segun modulo.
4. Reportes no deben apuntar a componentes operativos reutilizados como si fueran analitica.
5. Permisos de sidebar, middleware, policies y requests deben expresar el mismo contrato funcional.
6. Los modulos deben separar claramente: operacion, configuracion, historial y analitica.

## Fuentes de verdad recomendadas
- Clientes: `clientes`
- Comercial de planes: `cliente_matriculas`
- Legacy comercial: `cliente_membresias` solo lectura y cobranza controlada
- Caja y ventas: `cajas`, `caja_movimientos`, `ventas`, `pagos`, `client_debts`
- Bienestar clinico: `health_records`, `evaluacion_medidas_nutricion`, `seguimiento_nutricion`, `citas`
- Entrenamiento: `routine_templates`, `client_routines`, `workout_sessions`
- CRM: `crm_leads`, `deals`, `crm_tasks`, `crm_activities`, `campaigns`
- Recursos: `productos`, `servicios_externos`, `rentable_spaces`, `rentals`
- Administracion: `users`, `roles`, `payment_methods`, `employees`, `employee_attendances`
- Integracion: `biotime_settings`, `biotime_access_logs`, `integration_error_logs`

## Matriz ejecutiva
| Modulo | Livewire eje | Servicio eje | Modelos eje | Fuente de verdad | Riesgo principal | Prioridad |
| --- | --- | --- | --- | --- | --- | --- |
| Clientes | `ClienteLive`, `ClientePerfilLive` | `ClienteService`, `ClientEnrollmentService`, `ClienteMatriculaService` | `Cliente`, `ClienteMatricula`, `Pago`, `Asistencia` | `clientes` + agregadores de contexto | `ClientePerfilLive` concentra demasiados dominios | Alta |
| Bienestar | `GestionNutricionalUnificadoLive` | `ClientWellnessService`, `SeguimientoNutricionService`, `EvaluacionMedidasNutricionService`, `CitaService` | `HealthRecord`, `EvaluacionMedidasNutricion`, `SeguimientoNutricion`, `Cita`, `ClientRoutine`, `Rental` | bienestar + entrenamiento | componente unificado demasiado grande y con consultas directas | Alta |
| Comercial | `CrmPipelineLive`, `LeadsListLive`, `LeadDetailLive`, `RenewalReactivacionLive` | `LeadService`, `DealService`, `ConvertLeadToClientService`, `RenewalReactivationService` | `Lead`, `Deal`, `CrmTask`, `CrmActivity`, `Campaign` | `crm_leads` | conversion, permisos y trazabilidad dispersa | Alta |
| Recursos | `ProductoLive`, `ServicioExternoLive`, `Rentals/*` | `ProductoService`, `ServicioExternoService` | `Producto`, `CategoriaProducto`, `ServicioExterno`, `RentableSpace`, `Rental` | catalogos y alquileres | catalogos e inventario con poca capa de movimientos | Media |
| Analitica | `ReporteIndexLive` y reportes derivados | `ReporteModuloService`, `ReporteService` | agregaciones multi-modulo | servicios agregadores | mezcla operativo/analitico y dependencia legacy | Alta |
| Administracion | `UsuarioLive`, `RolLive`, `Employees/*`, `PaymentMethods/Index` | servicios especificos + `SucursalContext` | `User`, `Role`, `PaymentMethod`, `Employee`, `EmployeeAttendance` | administracion del sistema | gestion fragmentada de seguridad, personal y configuracion | Media |
| Biotime | `BiotimeIndexLive`, `BiotimeSyncLive`, `BiotimeConfigLive` | `BiotimeApiClient` | `BiotimeSetting`, `BiotimeAccessLog`, `IntegrationErrorLog` | integracion externa | falta observabilidad y separacion entre configuracion y operaciones | Media |

## 1. Clientes
### Alcance actual
El modulo `Clientes` agrupa:

- listado y busqueda general de clientes,
- ficha 360 del cliente,
- catalogo de membresias,
- matriculas de clientes,
- clases,
- acceso indirecto a cuotas.

### Componentes y servicios eje
- Livewire:
  - `app/Livewire/Clientes/ClienteLive.php`
  - `app/Livewire/Clientes/ClientePerfilLive.php`
  - `app/Livewire/Membresias/MembresiaLive.php`
  - `app/Livewire/ClienteMatriculas/ClienteMatriculaLive.php`
  - `app/Livewire/Clases/ClaseLive.php`
- Servicios:
  - `app/Services/ClienteService.php`
  - `app/Services/ClientEnrollmentService.php`
  - `app/Services/ClienteMatriculaService.php`
  - `app/Services/EnrollmentInstallmentService.php`
  - `app/Services/DailyOperationsDebtService.php`

### Modelos y relaciones dominantes
- `Cliente -> clienteMatriculas, clienteMembresias, pagos, asistencias, healthRecord, citas, seguimientosNutricion, clientRoutines, rentals, crmTags, crmTasks, crmActivities`
- `ClienteMatricula -> cliente, membresia|clase, pagos, enrollmentInstallments`
- `Pago -> cliente, clienteMatricula|clienteMembresia|clientDebt`

### Hallazgos
- `ClienteLive` esta bien acotado como listado CRUD y usa `ClienteService`.
- `ClientePerfilLive` funciona como ficha 360 real, pero hoy concentra comercial, cobranza, bienestar, reservas y fidelizacion.
- La ficha ya consume `DailyOperationsDebtService`, lo cual es correcto, pero todavia sigue resolviendo mucha informacion con consultas directas.
- El perfil mezcla acciones operativas de otros modulos:
  - cobrar matriculas,
  - ver cuotas,
  - crear reservas,
  - congelar planes,
  - registrar fidelizacion.
- El sidebar mezcla en el mismo grupo:
  - la ficha del cliente,
  - catalogos comerciales (`membresias`, `clases`),
  - gestion de matriculas.

### Riesgos actuales
- La ficha del cliente puede seguir creciendo hasta convertirse en un “super modulo”.
- Si cada tab calcula estado comercial o bienestar por su cuenta, se rompe la consistencia.
- `ClienteService::checkRelations()` ya deja ver que `Cliente` es el nodo mas conectado del sistema; cualquier cambio sin agregadores aumenta el acoplamiento.

### Plan de mejora
1. Mantener `ClienteLive` como modulo de listado y alta/edicion basica.
2. Consolidar `ClientePerfilLive` como ficha 360, pero extrayendo subdominios a servicios/agregadores:
   - `ClienteCommercialProfileService`
   - `ClienteWellnessProfileService`
   - `ClienteCrmProfileService`
3. Dejar que la ficha muestre estado y accesos rapidos, pero no concentre toda la logica transaccional.
4. Mover acciones de cobranza pesada hacia `Operacion diaria > Cobranza` y dejar en ficha atajos contextuales.
5. Separar visualmente dentro del sidebar:
   - `Perfil de cliente` y `Listado de clientes`
   - `Planes y clases` como bloque de catalogo/comercial
6. Hacer que la ficha consuma una sola carga de contexto por cliente, no consultas independientes por tab.

### Prioridad recomendada
- Fase 1: extraer agregadores de contexto y reducir consultas directas.
- Fase 2: convertir ficha 360 en shell con tabs respaldadas por servicios especificos.
- Fase 3: separar navegacion entre ficha, catalogo comercial y administracion de planes.

## 2. Bienestar
### Alcance actual
El modulo `Bienestar` mezcla:

- ficha de salud,
- evaluaciones corporales,
- seguimientos nutricionales,
- citas,
- objetivos,
- rutinas,
- sesiones de entrenamiento,
- progreso/cumplimiento,
- congelamiento de planes,
- reservas de espacios.

### Componentes y servicios eje
- Livewire:
  - `app/Livewire/GestionNutricional/GestionNutricionalUnificadoLive.php`
  - `app/Livewire/GestionNutricional/CalendarioCitasLive.php`
  - `app/Livewire/Nutrition/*`
  - `app/Livewire/Exercises/*`
  - `app/Livewire/Routines/Templates/*`
  - `app/Livewire/Clients/Routines/*`
  - `app/Livewire/Clients/Workouts/*`
  - `app/Livewire/Reports/ProgressByExercise.php`
  - `app/Livewire/Reports/Compliance.php`
- Servicios:
  - `app/Services/ClientWellnessService.php`
  - `app/Services/EvaluacionMedidasNutricionService.php`
  - `app/Services/SeguimientoNutricionService.php`
  - `app/Services/CitaService.php`
  - `app/Services/ClientRoutineService.php`
  - `app/Services/RoutineTemplateService.php`

### Modelos y relaciones dominantes
- `Cliente -> healthRecord, evaluacionesMedidasNutricion, seguimientosNutricion, citas, clientRoutines`
- `ClientRoutine -> cliente, trainer, routineTemplate, days`
- `WorkoutSession -> clientRoutine, exercises, sets`
- `Rental -> cliente, rentableSpace`
- `ClienteMatricula|ClienteMembresia` tambien aparecen por congelamiento

### Hallazgos
- `GestionNutricionalUnificadoLive` es hoy uno de los componentes mas cargados del sistema.
- Mezcla bienestar clinico, entrenamiento y operaciones anexas como reservas y congelamientos.
- Usa servicios correctos en varias partes, pero en `render()` sigue resolviendo usuarios, citas y recursos de forma directa.
- `ClientWellnessService` ya actua como agregador parcial de gestion, pero tambien mezcla:
  - congelamiento de planes comerciales,
  - reservas,
  - timeline,
  - overview de rutinas.
- El modulo tiene una tension clara entre dos dominios distintos:
  - salud/nutricion,
  - entrenamiento/rutinas.

### Riesgos actuales
- Cualquier cambio pequeno en bienestar puede romper entrenamiento o reservas.
- Congelamiento de planes vive en bienestar aunque afecta el dominio comercial.
- Las rutas de objetivos, rutinas, progreso y cumplimiento estan dispersas entre modulos conceptuales diferentes.

### Plan de mejora
1. Separar conceptualmente `Bienestar` en dos subflujos:
   - `Salud y nutricion`
   - `Entrenamiento`
2. Mantener `GestionNutricionalUnificadoLive` solo como shell temporal y extraer:
   - `ClienteHealthHubService`
   - `ClienteNutritionService`
   - `ClienteTrainingOverviewService`
3. Mover congelamiento de planes a una capa compartida de comercial/bienestar, no dejarlo acoplado a una pantalla unificada.
4. Reubicar reservas de espacios bajo `Recursos` como operacion principal y dejarlas en bienestar solo como atajo desde cliente.
5. Centralizar la linea de tiempo del cliente como servicio transversal reutilizable desde ficha y bienestar.
6. Definir `health_records` y tablas de seguimiento como fuente principal del subdominio de salud.

### Prioridad recomendada
- Fase 1: dividir servicios de `GestionNutricionalUnificadoLive`.
- Fase 2: separar salud/nutricion de entrenamiento en UI y rutas.
- Fase 3: mover reservas y congelamientos a dominios mas coherentes.

## 3. Comercial
### Alcance actual
El modulo `Comercial` agrupa:

- pipeline,
- leads,
- tareas CRM,
- oportunidades,
- campañas,
- etiquetas,
- renovacion/reactivacion,
- mensajes WhatsApp,
- cupones.

### Componentes y servicios eje
- Livewire:
  - `app/Livewire/Crm/CrmPipelineLive.php`
  - `app/Livewire/Crm/LeadsListLive.php`
  - `app/Livewire/Crm/LeadDetailLive.php`
  - `app/Livewire/Crm/CrmTasksLive.php`
  - `app/Livewire/Crm/CrmDealsLive.php`
  - `app/Livewire/Crm/CrmCampaignsLive.php`
  - `app/Livewire/Crm/RenewalReactivacionLive.php`
  - `app/Livewire/Crm/MensajesLive.php`
  - `app/Livewire/Coupons/*`
- Servicios:
  - `app/Services/Crm/LeadService.php`
  - `app/Services/Crm/DealService.php`
  - `app/Services/Crm/CrmTaskService.php`
  - `app/Services/Crm/CrmActivityService.php`
  - `app/Services/Crm/CampaignService.php`
  - `app/Services/Crm/ConvertLeadToClientService.php`
  - `app/Services/Crm/RenewalReactivationService.php`
  - `app/Services/CrmMensajeService.php`

### Modelos y relaciones dominantes
- `Lead -> stage, assignedTo, tags, deals, activities, tasks, cliente`
- `Deal -> lead, cliente, membresia`
- `CrmTask -> lead|cliente`
- `Campaign -> targets, activities`
- `Coupon -> usages`

### Hallazgos
- `CrmPipelineLive` esta bien enfocado como vista Kanban.
- `LeadService` ya concentra filtros, stages y asignacion, lo cual es una buena base.
- La conversion de lead a cliente esta separada en servicio, lo cual es correcto arquitectonicamente.
- El modulo comercial tiene dos ramas:
  - CRM relacional,
  - activacion/promocion (`mensajes`, `cupones`, `renovacion-reactivacion`).
- La ruta `crm.mensajes` usa permiso distinto (`crm_mensaje.ver`) y se sale del grupo principal, lo cual aumenta friccion de permisos.

### Riesgos actuales
- Riesgo de incoherencia entre estados del lead, etapa del pipeline y actividad comercial real.
- Posible desalineacion entre request, middleware y permiso usado en creacion/edicion de leads.
- La conversion lead -> cliente puede duplicar informacion o perder trazabilidad si no se estandariza el proceso.

### Plan de mejora
1. Formalizar el flujo comercial como embudo:
   - captacion,
   - contacto,
   - oportunidad,
   - conversion,
   - renovacion/reactivacion.
2. Unificar permisos del CRM:
   - ver,
   - crear,
   - editar,
   - convertir,
   - mensajeria.
3. Crear un `CrmOperationalSummaryService` para que pipeline, detalle, campañas y renovacion consuman KPIs y estados consistentes.
4. Asegurar que `ConvertLeadToClientService` deje trazabilidad completa:
   - lead origen,
   - cliente creado,
   - deal relacionado,
   - usuario responsable.
5. Separar claramente en UI:
   - `CRM`
   - `Retencion y fidelizacion`
   - `Promociones`
6. Integrar cupones con POS/comercial de forma trazable, no solo como catalogo independiente.

### Prioridad recomendada
- Fase 1: alinear permisos y trazabilidad de conversion.
- Fase 2: consolidar retencion/reactivacion con CRM.
- Fase 3: integrar promociones/cupones con ventas y campañas.

## 4. Recursos
### Alcance actual
El modulo `Recursos` contiene:

- categorias de productos,
- productos,
- servicios,
- espacios,
- calendario de alquileres,
- ingresos por alquiler.

### Componentes y servicios eje
- Livewire:
  - `app/Livewire/Categorias/CategoriaProductoLive.php`
  - `app/Livewire/Productos/ProductoLive.php`
  - `app/Livewire/Servicios/ServicioExternoLive.php`
  - `app/Livewire/Rentals/Spaces/Index.php`
  - `app/Livewire/Rentals/Calendar/Index.php`
  - `app/Livewire/Rentals/Bookings/*`
  - `app/Livewire/Rentals/Report.php`
- Servicios:
  - `app/Services/ProductoService.php`
  - `app/Services/ServicioExternoService.php`
  - `app/Services/InventarioService.php`
  - soporte parcial desde `ClientWellnessService` para reservas

### Modelos y relaciones dominantes
- `Producto -> categoria, movimientosInventario`
- `ServicioExterno`
- `RentableSpace -> rates, rentals`
- `Rental -> rentableSpace, cliente, rentalPayments`

### Hallazgos
- `ProductoLive` y `ProductoService` estan razonablemente encapsulados para CRUD.
- Sin embargo, el dominio de inventario todavia se ve mas como catalogo que como sistema de stock/movimientos.
- Alquileres forman un subdominio propio con calendario, reservas y reporte, pero parte de esa logica se reutiliza desde bienestar.
- El modulo mezcla recursos vendibles y recursos reservables.

### Riesgos actuales
- Si POS vende productos sin una capa fuerte de inventario, la consistencia de stock queda fragil.
- Alquileres puede terminar duplicando logica entre cliente, bienestar y recursos.
- Servicios externos no parecen integrados a una experiencia operativa completa similar a productos/alquileres.

### Plan de mejora
1. Dividir `Recursos` en tres capacidades:
   - catalogo comercial,
   - inventario,
   - espacios y alquileres.
2. Fortalecer `InventarioService` como fuente de movimientos y no solo dejar el stock en el CRUD de producto.
3. Crear una bandeja operativa para alquileres:
   - reservas del dia,
   - proximas confirmaciones,
   - pagos pendientes,
   - espacios ocupados/disponibles.
4. Hacer que reservas de espacios se creen y consulten principalmente desde `Recursos`, y desde cliente/bienestar solo por atajo contextual.
5. Revisar si `ServicioExterno` debe tener la misma trazabilidad comercial y analitica que `Producto`.

### Prioridad recomendada
- Fase 1: consolidar alquileres como submodulo coherente.
- Fase 2: robustecer inventario y movimientos.
- Fase 3: alinear catalogos vendibles bajo una misma experiencia.

## 5. Analitica
### Alcance actual
El modulo `Analitica` agrupa:

- centro de reportes,
- reportes de clientes,
- financiero,
- cuentas por cobrar,
- cuotas vencidas,
- ventas,
- matriculas,
- cajas,
- productos/servicios,
- gimnasio,
- exportaciones PDF/Excel.

### Componentes y servicios eje
- Livewire:
  - `app/Livewire/Reportes/ReporteIndexLive.php`
  - `app/Livewire/Reportes/*`
  - hoy `reportes.cuentas-por-cobrar` reutiliza `app/Livewire/POS/CustomerDebts.php`
- Servicios:
  - `app/Services/ReporteModuloService.php`
  - `app/Services/ReporteService.php`
  - `app/Services/ReporteModuloPdfService.php`

### Modelos y relaciones dominantes
- Multi-modulo:
  - `Venta`, `VentaItem`
  - `Pago`
  - `Cliente`, `ClienteMatricula`, `ClienteMembresia`
  - `Caja`
  - `Producto`, `ServicioExterno`
  - `Asistencia`, `Cita`

### Hallazgos
- `ReporteIndexLive` es solo un contenedor de navegacion.
- `ReporteModuloService` concentra bastante logica de agregacion, lo cual es positivo.
- Sin embargo, varios reportes siguen mezclando:
  - datos legacy y nuevos,
  - logica operativa con analitica,
  - componentes de reportes con componentes de operacion.
- El caso mas visible es `reportes.cuentas-por-cobrar`, que reutiliza `POS\CustomerDebts` en vez de una vista analitica dedicada.

### Riesgos actuales
- La analitica puede devolver resultados distintos a operacion si cada una agrega saldos por su cuenta.
- Los reportes de clientes siguen teniendo compatibilidad compleja entre `cliente_membresias` y `cliente_matriculas`.
- Si se siguen reutilizando pantallas operativas como reportes, se mezclan permisos, filtros y expectativas de uso.

### Plan de mejora
1. Separar estrictamente `Operacion` de `Analitica`.
2. Crear servicios agregadores dedicados por dominio:
   - `SalesAnalyticsService`
   - `ClientAnalyticsService`
   - `FinanceAnalyticsService`
   - `CajaAnalyticsService`
3. Reemplazar vistas operativas reutilizadas por reportes dedicados con filtros, exportacion y permisos propios.
4. Hacer que los reportes financieros y de cuentas por cobrar consuman los mismos agregadores usados en operacion diaria.
5. Mantener compatibilidad con legacy solo a nivel de servicio y con etiquetas explicitas de origen.
6. Consolidar el centro de reportes como entrada unica y no como simple lista de links.

### Prioridad recomendada
- Fase 1: desacoplar reportes de componentes operativos.
- Fase 2: unificar agregadores de saldos, clientes y ventas.
- Fase 3: enriquecer exportaciones y trazabilidad por sucursal.

## 6. Administracion
### Alcance actual
El modulo `Administracion` agrupa:

- empleados,
- asistencia del personal,
- metodos de pago,
- usuarios,
- roles,
- backups.

### Componentes y servicios eje
- Livewire:
  - `app/Livewire/Usuarios/UsuarioLive.php`
  - `app/Livewire/Roles/RolLive.php`
  - `app/Livewire/Employees/*`
  - `app/Livewire/Settings/PaymentMethods/Index.php`
  - `app/Livewire/Administracion/DatabaseBackupLive.php`
- Servicios:
  - `SucursalContext`
  - servicios administrativos especificos
  - `DatabaseBackupService`, `DatabaseRestoreService`

### Modelos y relaciones dominantes
- `User -> roles, sucursales`
- `Employee -> attendances`
- `PaymentMethod`
- `Role`
- `Sucursal`

### Hallazgos
- `UsuarioLive` maneja correctamente restriccion por sucursal y bloquea gestion de super admins especiales desde el modulo comun.
- La administracion esta funcionalmente fragmentada:
  - seguridad y accesos,
  - personal,
  - configuracion,
  - soporte tecnico.
- `company-branches` existe pero no aparece en el sidebar principal; eso sugiere una administracion oculta o incompleta.

### Riesgos actuales
- La seguridad del sistema depende de permisos, sucursal activa y roles, pero la UX no refleja claramente esas diferencias.
- Usuarios, roles y sucursales forman un subdominio de seguridad que deberia estar mucho mas cohesionado.
- Backups se exponen como modulo administrativo sin un marco claro de auditoria y restauracion controlada.

### Plan de mejora
1. Dividir `Administracion` en:
   - seguridad y accesos,
   - personal,
   - configuracion financiera,
   - soporte tecnico.
2. Consolidar la relacion `usuario -> rol -> sucursales -> permisos efectivos` en una pantalla de lectura clara.
3. Exponer empresa/sucursales bajo una seccion administrativa real para super admins.
4. Revisar que los permisos sembrados sean los realmente usados por middleware y componentes.
5. Fortalecer auditoria de cambios en usuarios, roles, metodos de pago y backups.

### Prioridad recomendada
- Fase 1: ordenar navegacion administrativa y permisos efectivos.
- Fase 2: reforzar pantallas de configuracion de empresa/sucursales.
- Fase 3: agregar auditoria y soporte de administracion avanzada.

## 7. Biotime
### Alcance actual
El modulo `Biotime` contiene:

- panel de integracion,
- configuracion,
- sincronizacion,
- areas,
- departamentos,
- empleados BioTime.

### Componentes y servicios eje
- Livewire:
  - `app/Livewire/biotime/BiotimeIndexLive.php`
  - `app/Livewire/biotime/BiotimeConfigLive.php`
  - `app/Livewire/biotime/BiotimeSyncLive.php`
  - `app/Livewire/biotime/area/AreaIndexLive.php`
  - `app/Livewire/biotime/department/DepartmentIndexLive.php`
  - `app/Livewire/biotime/employees/EmployeesIndexLive.php`
- Servicios:
  - `app/Services/BiotimeApiClient.php`

### Modelos y relaciones dominantes
- `BiotimeSetting`
- `BiotimeAccessLog`
- `IntegrationErrorLog`
- entidades sincronizadas de personal/estructura

### Hallazgos
- `BiotimeIndexLive` hoy es una pantalla muy ligera cuyo rol principal es testear conexion.
- La integracion parece estar organizada por vistas separadas, pero falta una experiencia de observabilidad.
- BioTime es un modulo tecnico; no deberia competir visualmente con modulos operativos de negocio.

### Riesgos actuales
- Si la integracion falla, el usuario no tiene un tablero claro de:
  - ultimo sync exitoso,
  - errores recientes,
  - impacto funcional.
- Configuracion, sincronizacion manual y datos maestros estan separados, pero no articulados como flujo.

### Plan de mejora
1. Convertir `Biotime` en un modulo de integracion con tres vistas:
   - estado del conector,
   - sincronizaciones,
   - catalogos sincronizados.
2. Incorporar logs operativos visibles:
   - ultimo intento,
   - ultimo exito,
   - errores recientes,
   - entidad afectada.
3. Enlazar BioTime con asistencia de clientes o empleados solo a traves de servicios de integracion, nunca con consultas directas desde UI.
4. Dejar `Biotime` como modulo de soporte/administracion, no como parte del flujo diario.

### Prioridad recomendada
- Fase 1: observabilidad y estado del conector.
- Fase 2: trazabilidad de sincronizaciones.
- Fase 3: cierre de brechas entre integracion y modulos consumidores.

## Dependencias y relaciones transversales que debemos cuidar
### Cliente como entidad pivote
`Cliente` es el centro real del sistema y conecta:

- comercial,
- operacion diaria,
- bienestar,
- CRM,
- alquileres,
- reportes.

Esto obliga a usar agregadores de contexto en vez de consultas sueltas por modulo.

### Comercial y bienestar
`ClientWellnessService` hoy congela `ClienteMatricula|ClienteMembresia`, lo que confirma que bienestar toca un dominio comercial. Esa frontera debe hacerse explicita.

### Operacion y analitica
`ReporteModuloService` sigue calculando varias vistas desde modelos base, pero necesita alinearse con los nuevos agregadores operativos para no divergir.

### Cliente legacy vs nuevo comercial
`cliente_membresias` aun aparece en:

- ficha del cliente,
- bienestar,
- reportes.

Debe quedar claramente marcado como legado de lectura/cobranza controlada.

## Roadmap global recomendado
### Fase 1. Consistencia de dominio
- Consolidar fuentes de verdad por modulo.
- Crear agregadores compartidos para cliente, comercial, bienestar y analitica.
- Eliminar dependencias operativas directas sobre tablas legacy en nuevas funciones.

### Fase 2. Desacople de componentes grandes
- Reducir `ClientePerfilLive`.
- Reducir `GestionNutricionalUnificadoLive`.
- Evitar que reportes reutilicen componentes operativos.

### Fase 3. Navegacion y permisos
- Reordenar sidebar por tareas reales.
- Alinear permisos sembrados, middleware y acciones reales.
- Exponer mejor la administracion avanzada por rol y sucursal.

### Fase 4. Observabilidad y reportabilidad
- Hacer que cada modulo tenga resumen operativo propio.
- Mejorar reportes por dominio.
- Incorporar trazabilidad de integraciones, cambios administrativos y procesos comerciales.

## Orden sugerido de implementacion
1. Clientes
2. Bienestar
3. Analitica
4. Comercial
5. Recursos
6. Administracion
7. Biotime

## Criterios de exito
- Un mismo cliente muestra el mismo estado comercial, de bienestar y de deuda en cualquier modulo.
- Ningun reporte operativo depende de un componente Livewire pensado para transaccion diaria.
- Los componentes principales quedan mas pequenos y orquestan servicios en vez de consultar modelos de forma directa.
- El sidebar refleja tareas reales del negocio y no solo agrupaciones historicas.
- Legacy sigue visible solo donde aporta compatibilidad, no como fuente activa de nuevas operaciones.
