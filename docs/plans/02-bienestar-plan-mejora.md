# Plan de mejora: Bienestar

> **Referencia:** [module-consistency-matrix.md](../architecture/module-consistency-matrix.md)  
> **Prioridad:** Alta (orden global #4)  
> **Inconsistencias vinculadas:** INC-08 (parcial, ver `PlanFreezeService`)  
> **Ultima actualizacion:** 2026-08-27 (refresco de 2026-06-24 — verificado: `GestionNutricionalUnificadoLive` sigue en ~876 LOC, sin cambio; contenido de este plan sigue vigente al 100%)

---

## 1. Contexto y diagnostico

### Alcance funcional
Salud/nutricion (evaluaciones, seguimientos, citas, objetivos) y entrenamiento (ejercicios, rutinas, sesiones, progreso, cumplimiento), mas operaciones anexas (congelamiento, reservas).

### Estado actual

| Elemento | Situacion |
| --- | --- |
| `GestionNutricionalUnificadoLive` | ~875 LOC, ~39 metodos, 10 modales |
| `ClientWellnessService` | Agregador parcial que mezcla dominios |
| `MedidasNutricionLive` | ~592 LOC; candidato a consolidar |
| Sidebar | Permisos separados nutricion vs rutinas |
| Sesiones entrenamiento | Rutas sin item sidebar |

### Riesgo principal
Dominios clinicos y de entrenamiento comparten componente y servicio; congelamiento comercial vive en bienestar.

### Fuentes de verdad objetivo
- Clinico: `health_records`, `evaluacion_medidas_nutricion`, `seguimiento_nutricion`, `citas`, `nutrition_goals`
- Entrenamiento: `routine_templates`, `client_routines`, `workout_sessions`

---

## 2. Objetivos

1. Dividir bienestar en subdominios **Salud/Nutricion** y **Entrenamiento**.
2. Reducir `GestionNutricionalUnificadoLive` a shell temporal (<300 LOC).
3. Extraer congelamiento a servicio comercial compartido.
4. Reservas de espacios solo como atajo; escritura en Recursos.
5. Timeline del cliente como servicio transversal reutilizable.

---

## 3. Plan por fases

### Fase 1 — Division de servicios

**Objetivo de fase:** Extraer logica de negocio del componente unificado.

#### Paso 1.1 — Inventario de GestionNutricionalUnificadoLive

- **Objetivo:** Mapa de modales, metodos y dependencias.
- **Archivos:** `GestionNutricionalUnificadoLive.php`, vista Blade.
- **Tareas:**
  1. Listar 4 tabs principales y 10 modales con acciones CRUD.
  2. Clasificar por subdominio: salud, nutricion, citas, rutinas, congelamiento, reservas.
- **Criterios de aceptacion:**
  - Documento/tabla de clasificacion completa.

#### Paso 1.2 — Crear `ClienteHealthHubService`

- **Objetivo:** Ficha de salud, antecedentes, evaluaciones corporales agregadas.
- **Archivos nuevos:** `app/Services/Wellness/ClienteHealthHubService.php`.
- **Tareas:**
  1. Metodos: `getHealthRecord`, `getLatestEvaluations`, `getEvaluationHistory`.
  2. Delegar CRUD a `EvaluacionMedidasNutricionService` existente.
  3. Sin logica de citas ni rutinas.
- **Criterios de aceptacion:**
  - Componente unificado usa servicio para tab salud/evaluaciones.
- **Dependencias:** Paso 1.1.

#### Paso 1.3 — Crear `ClienteNutritionService`

- **Objetivo:** Seguimientos nutricionales y objetivos.
- **Archivos nuevos:** `app/Services/Wellness/ClienteNutritionService.php`.
- **Tareas:**
  1. Agregar seguimientos, objetivos activos, progreso reciente.
  2. Integrar `SeguimientoNutricionService` y modelos `NutritionGoal`.
  3. Separar de `MedidasNutricionLive` donde haya duplicacion.
- **Criterios de aceptacion:**
  - Tab nutricion/objetivos sin queries directas en Livewire.
- **Dependencias:** Paso 1.1.

#### Paso 1.4 — Crear `ClienteTrainingOverviewService`

- **Objetivo:** Resumen rutinas activas, sesiones recientes, cumplimiento basico.
- **Archivos nuevos:** `app/Services/Wellness/ClienteTrainingOverviewService.php`.
- **Tareas:**
  1. Delegar a `ClientRoutineService`, queries de `WorkoutSession`.
  2. Metodo `getTrainingSummary(Cliente $cliente)`.
- **Criterios de aceptacion:**
  - Overview de entrenamiento independiente de nutricion.
- **Dependencias:** Paso 1.1.

#### Paso 1.5 — Extraer `PlanFreezeService` (congelamiento)

- **Objetivo:** Resolver INC-08; sacar congelamiento de `ClientWellnessService`.
- **Archivos nuevos:** `app/Services/Cliente/PlanFreezeService.php`.
- **Tareas:**
  1. Mover logica freeze/unfreeze de matricula y membresia legacy.
  2. Validaciones comerciales: fechas, estado plan, sucursal.
  3. Actualizar `ClientWellnessService` para delegar o eliminar metodos freeze.
  4. Consumir desde ficha cliente y gestion nutricional.
- **Criterios de aceptacion:**
  - Un solo servicio escribe congelamientos.
  - Tests de freeze/unfreeze matricula.
- **Dependencias:** [01-clientes-plan-mejora.md](./01-clientes-plan-mejora.md).

#### Paso 1.6 — Refactorizar GestionNutricionalUnificadoLive a shell

- **Objetivo:** Componente solo orquesta tabs y modales.
- **Tareas:**
  1. Reemplazar logica inline por llamadas a servicios Fase 1.
  2. Extraer modales repetitivos a traits por dominio.
  3. Target: < 400 LOC.
- **Criterios de aceptacion:**
  - LOC reducido >50%.
  - Feature tests existentes pasan.

---

### Fase 2 — Separacion UI y rutas

**Objetivo de fase:** Navegacion refleja dos subdominios distintos.

#### Paso 2.1 — Sub-grupos sidebar Salud vs Entrenamiento

- **Objetivo:** Claridad visual sin romper permisos.
- **Archivos:** `sidebar.blade.php`.
- **Tareas:**
  1. Separador o sub-heading "Salud y nutricion" vs "Entrenamiento".
  2. Mantener permisos `gestion_nutricional.ver` y `ejercicio_rutina.ver`.
- **Criterios de aceptacion:**
  - Usuario identifica dos areas funcionales.

#### Paso 2.2 — Consolidar rutas de objetivos y salud

- **Objetivo:** Evitar duplicacion `gestion-nutricional.*` vs componentes sueltos.
- **Archivos:** `routes/web.php`, `Nutrition/*`, `MedidasNutricionLive`.
- **Tareas:**
  1. Auditar rutas redundantes (`medidas-nutricion`, redirects).
  2. Deprecar componentes legacy si estan cubiertos por unificado o nuevos shells.
- **Criterios de aceptacion:**
  - Mapa rutas sin duplicados funcionales.

#### Paso 2.3 — Exponer sesiones de entrenamiento en navegacion

- **Objetivo:** `clientes.sesiones.*` descubrible desde Bienestar.
- **Tareas:**
  1. Enlace contextual desde "Asignar rutina" o progreso hacia sesiones del cliente.
  2. No requiere item sidebar global; flujo cliente → rutina → sesiones.
- **Criterios de aceptacion:**
  - Flujo documentado en UI entrenamiento.

#### Paso 2.4 — Calendario de citas independiente

- **Objetivo:** `CalendarioCitasLive` como modulo propio bajo salud.
- **Tareas:**
  1. Verificar que calendario no dependa de tabs del unificado.
  2. API eventos (`gestion-nutricional.calendario.eventos`) estable.
- **Criterios de aceptacion:**
  - Calendario usable sin abrir gestion unificada.

---

### Fase 3 — Reservas, timeline y legacy

**Objetivo de fase:** Coherencia con Recursos y Clientes.

#### Paso 3.1 — Eliminar escritura de reservas desde bienestar

- **Objetivo:** Bienestar solo enlaza a Recursos o abre modal que llama `RentalService`.
- **Archivos:** `GestionNutricionalUnificadoLive`, `ClientePerfilLive`, `ClientWellnessService`.
- **Tareas:**
  1. Reemplazar creacion directa de `Rental` por `RentalService::createFromContext()`.
  2. Modal reserva: seleccion espacio → redirect o confirm via servicio unico.
- **Criterios de aceptacion:**
  - Cero `Rental::create` en Livewire bienestar.
- **Dependencias:** [04-recursos-plan-mejora.md](./04-recursos-plan-mejora.md).

#### Paso 3.2 — Crear `ClienteTimelineService`

- **Objetivo:** Linea de tiempo transversal (citas, evaluaciones, sesiones, pagos bienestar).
- **Archivos nuevos:** `app/Services/Cliente/ClienteTimelineService.php`.
- **Tareas:**
  1. Agregar eventos de multiples fuentes ordenados por fecha.
  2. Consumir desde ficha cliente y gestion nutricional.
  3. Paginacion cursor-based.
- **Criterios de aceptacion:**
  - Timeline identica en ficha y bienestar para mismo cliente.

#### Paso 3.3 — Reportes progreso/cumplimiento

- **Objetivo:** `ProgressByExercise`, `Compliance` alineados con servicios entrenamiento.
- **Tareas:**
  1. Consumir `ClienteTrainingOverviewService` o queries centralizadas.
  2. Filtros por sucursal y rango fechas.
- **Criterios de aceptacion:**
  - Reportes no duplican SQL del componente unificado.

#### Paso 3.4 — Deprecar ClientWellnessService monolitico

- **Objetivo:** Reducir a facade que delega a servicios nuevos o eliminar.
- **Tareas:**
  1. Migrar consumidores restantes.
  2. Marcar `@deprecated` y eliminar en release posterior.
- **Criterios de aceptacion:**
  - Sin referencias activas en Livewire.

---

## 4. Riesgos y mitigaciones

| Riesgo | Mitigacion |
| --- | --- |
| Regresion en flujos clinicos | Tests por tab antes de extraer |
| Congelamiento rompe comercial | Tests integracion con matriculas |
| Reservas duplicadas | RentalService unico (Recursos) |

---

## 5. Criterios de cierre del modulo

- [ ] Tres servicios wellness dedicados implementados
- [ ] `PlanFreezeService` fuera de wellness
- [ ] `GestionNutricionalUnificadoLive` < 400 LOC
- [ ] Reservas sin escritura local
- [ ] `ClienteTimelineService` compartido con ficha
- [ ] Sidebar refleja salud vs entrenamiento

---

## 6. Dependencias con otros modulos

| Modulo | Dependencia |
| --- | --- |
| Clientes | Wellness summary en ficha; congelamiento comercial |
| Recursos | RentalService unico |
| Analitica | Reportes progreso alineados |
| Operaciones | Sin solapamiento cobranza |

---

## 7. Avance de implementación (2026-06-24)

### Completado (Fase 1 parcial)
- `ClienteHealthHubService`, `ClienteNutritionService`, `ClienteTrainingOverviewService`
- `PlanFreezeService` extraído; `ClientWellnessService` delega freeze
- Reservas vía `RentalService` desde wellness (sin `Rental::create` directo)
- `ClienteTimelineService` creado
- Sidebar: separadores Salud vs Entrenamiento

### Pendiente
- Refactor `GestionNutricionalUnificadoLive` < 400 LOC
- Integrar hub services en componente unificado
- Tests feature por tab
