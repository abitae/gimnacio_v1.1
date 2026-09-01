# Planes de mejora por modulo

Documentacion derivada del analisis en [`architecture/module-consistency-matrix.md`](../architecture/module-consistency-matrix.md).

Cada plan describe el estado actual, objetivos, fases, pasos accionables, criterios de aceptacion y dependencias entre modulos.

## Orden de implementacion recomendado

| Orden | Modulo | Prioridad | Archivo |
| --- | --- | --- | --- |
| 1 | Operaciones (Operacion diaria) | Alta | [00-operaciones-plan-mejora.md](./00-operaciones-plan-mejora.md) |
| 1b | Caja — integridad transaccional | Alta | [09-caja-integridad-plan-mejora.md](./09-caja-integridad-plan-mejora.md) |
| 2 | Clientes | Alta | [01-clientes-plan-mejora.md](./01-clientes-plan-mejora.md) |
| 3 | Analitica | Alta | [05-analitica-plan-mejora.md](./05-analitica-plan-mejora.md) |
| 4 | Bienestar | Alta | [02-bienestar-plan-mejora.md](./02-bienestar-plan-mejora.md) |
| 5 | Comercial | Alta | [03-comercial-plan-mejora.md](./03-comercial-plan-mejora.md) |
| 5b | Comercial — UI/UX | Alta | [03-comercial-ui-ux-plan-mejora.md](./03-comercial-ui-ux-plan-mejora.md) |
| 6 | Recursos | Media | [04-recursos-plan-mejora.md](./04-recursos-plan-mejora.md) |
| 7 | Administracion | Media | [06-administracion-plan-mejora.md](./06-administracion-plan-mejora.md) |
| 8 | Plataforma | Media | [07-plataforma-plan-mejora.md](./07-plataforma-plan-mejora.md) |
| 9 | BioTime (integracion acceso) | Alta | [08-biotime-integracion-plan.md](./08-biotime-integracion-plan.md) |

## Plan transversal

| Documento | Contenido |
| --- | --- |
| [99-transversal-plan-mejora.md](./99-transversal-plan-mejora.md) | Agregadores compartidos, legacy, multi-sucursal, nomenclatura y criterios globales |
| [99-css-colores-plan-mejora.md](./99-css-colores-plan-mejora.md) | Pipeline de build CSS, sistema de personalizacion de tema en runtime, eliminacion de dark mode, tokens de color de estado |
| [08-biotime-integracion-plan.md](./08-biotime-integracion-plan.md) | Config BioTime por sedes, API commands/roster, elegibilidad por matricula, puente Python + prompts Cursor |

## Convenciones de los planes

- **Fase:** bloque de trabajo con objetivo de negocio/arquitectura claro.
- **Paso:** unidad implementable en una o pocas PRs.
- **Criterios de aceptacion:** condiciones verificables antes de dar el paso por cerrado.
- **Dependencias:** modulos o pasos que deben completarse antes.

## Estado global (snapshot 2026-08-27, refresco del 2026-06-24)

- Agregadores perfil cliente (`ClienteCommercialProfileService`, `ClienteWellnessProfileService`, `ClienteCrmProfileService`): **implementados**. `ClientePerfilLive` corrección de cifra (2026-08-27): **1.159 LOC** (volvió a crecer respecto a la medición previa de ~934; ver `01-clientes-plan-mejora.md` Fase 6 sobre causas de rendimiento/tamaño).
- **Nuevo (2026-08-27):** revisión profunda de Caja y Clientes encontró deuda de integridad real, no solo arquitectónica — ver [`09-caja-integridad-plan-mejora.md`](./09-caja-integridad-plan-mejora.md) (condición de carrera en apertura/cierre de caja, bypass de aislamiento multi-sucursal, cierre sin permiso granular) y Fases 4-8 de [`01-clientes-plan-mejora.md`](./01-clientes-plan-mejora.md) (borrado de cliente que hoy elimina en silencio cuenta de app/fidelización/traspasos, código muerto `ClienteRequest.php`).
- `DailyOperationsDebtService`: implementado.
- `ReporteCuentasPorCobrarLive`: implementado y desacoplado de `POS\CustomerDebts` (permiso propio `reporte.cuentas_por_cobrar`). `FinanceAnalyticsService` y demas servicios analiticos dedicados (`SalesAnalyticsService`, `ClientAnalyticsService`, `CajaAnalyticsService`): **no implementados**, `ReporteModuloService` sigue centralizando todo.
- Permiso `checking.ver`: implementado.
- `PlanFreezeService` (congelamiento de planes, INC-08): extraido de `ClientWellnessService` — parcial, vive en namespace `Cliente`.
- `RentalService` como fuente unica de **escritura** de reservas: implementado (POS, bienestar y Recursos ya delegan ahi); persiste la UI de entrada triple.
- Integracion BioTime (`08-biotime-integracion-plan.md`): **fases 0-5 completas**; BioTime es ahora grupo de sidebar propio (no Operaciones ni Administracion). Solo quedan 2 checklist de validacion de campo.
- Mega-componentes: `POSLive` (~1.142 LOC) y `GestionNutricionalUnificadoLive` (~876 LOC) **sin avance real** de fragmentacion — siguen siendo el mayor riesgo tecnico del sistema.
- Permisos CRM (INC-10) y etiquetado de registros importados (Plataforma): **sin avance**.
- Hallazgo nuevo: `AuditLog` esta modelado pero **inerte** (cero usos en `app/`) — ningun cambio critico de administracion queda auditado.

Detalle completo de la verificacion en [`../architecture/module-consistency-matrix.md`](../architecture/module-consistency-matrix.md).
