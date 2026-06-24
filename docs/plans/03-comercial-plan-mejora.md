# Plan de mejora: Comercial (CRM y promociones)

> **Referencia:** [module-consistency-matrix.md](../architecture/module-consistency-matrix.md)  
> **Prioridad:** Alta (orden global #5)  
> **Inconsistencias vinculadas:** INC-10  
> **Ultima actualizacion:** 2026-06-24

---

## 1. Contexto y diagnostico

### Alcance funcional
Pipeline CRM, leads, tareas, oportunidades, campanas, etiquetas, renovacion/reactivacion, mensajes WhatsApp, cupones.

### Estado actual

| Elemento | Situacion |
| --- | --- |
| Componentes Livewire CRM | 18; modulo maduro |
| `ConvertLeadToClientService` | Separado; trazabilidad mejorable |
| Permisos | `crm.ver` vs `crm_mensaje.ver` fragmentados |
| `crm.reportes` | Ruta sin sidebar |
| Cupones | Integrados en POS; trazabilidad limitada fuera venta |

### Riesgo principal
Estados de lead, pipeline y conversion pueden divergir; permisos CRM no siguen un contrato unificado.

### Fuente de verdad objetivo
`crm_leads`, `deals`, `crm_tasks`, `crm_activities`, `campaigns`, `tags`, `discount_coupons`, `coupon_usages`

---

## 2. Objetivos

1. Formalizar embudo comercial de punta a punta.
2. Unificar matriz de permisos CRM.
3. Trazabilidad completa lead → cliente → deal.
4. Separar UI: CRM / Retencion / Promociones.
5. Cupones trazables en ventas y campanas.

---

## 3. Plan por fases

### Fase 1 — Permisos y trazabilidad de conversion

**Objetivo de fase:** Base segura y auditable del embudo.

#### Paso 1.1 — Documentar matriz de permisos CRM

- **Objetivo:** Resolver INC-10 con contrato explicito.
- **Archivos:** `docs/plans/03-comercial-permisos-matriz.md` (anexo), `PermissionCatalog`, seeders.
- **Matriz sugerida:**

| Accion | Permiso propuesto |
| --- | --- |
| Ver CRM | `crm.ver` |
| Crear/editar leads | `crm.crear`, `crm.editar` |
| Convertir lead | `crm.convertir` |
| Mensajes WhatsApp | `crm_mensaje.ver`, `crm_mensaje.enviar` |
| Campanas | `crm.campana.ver`, `crm.campana.editar` |
| Cupones | `cupon.ver`, `cupon.crear`, `cupon.editar` |

- **Criterios de aceptacion:**
  - Matriz publicada y semilla alineada.
  - Middleware de rutas CRM auditado.

#### Paso 1.2 — Alinear middleware y policies

- **Objetivo:** Misma regla en ruta, Livewire mount y FormRequest.
- **Archivos:** `routes/web.php`, componentes `Crm/*`, `Coupons/*`, policies si existen.
- **Tareas:**
  1. Auditar cada componente CRM: `authorize()` en mount/actions.
  2. Corregir desalineaciones lead create/edit vs permiso.
  3. Tests de autorizacion por rol.
- **Criterios de aceptacion:**
  - Test suite permisos CRM verde.

#### Paso 1.3 — Fortalecer ConvertLeadToClientService

- **Objetivo:** Trazabilidad completa de conversion.
- **Archivos:** `ConvertLeadToClientService.php`, modelos `Lead`, `Cliente`, `Deal`.
- **Tareas:**
  1. Al convertir: guardar `lead_id` en cliente (`cliente.lead_origen_id` o tabla pivot).
  2. Crear actividad CRM automatica "Lead convertido".
  3. Vincular deal existente o crear deal ganado.
  4. Registrar `converted_by` user id y timestamp.
  5. Transaccion DB atomica.
- **Criterios de aceptacion:**
  - Desde detalle lead se navega a cliente creado.
  - Actividad visible en timeline lead y cliente.
- **Dependencias:** Paso 1.1.

#### Paso 1.4 — Estados de pipeline consistentes

- **Objetivo:** Etapa Kanban alineada con reglas de negocio.
- **Archivos:** `LeadService.php`, `CrmPipelineLive.php`, `CrmStage` model/seeder.
- **Tareas:**
  1. Definir transiciones validas entre stages.
  2. Validar en servicio al mover tarjeta.
  3. Impedir conversion si stage no es "calificado" (regla configurable).
- **Criterios de aceptacion:**
  - Movimientos invalidos rechazados con mensaje claro.
  - Tests de transicion.

#### Paso 1.5 — Exponer crm.reportes en sidebar (opcional)

- **Objetivo:** Descubrimiento de reportes CRM.
- **Archivos:** `sidebar.blade.php`, `CrmReportesLive.php`.
- **Tareas:**
  1. Item bajo Comercial si usuario tiene `crm.ver`.
  2. O enlace desde pipeline header.
- **Criterios de aceptacion:**
  - Ruta accesible sin URL manual.

---

### Fase 2 — Retencion y consolidacion CRM

**Objetivo de fase:** Renovacion/reactivacion integrada al embudo.

#### Paso 2.1 — Crear `CrmOperationalSummaryService`

- **Objetivo:** KPIs unificados para pipeline, dashboard, renovacion.
- **Archivos nuevos:** `app/Services/Crm/CrmOperationalSummaryService.php`.
- **Metricas sugeridas:**
  - Leads nuevos / semana
  - Tasa conversion
  - Deals abiertos por valor
  - Clientes en riesgo churn (renovacion)
  - Tareas vencidas
- **Criterios de aceptacion:**
  - Pipeline y `RenewalReactivacionLive` muestran mismos totales base.

#### Paso 2.2 — Integrar RenewalReactivacion con leads/deals

- **Objetivo:** Reactivacion genera o reabre lead/deal trazable.
- **Archivos:** `RenewalReactivationService.php`, `RenewalReactivacionLive.php`.
- **Tareas:**
  1. Al marcar cliente para reactivacion: crear task + opcional lead.
  2. Historial visible en ficha cliente (via `ClienteCrmProfileService`).
- **Criterios de aceptacion:**
  - Accion reactivacion deja rastro CRM.

#### Paso 2.3 — Campanas vinculadas a segmentos reales

- **Objetivo:** Targets de campana desde datos de clientes/matriculas.
- **Archivos:** `CampaignService.php`, `CampaignDetailLive.php`.
- **Tareas:**
  1. Definir tipos target: leads por stage, clientes inactivos, matriculas por vencer.
  2. Preview conteo antes de lanzar.
- **Criterios de aceptacion:**
  - Campana muestra N destinatarios calculados en servicio.

#### Paso 2.4 — Mensajes WhatsApp con trazabilidad

- **Objetivo:** Registro de envios aun con mock.
- **Archivos:** `CrmMensajeService.php`, `MensajesLive.php`, `WhatsApp/*`.
- **Tareas:**
  1. Log en `crm_mensajes` o actividad CRM por envio.
  2. Vincular a lead/cliente/campana.
  3. Preparar interfaz para proveedor real.
- **Criterios de aceptacion:**
  - Historial mensajes por cliente visible en CRM profile.

---

### Fase 3 — Promociones y cupones

**Objetivo de fase:** Cupones integrados al ciclo comercial completo.

#### Paso 3.1 — Trazabilidad cupon en venta

- **Objetivo:** Cada uso registrado con venta, campana opcional, usuario.
- **Archivos:** `VentaService.php`, `CouponUsage`, POS.
- **Tareas:**
  1. Verificar `coupon_usages` se crea en cada venta con cupon.
  2. Reporte usos en `Coupons/Show`.
  3. Enlace desde venta a cupon aplicado.
- **Criterios de aceptacion:**
  - Show cupon lista ventas asociadas.

#### Paso 3.2 — Cupones en campanas

- **Objetivo:** Campana puede incluir codigo promocional.
- **Tareas:**
  1. Relacion opcional campana ↔ cupon.
  2. KPI conversion post-campana (cupones usados / enviados).
- **Criterios de aceptacion:**
  - Detalle campana muestra cupon vinculado.

#### Paso 3.3 — UI: separar bloques sidebar Comercial

- **Objetivo:** CRM core vs Retencion vs Promociones.
- **Archivos:** `sidebar.blade.php`.
- **Estructura sugerida:**
  - CRM: pipeline, leads, tareas, oportunidades, etiquetas
  - Retencion: renovacion-reactivacion, campanas
  - Promociones: cupones, mensajes
- **Criterios de aceptacion:**
  - Separadores visuales Flux aplicados.

#### Paso 3.4 — Integracion ficha cliente CRM

- **Objetivo:** Tab CRM en ficha usa mismos datos que modulo Comercial.
- **Dependencias:** [01-clientes-plan-mejora.md](./01-clientes-plan-mejora.md) Paso 1.5.
- **Criterios de aceptacion:**
  - Tags y tasks identicos entre ficha y `LeadDetailLive`.

---

## 4. Riesgos y mitigaciones

| Riesgo | Mitigacion |
| --- | --- |
| Permisos rotos en produccion | Migracion gradual; rol admin mantiene todos |
| Conversion duplica clientes | Unique constraint email/documento + transaccion |
| WhatsApp mock vs prod | Interface ya definida; swap implementacion |

---

## 5. Criterios de cierre del modulo

- [ ] Matriz permisos CRM documentada y aplicada
- [ ] Conversion con trazabilidad completa
- [ ] `CrmOperationalSummaryService` en uso
- [ ] Cupones trazables en ventas y campanas
- [ ] UI separada CRM / Retencion / Promociones
- [ ] Reportes CRM accesibles desde navegacion

---

## 6. Dependencias con otros modulos

| Modulo | Dependencia |
| --- | --- |
| Clientes | CRM profile; conversion crea cliente |
| Operaciones | Cupones en POS |
| Analitica | Reportes conversion y ventas con cupon |
| Comercial permisos | Anexo matriz permisos |

---

## 7. Avance de implementación (2026-06-24)

### Completado (Fase 1 parcial)
- Matriz permisos: `docs/plans/03-comercial-permisos-matriz.md`
- `CrmOperationalSummaryService` (KPIs pipeline)
- `ConvertLeadToClientService`: actividad CRM al convertir
- Sidebar: bloques CRM / Retención / Promociones + reportes CRM

### Pendiente
- Policies y tests autorización por rol
- Transiciones pipeline validadas en servicio
- Cupones trazables en campañas
