# ADR: Puente BioTime ↔ Laravel (control de acceso)

**Estado:** Propuesto  
**Fecha:** 2026-07-15  
**Relacionado:** [adr-biotime-clasificacion.md](./adr-biotime-clasificacion.md), plan Operaciones Fase 3, plan ejecutable [08-biotime-integracion-plan.md](../plans/08-biotime-integracion-plan.md)

## Contexto

El gimnasio usa **BioTime 8.x** on-premise (puerto `8090`) en cada sede, con dispositivos de **huella** y **facial**. Laravel corre en **Banahosting** (hosting compartido): el servidor no puede abrir conexiones entrantes hacia la red local del gimnasio.

Ya existe sync **BioTime → Laravel** (`POST /api/biotime/sync`: employees, devices, areas, departments, transactions). Falta el canal **Laravel → BioTime** para reflejar quién puede o no entrar.

### Decisiones de negocio acordadas

| Tema | Decisión |
| --- | --- |
| Versión | BioTime 8.x local (`:8090`) |
| Dispositivos | Huella + facial |
| Fuente de verdad de acceso | **Laravel** (membresía/matrícula vigente) |
| Objetivo | Bloquear acceso físico si no hay membresía vigente |
| Latencia aceptable | ~1 hora |
| Sedes | Varias; **cada sede** con su BioTime + su puente |
| Roster por sede | Solo clientes de **esa** sucursal |
| Identidad | `emp_code` = `cliente.id` Laravel |
| Inactivo en BioTime | **Quitar áreas** (no borrar empleado; conserva biometría) |
| Alta biométrica | Recepción enrolla solo en BioTime |
| Áreas | Una área BioTime = una sucursal Laravel |
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
[Dispositivos] ↔ [BioTime 8 :8090] ↔ [Puente Python (PC sede)]
                                            │ HTTPS saliente (poll)
                                            ▼
                                   [Laravel en Banahosting]
                                   Fuente de verdad: membresía vigente
```

**Principios:**

1. El puente **siempre inicia** la conexión hacia Internet (pull). Laravel no necesita alcanzar el gimnasio.
2. **No usar WebSockets** (ni Reverb/Pusher obligatorio) para esta integración.
3. Reutilizar el receptor entrante actual; añadir API de **comandos** y **roster**.
4. Inactivar = `area: []` (o área no autorizada); activar = asignar área de la sede.
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

### Laravel → puente (nuevo)

- `GET /api/biotime/commands` — comandos pendientes de la sede autenticada (`activate` / `deactivate`).
- `POST /api/biotime/commands/{id}/ack` — resultado ok/error.
- `GET /api/biotime/roster` — snapshot activo/inactivo para reconciliación periódica.
- Auth: token por sede (extender patrón de `VerifyBioTimeSyncToken` / `BioTimeSetting`).

### Puente → Laravel (existente)

- `POST /api/biotime/sync` — employees, areas, devices, transactions (sin cambio de dirección).
- Heartbeat implícito vía `last_received_at` / futuro endpoint de salud del agente.

### Regla de elegibilidad

Un cliente está **activo para acceso** en una sucursal si tiene **matrícula vigente** (tipo membresía) en esa sede. Legacy `cliente_membresias` no otorga acceso físico. Gracia por defecto: 0 días. Detalle ejecutivo: [08-biotime-integracion-plan.md](../plans/08-biotime-integracion-plan.md).

## Fases de implementación

Detalle paso a paso, módulo config por sede y **prompts Cursor**:  
→ **[docs/plans/08-biotime-integracion-plan.md](../plans/08-biotime-integracion-plan.md)**

### Resumen

| Fase (plan 08) | Contenido |
| --- | --- |
| 0 PoC | Auth + áreas en BioTime 8; checklist dispositivo |
| 1 Config por sede | `bio_time_sucursal_settings`, middleware token→sede, UI |
| 2 API commands | `commands` / `ack` / `roster` |
| 3 Elegibilidad | Matrícula vigente → encolar activate/deactivate |
| 4 Puente Python | Poll + aplicar áreas + sync entrante + Windows |
| 5 Ops | Dashboard heartbeat, runbook, ADR Aceptado |

## Consecuencias

### Positivas

- Compatible con Banahosting y multi-sede.
- Conserva biometría al bloquear/desbloquear.
- Extiende código ya existente (`BioTimeSync*`, mapeos área↔sucursal).
- Operación simple (servicio Windows + HTTPS).

### Negativas / trade-offs

- Latencia de minutos/hora (aceptada).
- Dependencia de que el PC local y BioTime estén encendidos.
- Hay que validar en PoC el sync área → dispositivo (no solo API).
- Colisión de `emp_code` si hay personal no-cliente en BioTime → usar departamento/área de clientes o prefijo documentado.

## Referencias API BioTime 8.x

- Auth: `POST /jwt-api-token-auth/` o `POST /api-token-auth/`.
- Empleados: `/personnel/api/employees/` (GET/POST/PUT|PATCH/DELETE).
- Campos clave: `emp_code`, `area`, `app_status`, `enable_att`.
- Docs en el servidor: `/api/docs/`, `/api/personnel_docs/`.
- Manual ZKTeco BioTime 8.5 API User Manual.

## Preguntas abiertas (cerradas en el plan ejecutable, salvo ops)

Resueltas en [08-biotime-integracion-plan.md](../plans/08-biotime-integracion-plan.md):

1. Elegibilidad = **solo matrícula vigente** (legacy no otorga acceso físico).
2. Gracia por defecto = **0 días** (ajustar solo si el código lo documenta).
3. Alerta = dashboard BioTime (`biotime.ver`).
4. Alcance de esta integración = **bloqueo de acceso** (+ sync transacciones ya existente).

Pendientes de sede piloto (ops, no bloquean el diseño):

1. Versión exacta BioTime 8 y licencia API.
2. ¿Base BioTime vacía o con personas existentes?
3. Confirmación de clientes 100 % atados a una sede.

## Estado del módulo Laravel actual (baseline)

| Pieza | Estado |
| --- | --- |
| `POST /api/biotime/sync` | Existe |
| Jobs por entidad + `BioTimeSyncService` | Existe |
| Mapeos área/device → sucursal | Existe |
| `cliente.biotime_id` / `emp_code` linking | Existe |
| UI dashboard + widget Checking | Existe |
| API commands / roster / puente Python | **No existe** |
| Elegibilidad → área BioTime | **No existe** |
