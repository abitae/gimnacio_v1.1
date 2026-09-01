# ADR: Clasificación de BioTime en navegación

**Estado:** Aceptado (actualizado 2026-08-27 para reflejar la implementación real; decisión original 2026-06-24 más abajo)  
**Fecha:** 2026-06-24 (decisión inicial) · 2026-08-27 (verificación e implementación final)  
**Contexto:** INC-05 — BioTime estaba en Administración pero breadcrumbs lo agrupaban con operación diaria.

## Decisión final implementada (verificado 2026-08-27)

Con el avance del módulo BioTime (integración por sede, comandos, puente Python — ver [`08-biotime-integracion-plan.md`](../plans/08-biotime-integracion-plan.md), fases 0-5 completas), la implementación terminó adoptando una **Opción C** no contemplada en la decisión original: BioTime se expone como su **propio grupo de sidebar de nivel superior** (`heading="BioTime"`), independiente de Operaciones y de Administración. Los breadcrumbs (`breadcrumbs.blade.php`) también lo tratan como sección propia "BioTime".

Motivo probable del cambio respecto a la Opción A original: el módulo creció lo suficiente (configuración por sede, dashboard, mapeos, historial, API) como para justificar su propio espacio de navegación en vez de convivir dentro de Operaciones.

La configuración avanzada (token por sede, mapeos, área BioTime) permanece en `BioTimeDashboard` bajo permiso `biotime.ver`/`biotime.editar`. El uso diario de recepción sigue viendo el estado de sincronización desde el widget en Checking.

## Decisión original (2026-06-24, ya superada por el código)

**Opción A:** BioTime se expone en el grupo sidebar **Operaciones**, junto a Checking, Caja y POS.

## Consecuencias

- Recepción encuentra BioTime sin entrar a Administración (logrado, aunque vía grupo propio en vez de vía Operaciones).
- Breadcrumbs usan el label **Operaciones** para Checking/Caja/POS; BioTime tiene su propio label «BioTime».
- Administración conserva empleados, usuarios, roles y métodos de pago.

## Alternativa rechazada (en la decisión original)

Opción B (solo Administración + widget en Checking): descartada para reducir fricción operativa; el widget en Checking se implementa igualmente.

## Ver también

- [adr-biotime-puente-acceso.md](./adr-biotime-puente-acceso.md) — puente Python, polling y control de acceso Laravel → BioTime.
- [module-consistency-matrix.md](./module-consistency-matrix.md) — sección "8. BioTime", refresco 2026-08-27.
