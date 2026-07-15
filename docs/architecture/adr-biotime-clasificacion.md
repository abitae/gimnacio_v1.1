# ADR: Clasificación de BioTime en navegación

**Estado:** Aceptado  
**Fecha:** 2026-06-24  
**Contexto:** INC-05 — BioTime estaba en Administración pero breadcrumbs lo agrupaban con operación diaria.

## Decisión

**Opción A:** BioTime se expone en el grupo sidebar **Operaciones**, junto a Checking, Caja y POS.

La configuración avanzada (token, mapeos) permanece en `BioTimeDashboard` bajo permiso `biotime.ver`. El uso diario de recepción es ver estado de sincronización desde Checking.

## Consecuencias

- Recepción encuentra BioTime sin entrar a Administración.
- Breadcrumbs usan el label **Operaciones** (no «Operación diaria»).
- Administración conserva empleados, usuarios, roles y métodos de pago.

## Alternativa rechazada

Opción B (solo Administración + widget en Checking): descartada para reducir fricción operativa; el widget en Checking se implementa igualmente.

## Ver también

- [adr-biotime-puente-acceso.md](./adr-biotime-puente-acceso.md) — puente Python, polling y control de acceso Laravel → BioTime.
