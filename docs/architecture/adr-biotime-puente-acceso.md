# ADR: Puente BioTime ↔ Laravel (control de acceso)

**Estado:** Aceptado  
**Fecha:** 2026-07-15  
**Aceptado:** 2026-07-15  
**Relacionado:** [adr-biotime-clasificacion.md](./adr-biotime-clasificacion.md), plan Operaciones Fase 3, plan ejecutable [08-biotime-integracion-plan.md](../plans/08-biotime-integracion-plan.md)

## Contexto

El gimnasio usa **BioTime 8.x** on-premise (puerto tipico `8085`/`8090`) en cada sede, con dispositivos de **huella** y **facial**. Laravel corre en **Banahosting** (hosting compartido): el servidor no puede abrir conexiones entrantes hacia la red local del gimnasio.

Ya existe sync **BioTime → Laravel** (`POST /api/biotime/sync`: employees, devices, areas, departments, transactions) y el canal **Laravel → BioTime** via puente Python (commands / roster / reconcile).

### Decisiones de negocio acordadas

| Tema | Decisión |
| --- | --- |
| Versión | BioTime 8.x local |
| Dispositivos | Huella + facial |
| Fuente de verdad de acceso | **Laravel** (matrícula vigente) |
| Elegibilidad | Matrícula vigente tipo **membresía o clase**; legacy `cliente_membresias` no otorga acceso físico |
| Create-if-missing | Al `activate`, el puente **crea** el empleado si no existe (`emp_code`, nombres; `company_id`/`department_id` en config) |
| Cupo | **500** empleados por sede (configurable); al llenar se **borran** clientes inelegibles en BioTime (pierde biometría; nunca staff) |
| Gracia post-vencimiento | **0 días** (corte el día en que `fecha_fin` deja de ser ≥ hoy; ver `BioTimeAccessEligibilityService`) |
| Alerta operativa | Dashboard BioTime: heartbeat/comandos/cupo por sede; permiso `biotime.ver` (CTA reconcile con `biotime.editar`) |
| Botón perfil | Solo cambia `estado_cliente` (activar requiere suscripción vigente); **no** escribe acceso BioTime |
| Objetivo | Bloquear acceso físico si no hay suscripción vigente (membresía o clase) |
| Latencia aceptable | ~1 hora (más inmediato al guardar matrícula) |
| Sedes | Varias; **cada sede** con su BioTime + su puente |
| Roster por sede | Solo clientes de **esa** sucursal |
| Identidad | `emp_code` = `cliente.codigo` Laravel |
| Inactivo en BioTime | Mover a área denegada (conserva biometría); `delete` solo para liberar cupo |
| Alta biométrica | Recepción enrolla solo en BioTime |
| Áreas | Una área BioTime autorizada = una sucursal Laravel |
| Puente | Python en el **mismo PC** que BioTime |
| Auth puente → Laravel | Token **distinto por sede** |
| Transporte | **Polling HTTPS** (sin WebSockets) |

## Problema

1. Banahosting no llega a BioTime local (NAT / sin IP pública).
2. Hosting compartido no ofrece WebSockets confiables ni procesos largos tipo queue worker garantizado.
3. WebSockets no aportan valor con latencia horaria.
4. Borrar empleados al inactivar destruiría huella/cara ya enrolladas.

## Decisión

### Opción elegida: puente Python con polling + comandos de acceso

```text
[Dispositivos] ↔ [BioTime 8] ↔ [Puente Python (PC sede)]
                                      │ HTTPS saliente (poll)
                                      ▼
                             [Laravel en Banahosting]
                             Fuente de verdad: matrícula vigente
```

**Principios:**

1. El puente **siempre inicia** la conexión hacia Internet (pull). Laravel no necesita alcanzar el gimnasio.
2. **No usar WebSockets** (ni Reverb/Pusher obligatorio) para esta integración.
3. Reutilizar el receptor entrante; API de **comandos** y **roster** por sede.
4. Inactivar = área denegada (BioTime rechaza `area: []`); activar = área autorizada de la sede (`area_biotime_id`).
5. Un puente + un token + un `area_id` BioTime por sucursal.

### Alternativas rechazadas

| Opción | Motivo de rechazo |
| --- | --- |
| WebSockets Laravel ↔ puente | Frágil/inexistente en shared hosting; innecesario con latencia horaria |
| Laravel llama API BioTime directo | Imposible/frágil: BioTime no es reachable desde Banahosting |
| Túnel VPN / ngrok permanente | Complejidad operacional y costo; innecesario si el puente hace pull |
| Borrar/recrear empleado al inactivar | Pierde biometría; peor UX de recepción |
| Fuente de verdad en BioTime | El negocio de membresías vive en Laravel |

## Contrato funcional (alto nivel)

### Laravel → puente

- `GET /api/biotime/commands` — comandos pendientes (`activate` / `deactivate` / `delete`) con `ensure_create`, `first_name`, `last_name`.
- `POST /api/biotime/commands/{id}/ack` — resultado ok/error.
- `GET /api/biotime/roster` — snapshot activo/inactivo para reconciliación periódica.
- Auth: token por sede (`VerifyBioTimeSyncToken` → `BioTimeSucursalSetting`).

### Puente → Laravel

- `POST /api/biotime/sync` — employees, areas, devices, transactions.
- Health / heartbeat: `GET /api/biotime/health?employees_count=N` actualiza `last_heartbeat_at` y cupo.

### Regla de elegibilidad (cerrada)

Un cliente está **activo para acceso** en una sucursal si y solo si:

1. Pertenece a esa sucursal (`cliente.sucursal_id`).
2. Tiene al menos una **matrícula** `tipo` en (`membresia`, `clase`), `estado = activa`, con `fecha_inicio <= hoy` y (`fecha_fin` nula o `fecha_fin >= hoy`).
3. **Sin días de gracia** después del vencimiento (implementación actual: 0 días).
4. Legacy `cliente_membresias` **no** otorga acceso físico.
5. Deuda / `estado_cliente` **no** intervienen en elegibilidad BioTime.

Detalle operativo: [08-biotime-integracion-plan.md](../plans/08-biotime-integracion-plan.md) (runbook + avance).

## Fases de implementación

Detalle paso a paso:  
→ **[docs/plans/08-biotime-integracion-plan.md](../plans/08-biotime-integracion-plan.md)**

### Resumen

| Fase (plan 08) | Contenido | Estado |
| --- | --- | --- |
| 0 PoC | Auth + áreas en BioTime 8; checklist dispositivo | Implementado (validación terminal en sede piloto según ops) |
| 1 Config por sede | `bio_time_sucursal_settings`, middleware token→sede, UI | Hecho |
| 2 API commands | `commands` / `ack` / `roster` | Hecho |
| 3 Elegibilidad | Matrícula vigente → encolar activate/deactivate | Hecho |
| 4 Puente Python | Poll + aplicar áreas + sync entrante + Windows | Hecho |
| 5 Ops | Dashboard heartbeat, runbook, ADR Aceptado | Hecho |

## Consecuencias

### Positivas

- Compatible con Banahosting y multi-sede.
- Conserva biometría al bloquear/desbloquear.
- Extiende código ya existente (`BioTimeSync*`, mapeos área↔sucursal).
- Operación simple (servicio Windows + HTTPS).

### Negativas / trade-offs

- Latencia de minutos/hora (aceptada).
- Dependencia de que el PC local y BioTime estén encendidos.
- Sync área → dispositivo debe validarse en cada sede (API ≠ terminal).
- Colisión de `emp_code` si hay personal no-cliente en BioTime → usar departamento/área de clientes o prefijo documentado.

## Referencias API BioTime 8.x

- Auth: `POST /jwt-api-token-auth/` o `POST /api-token-auth/`.
- Empleados: `/personnel/api/employees/` (GET/POST/PUT|PATCH/DELETE).
- Campos clave: `emp_code`, `area`, `company`, `department`, `app_status`, `enable_att`.
- Docs en el servidor: `/api/docs/`, `/api/personnel_docs/`.
- Manual ZKTeco BioTime 8.5 API User Manual.

## Preguntas abiertas (solo ops de sede piloto)

Las reglas de elegibilidad, gracia (0 días) y alerta en dashboard quedan **cerradas** (tabla de decisiones + código).

Pendientes operativos por sede (no bloquean el diseño):

1. Versión exacta BioTime 8 y licencia API en cada PC.
2. ¿Base BioTime vacía o con personas existentes?
3. Confirmación de clientes 100 % atados a una sede.

## Estado del módulo Laravel (post implementación)

| Pieza | Estado |
| --- | --- |
| `POST /api/biotime/sync` | Existe |
| Jobs por entidad + `BioTimeSyncService` | Existe |
| Settings / token por sede | Existe (`BioTimeSucursalSetting`) |
| Mapeos área/device → sucursal | Existe |
| `cliente.biotime_id` / `emp_code` linking | Existe |
| UI dashboard + sedes + widget Checking | Existe |
| API commands / ack / roster | Existe |
| Elegibilidad → comandos de área | Existe |
| Puente Python (`tools/biotime-bridge`) | Existe |
| Panel ops (heartbeat, pending/failed, reconcile) | Existe |
