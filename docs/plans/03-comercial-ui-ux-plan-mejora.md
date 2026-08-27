# Plan de mejora UI/UX: CRM (Comercial)

> **Referencia:** [03-comercial-plan-mejora.md](./03-comercial-plan-mejora.md) (arquitectura/backend), [module-consistency-matrix.md](../architecture/module-consistency-matrix.md)
> **Prioridad:** Alta (complementario al plan de Comercial existente)
> **Alcance:** Visual (UI) y experiencia de uso (UX) del módulo CRM — no toca permisos, trazabilidad ni reglas de negocio salvo donde coordina con ellas
> **Última actualización:** 2026-08-26

---

## 1. Contexto y diagnóstico

### Alcance funcional
18 componentes Livewire bajo `app/Livewire/Crm/` (Pipeline, Leads, Tareas, Deals, Campañas, Etiquetas, Reportes, Renovación/Reactivación, Mensajes WhatsApp), con sus vistas en `resources/views/livewire/crm/`.

### Por qué este documento es distinto de `03-comercial-plan-mejora.md`
Ese plan existente formaliza el embudo comercial, unifica permisos (`crm.ver` vs `crm_mensaje.ver`, INC-10) y fortalece la trazabilidad de conversión lead→cliente. **No cubre el diseño visual ni la interacción** — el módulo es funcionalmente maduro pero visualmente inconsistente y con fricción evitable en varios flujos. Este documento llena ese hueco.

### Sistema de diseño disponible
- **Stack:** Tailwind v4 (tokens CSS en `resources/css/theme-personalizacion.css`, paleta base `zinc`, acento configurable por usuario, colores de estado `--theme-state-*`) + Flux UI (`flux:button`, `flux:modal`, `flux:field`, `flux:input`, `flux:dropdown`, `flux:menu`, etc.).
- **Kit de componentes compartidos:** `x-empty-state`, `x-breadcrumbs`, `x-ui.table-actions`, `x-cliente.search-input`, entre otros, en `resources/views/components/`.
- **Adopción real en CRM:** solo 3 de 18 vistas usan algún componente `x-*` compartido (`crm-deals-live` y `leads-list-live` usan `x-ui.table-actions`; `mensajes-live` usa `x-cliente.search-input`). El resto reimplementa markup inline.
- **Librerías instaladas pero sin uso en CRM:** `chart.js` (4.4.0) y `sweetalert2` (11) — disponibles en `package.json` pero ninguna vista CRM las usa.
- **Dark mode:** implementado y usado en CRM, pero de forma muy desigual — 1 ocurrencia de `dark:` en `activity-form-live.blade.php` vs 46 en `crm-pipeline-live.blade.php`.

### Hallazgos concretos (archivo:línea)

| # | Problema | Evidencia |
| --- | --- | --- |
| 1 | Falsa affordance: el Kanban anuncia arrastre pero solo hay menú desplegable para cambiar de etapa | `crm-pipeline-live.blade.php:5` (copy) vs `:160-174` (único mecanismo real, vía `moveToStage`) |
| 2 | Flujo de modales anidados pesado: cada sub-acción del detalle de lead abre uno de 5 modales, cada uno cargando un componente Livewire aparte | `lead-detail-live.blade.php:159-187` |
| 3 | Sin "marcar tarea completada" rápida ni `tel:` clicable en el teléfono del lead | `lead-detail-live.blade.php:26` (teléfono texto plano), `:144-157` (solo botón "Editar", sin acción de completar) |
| 4 | Tablas casi idénticas duplicadas en vez de un componente de tabla compartido | `crm-deals-live.blade.php` vs `leads-list-live.blade.php` (estructura completa repetida) |
| 5 | Pills de tag con color inline vía concatenación de hex + alpha, sin componente compartido, duplicado en 4 archivos | `crm-pipeline-live.blade.php:188`, `lead-detail-live.blade.php:47`, `crm-tags-live.blade.php:19`, `tag-picker-live.blade.php:10` |
| 6 | Formularios de tarea y actividad casi idénticos (mismo patrón: tipo, fecha/hora, notas, guardar/cancelar) sin extraer a partial | `task-form-live.blade.php` vs `activity-form-live.blade.php` |
| 7 | Filtro de rango de días duplicado | `campaign-detail-live.blade.php:89-101` vs `renewal-reactivacion-live.blade.php` |
| 8 | Selects de filtro sin `<label>`/`aria-label` | `crm-pipeline-live.blade.php:54-69`, `mensajes-live.blade.php:61-71`, filtros de `leads-list-live` y `crm-deals-live` |
| 9 | `<select>` nativo mezclado con `flux:select` dentro del mismo formulario | `lead-form-live.blade.php` (tipo_documento, stage_id, assigned_to), `deal-form-live.blade.php`, `convert-lead-live.blade.php`, `task-form-live.blade.php:6`, `activity-form-live.blade.php:6` |
| 10 | Estado/etapa como texto plano, sin badge de color, inconsistente con los badges de stage que sí existen en "Gestionar etapas" | `crm-deals-live.blade.php:48` y `lead-detail-live.blade.php:71` (`{{ $d->estado }}` sin badge), `leads-list-live.blade.php:57` |
| 11 | Reportes CRM (`crm-reportes-live.blade.php`) es la vista analítica del módulo pero no tiene ni un gráfico, pese a tener Chart.js instalado; tablas de asesor/canal sin paginación | ver hallazgos del agente de exploración sobre `crm-reportes-live.blade.php` |
| 12 | Edición inline sin feedback por fila: `wire:model.live` por celda en la tabla de targets de campaña, sin debounce ni indicador de guardado individual | `campaign-detail-live.blade.php:46-57` |
| 13 | Sin sub-navegación entre las 9+ páginas de CRM; solo el sidebar global las conecta; breadcrumbs solo en `campaign-detail` y `cliente-etiquetas` | inventario de vistas `resources/views/livewire/crm/*.blade.php` |
| 14 | Toggle "Mi día"/"Lista" en tareas no persiste en la URL, se pierde al recargar | `crm-tasks-live.blade.php` (`$view` sin binding a query string) |
| 15 | Confirmaciones de borrado usan `wire:confirm` nativo del navegador en vez de un modal Flux consistente con el resto del módulo | `crm-pipeline-live.blade.php:285` (borrar etapa), `lead-detail-live.blade.php:83,120` (borrar deal/actividad), `crm-tags-live.blade.php` (borrar tag) |
| 16 | Punto positivo a preservar: el pipeline ya tiene un patrón de overlay de "moviendo..." (`$isMoving`, spinner) que debe reutilizarse como feedback optimista al implementar drag-and-drop real | `crm-pipeline-live.blade.php:137-144` |

### Decisiones de alcance confirmadas
- **Kanban:** se implementará **drag-and-drop real** (no solo corregir el texto), reutilizando el patrón de overlay "moviendo..." ya existente para el estado optimista.
- **Responsive:** prioridad **media**. El CRM web es back-office de escritorio para asesores comerciales; existe una app móvil separada (`gimnacio_mobile`) que cubre el uso en campo. Se busca que el módulo no se rompa en tablet (~768–1024px), sin invertir en mobile-first.

---

## 2. Objetivos

1. **Consistencia visual:** badges de estado, tags y selects unificados vía componentes Blade reutilizables, alineados a los tokens de `theme-personalizacion.css`.
2. **Reducir fricción de interacción:** Kanban con arrastre real, menos pasos vía modal para acciones frecuentes, feedback en tiempo real y por fila (no solo global).
3. **Eliminar duplicación de markup:** extraer componentes compartidos para tablas, badges, tag pills, listas relacionadas y formularios similares.
4. **Cerrar gaps de accesibilidad:** labels/aria en todos los filtros, uso consistente de `flux:select`, contraste correcto en tags.
5. **Convertir Reportes CRM en una vista analítica real:** al menos un gráfico (Chart.js, ya disponible) y paginación en tablas largas.
6. **Navegación intra-CRM coherente:** sub-nav/tabs y breadcrumbs consistentes entre las 9 páginas principales del módulo.
7. **Responsive de prioridad media:** validar y corregir comportamiento en tablet sin rediseñar para mobile-first.

---

## 3. Plan por fases

### Fase 0 — Fundamentos de sistema de diseño CRM

**Objetivo de fase:** Tener las piezas reutilizables antes de tocar cada vista, para evitar refactors repetidos.

#### Paso 0.1 — Componentes Blade compartidos para CRM
- **Archivos nuevos:** `resources/views/components/crm/status-badge.blade.php`, `resources/views/components/crm/tag-pill.blade.php`, `resources/views/components/crm/related-list.blade.php`, `resources/views/components/crm/day-range-filter.blade.php`.
- **Tareas:**
  1. `status-badge`: recibe estado/etapa + tipo (`lead`, `deal`, `task`) y renderiza un badge de color consistente con los tokens `--theme-state-*` existentes.
  2. `tag-pill`: reemplaza los 4 usos de `style="background: {{ $tag->color }}NN"` inline por un componente que calcule contraste de texto (claro/oscuro según luminancia del color) en vez de hardcodear alpha hex.
  3. `related-list`: encapsula el patrón "header + botón nuevo + `<ul>` + empty state" repetido 3 veces en `lead-detail-live.blade.php` (Oportunidades, Actividades, Tareas).
  4. `day-range-filter`: extrae los botones de rango de días duplicados en `campaign-detail-live` y `renewal-reactivacion-live`.
- **Criterios de aceptación:** 0 ocurrencias de `style="background: {{ $tag->color`; badges de estado visualmente consistentes en pipeline, deals, leads-list y lead-detail.

#### Paso 0.2 — Adoptar `flux:select` y labels en todos los filtros/formularios CRM
- **Archivos:** `crm-pipeline-live.blade.php` (filtros asesor/canal), `leads-list-live.blade.php`, `crm-deals-live.blade.php`, `mensajes-live.blade.php`, `lead-form-live.blade.php`, `deal-form-live.blade.php`, `convert-lead-live.blade.php`, `task-form-live.blade.php`, `activity-form-live.blade.php`.
- **Tareas:** reemplazar `<select>` nativo por `<flux:select>`; agregar `<flux:label>` o `aria-label` a cada filtro.
- **Criterios de aceptación:** grep de `<select` en `resources/views/livewire/crm/` sin resultados fuera de `flux:select`; 0 filtros sin label/aria-label.

#### Paso 0.3 — Adoptar `<x-empty-state>` existente
- **Archivos:** `crm-deals-live.blade.php:64-70`, `leads-list-live.blade.php`, `crm-campaigns-live.blade.php`, otros estados vacíos reimplementados inline.
- **Criterios de aceptación:** estados vacíos de listas usan el componente compartido en vez de markup ad-hoc.

---

### Fase 1 — Kanban Pipeline: drag-and-drop real

**Objetivo de fase:** Cumplir la promesa de UX del pipeline y agilizar el movimiento de leads entre etapas.

#### Paso 1.1 — Implementar arrastre real
- **Archivos:** `crm-pipeline-live.blade.php`, `app/Livewire/Crm/CrmPipelineLive.php`, `app/Services/Crm/LeadService.php` / `CrmStageService.php`.
- **Tareas:**
  1. Integrar arrastre de tarjetas entre columnas (Alpine + Sortable, disponible vía el bundle de Flux; evaluar `x-sort` si la versión de Flux lo expone).
  2. Al soltar, invocar el mismo método `moveToStage` ya usado por el dropdown, reutilizando el overlay `$isMoving`/spinner existente (`:137-144`) para el estado optimista.
  3. Manejar rollback visual si la transición es rechazada por el servicio (coordinar con las reglas de transición de stage del **Paso 1.4 de `03-comercial-plan-mejora.md`**, si ya están implementadas).
  4. Mantener el menú dropdown como alternativa accesible por teclado/lector de pantalla (no eliminarlo, es el fallback de accesibilidad).
- **Criterios de aceptación:** arrastrar una tarjeta a otra columna persiste el cambio sin recargar la página, con rollback visual ante error; el copy del header refleja fielmente la funcionalidad.

#### Paso 1.2 — Simplificar interacciones de columna
- **Archivos:** `crm-pipeline-live.blade.php:221-300` (modal "Gestionar etapas").
- **Tareas:** evaluar mover la gestión de etapas (hoy modal-dentro-de-modal con lista + formulario alternando) a un panel lateral (`flux:modal ... variant="flyout"` ya se usa; considerar una vista propia si crece).
- **Criterios de aceptación:** crear/editar/reordenar etapas no requiere navegar dos niveles de UI dentro del mismo modal sin indicación de "volver".

---

### Fase 2 — Reducir fricción de modales / edición inline

**Objetivo de fase:** Que las acciones más frecuentes no requieran un ciclo completo de apertura de modal + carga de sub-componente.

#### Paso 2.1 — Lead Detail: acciones rápidas
- **Archivos:** `lead-detail-live.blade.php`, `app/Livewire/Crm/LeadDetailLive.php`.
- **Tareas:**
  1. Agregar acción "Marcar completada" directamente en el ítem de tarea (sin abrir `task-form-live`) para tareas simples.
  2. Convertir el teléfono (`:26`) en enlace `tel:`.
  3. Reemplazar `wire:confirm` nativo en borrar deal/actividad (`:83,120`) por un modal de confirmación Flux consistente con el resto de la app.
- **Criterios de aceptación:** completar una tarea desde el detalle del lead toma 1 clic sin modal; confirmar borrado usa el mismo componente visual en toda la vista.

#### Paso 2.2 — Campaign detail: feedback por fila
- **Archivos:** `campaign-detail-live.blade.php:46-57`, `app/Livewire/Crm/CampaignDetailLive.php`.
- **Tareas:** agregar `wire:loading` con `wire:target` scoped a la fila/celda modificada (no solo global), e indicador transitorio de "guardado" (check icon) por fila.
- **Criterios de aceptación:** editar la asignación/estado de un target de campaña muestra feedback visual propio de esa fila, sin bloquear ni confundir con el resto de la tabla.

---

### Fase 3 — Navegación intra-CRM

**Objetivo de fase:** Que el usuario entienda en qué parte del módulo está y pueda moverse sin volver al sidebar completo.

#### Paso 3.1 — Sub-nav y breadcrumbs consistentes
- **Archivo nuevo:** `resources/views/components/crm/subnav.blade.php` con enlaces a Pipeline, Leads, Tareas, Deals, Campañas, Etiquetas, Reportes, Renovación/Reactivación, Mensajes, resaltando la página activa.
- **Tareas:** incluir el sub-nav en las 9 vistas top-level (no en modales ni formularios); usar `<x-breadcrumbs>` (ya existe) en `lead-detail-live`, `campaign-detail-live` (ya la tiene parcialmente) y `cliente-tags-live`.
- **Criterios de aceptación:** desde cualquier página CRM se puede navegar a otra sub-sección sin volver al sidebar global.

#### Paso 3.2 — Estado de vista bookmarkable
- **Archivo:** `crm-tasks-live.blade.php`, `app/Livewire/Crm/CrmTasksLive.php`.
- **Tareas:** enlazar la propiedad `$view` (`my-day`/`list`) a la query string vía el soporte de Livewire para URL binding.
- **Criterios de aceptación:** recargar la página o compartir la URL conserva la vista seleccionada.

---

### Fase 4 — Reportes CRM como vista analítica real

**Objetivo de fase:** Que "Reportes CRM" cumpla su función de analítica visual, no solo tablas numéricas.

#### Paso 4.1 — Gráficos con Chart.js
- **Archivos:** `crm-reportes-live.blade.php`, `app/Livewire/Crm/CrmReportesLive.php`, `app/Services/Crm/CrmReportService.php`.
- **Tareas:**
  1. Agregar gráfico de embudo de conversión (barras/funnel) en la pestaña de conversión.
  2. Agregar tendencia temporal (línea) si el servicio ya expone series por fecha; si no, evaluar extenderlo.
  3. Verificar si `chart.js` ya se usa en otro módulo de Analítica para mantener un solo patrón de integración (wrapper Alpine/Livewire) en todo el sistema.
- **Criterios de aceptación:** la pestaña de conversión muestra al menos un gráfico junto a las tarjetas numéricas actuales.

#### Paso 4.2 — Paginar tablas de asesor/canal
- **Archivos:** `crm-reportes-live.blade.php`, `CrmReportesLive.php`.
- **Tareas:** paginar o acotar a top-N con "ver más" las tablas `byAdvisorData`/`byChannelData`.
- **Criterios de aceptación:** las tablas no crecen sin límite visual con más asesores/canales.

---

### Fase 5 — Accesibilidad y consistencia visual final

**Objetivo de fase:** Cerrar los gaps residuales antes de dar el módulo por terminado en UI/UX.

#### Paso 5.1 — Auditoría de accesibilidad y confirmaciones
- **Tareas:**
  1. Verificar contraste de texto sobre color de tag en el nuevo `x-crm.tag-pill` (Paso 0.1) con distintos colores guardados en producción.
  2. Confirmar que todos los `wire:confirm` nativos del módulo (borrar etapa, deal, actividad, tag) fueron migrados a modal Flux (Paso 2.1 cubre lead-detail; extender a `crm-pipeline-live` y `crm-tags-live`).
  3. Revisar `dark:` classes faltantes en archivos con baja densidad (`activity-form-live.blade.php` y similares) para nivelar el soporte de modo oscuro.
- **Criterios de aceptación:** checklist de accesibilidad (labels, contraste, confirmaciones) cerrado para las 18 vistas.

#### Paso 5.2 — Responsive (prioridad media)
- **Tareas:** validar tablas (scroll horizontal controlado) y el Kanban (scroll horizontal ya presente) en viewport ~768–1024px; corregir overflow roto si aparece.
- **Criterios de aceptación:** ninguna vista CRM se rompe visualmente en tablet; no se exige layout mobile-first.

---

### Fase 6 — QA y rollout

- Checklist manual por cada una de las 9 páginas top-level de CRM, en modo claro y oscuro.
- Revisar en paralelo el estado de `03-comercial-plan-mejora.md` (Fase 1, permisos y transición de stages) antes de cerrar la Fase 1 de este plan, para no introducir conflictos entre la regla de negocio de transición y el nuevo drag-and-drop.

---

## 4. Riesgos y mitigaciones

| Riesgo | Mitigación |
| --- | --- |
| Drag-and-drop puede chocar con reglas de transición de stage que el plan backend aún no ha implementado | Coordinar Fase 1 de este plan con el Paso 1.4 de `03-comercial-plan-mejora.md`; si las reglas no existen aún, el backend debe rechazar transiciones inválidas igual que hoy rechaza vía dropdown |
| Refactor de componentes compartidos (badge/tag-pill/related-list) toca las 18 vistas | Aplicar incrementalmente página por página, con verificación visual antes/después de cada una, no en un solo cambio masivo |
| Chart.js no se usa aún en CRM | Confirmar si ya hay un patrón de integración en otro módulo de Analítica antes de crear uno nuevo; evitar dos formas distintas de integrar gráficos en la misma app |
| Sub-nav nuevo puede duplicar información ya presente en el sidebar | Diseñarlo como navegación secundaria compacta (tabs), no como un segundo menú completo |

---

## 5. Criterios de cierre del módulo (UI/UX)

- [ ] Componentes `x-crm.status-badge`, `x-crm.tag-pill`, `x-crm.related-list`, `x-crm.day-range-filter` creados y adoptados en las vistas correspondientes
- [ ] 0 `<select>` nativos sin label/`flux:select` en `resources/views/livewire/crm/`
- [ ] Kanban del pipeline con drag-and-drop funcional y rollback visual
- [ ] Reportes CRM con al menos un gráfico y tablas de asesor/canal paginadas o acotadas
- [ ] Sub-nav y breadcrumbs presentes en las 9 páginas top-level de CRM
- [ ] Confirmaciones de borrado migradas de `wire:confirm` nativo a modal Flux consistente
- [ ] Validación responsive en tablet (~768–1024px) sin overflow roto

---

## 6. Dependencias con otros documentos

| Documento | Relación |
| --- | --- |
| [03-comercial-plan-mejora.md](./03-comercial-plan-mejora.md) | Reglas de transición de stage (Paso 1.4) deben coordinarse con el drag-and-drop de la Fase 1 de este plan; permisos `crm.*` no se modifican aquí |
| [module-consistency-matrix.md](../architecture/module-consistency-matrix.md) | Diagnóstico transversal de consistencia de módulos, fuente del contexto arquitectónico general |
