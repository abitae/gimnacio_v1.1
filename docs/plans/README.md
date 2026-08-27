# Planes de mejora por modulo

Documentacion derivada del analisis en [`architecture/module-consistency-matrix.md`](../architecture/module-consistency-matrix.md).

Cada plan describe el estado actual, objetivos, fases, pasos accionables, criterios de aceptacion y dependencias entre modulos.

## Orden de implementacion recomendado

| Orden | Modulo | Prioridad | Archivo |
| --- | --- | --- | --- |
| 1 | Operaciones (Operacion diaria) | Alta | [00-operaciones-plan-mejora.md](./00-operaciones-plan-mejora.md) |
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
| [08-biotime-integracion-plan.md](./08-biotime-integracion-plan.md) | Config BioTime por sedes, API commands/roster, elegibilidad por matricula, puente Python + prompts Cursor |

## Convenciones de los planes

- **Fase:** bloque de trabajo con objetivo de negocio/arquitectura claro.
- **Paso:** unidad implementable en una o pocas PRs.
- **Criterios de aceptacion:** condiciones verificables antes de dar el paso por cerrado.
- **Dependencias:** modulos o pasos que deben completarse antes.

## Estado global (snapshot 2026-06-24)

- Agregadores perfil cliente: **implementados** (Fase 1)
- `DailyOperationsDebtService`: implementado
- `ReporteCuentasPorCobrarLive` / `FinanceAnalyticsService`: implementados
- Permiso `checking.ver`: implementado
- Servicios Wellness / PlanFreeze / RentalService unificado: **parcial**
- Mega-componentes: desacople en progreso
