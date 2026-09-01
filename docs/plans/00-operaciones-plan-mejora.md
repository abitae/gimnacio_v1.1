# Plan de mejora: Operaciones (Operacion diaria)

> **Referencia:** [module-consistency-matrix.md](../architecture/module-consistency-matrix.md)  
> **Prioridad:** Alta (orden global #1)  
> **Inconsistencias vinculadas:** INC-01, INC-02, INC-03, INC-04, INC-05  
> **Ultima actualizacion:** 2026-08-27 (refresco de 2026-06-24)  
> **Estado implementacion:** Fase 1 y Fase 2 (parcial) + Fase 3 (parcial) ejecutadas en codigo — ver seccion «Avance de implementacion» al final. **Nota de refresco:** `POSLive` sigue en ~1.142 LOC (paso 1.4 sin resolver); es el pendiente real de mayor prioridad de todo el sistema.

---

## 1. Contexto y diagnostico

### Alcance funcional
Acceso/checking, caja, punto de venta, ventas a credito, cobros pendientes, comprobantes y tickets.

### Estado actual

| Elemento | Situacion |
| --- | --- |
| `DailyOperationsDebtService` | Implementado; usado en checking, POS y ficha cliente |
| `ClientDebtService` | Implementado; cobro transaccional |
| `POSLive` | ~1.202 LOC; mayor deuda del modulo |
| `CustomerDebts` | Compartido con Analitica (debe quedar solo operativo) |
| `checking.index` | Sin permiso granular |
| BioTime | Fuera del grupo sidebar Operaciones |

### Riesgo principal
Cualquier cambio en `POSLive` impacta ventas, credito, cupones, reservas y tickets simultaneamente.

### Fuente de verdad objetivo
`cajas`, `caja_movimientos`, `ventas`, `venta_items`, `pagos`, `client_debts`, `asistencias`

---

## 2. Objetivos

1. Reducir `POSLive` a un orquestador delgado (<400 LOC).
2. Consolidar resumen de deuda en `DailyOperationsDebtService` como unica fuente operativa.
3. Reservar `CustomerDebts` exclusivamente para cobranza transaccional.
4. Alinear permisos y nomenclatura de navegacion.
5. Integrar BioTime con checking de forma coherente.

---

## 3. Plan por fases

### Fase 1 — Desacople critico (POS y cobranza)

**Objetivo de fase:** Eliminar el acoplamiento mas peligroso sin cambiar flujos visibles al usuario final.

#### Paso 1.1 — Inventario y mapa de responsabilidades de POSLive

- **Objetivo:** Documentar en codigo que hace cada bloque de `POSLive` antes de extraer.
- **Archivos:** `app/Livewire/POS/POSLive.php`, vista Blade asociada.
- **Tareas:**
  1. Listar propiedades publicas y metodos por dominio: carrito, productos, servicios, matriculas, cupones, credito, reservas, cliente, pagos, tickets.
  2. Identificar consultas Eloquent directas vs llamadas a servicios.
  3. Crear comentario de seccion temporal en el componente (o doc interno) con el mapa.
- **Criterios de aceptacion:**
  - Mapa completo con al menos 5 dominios identificados.
  - Cada metodo publico clasificado como UI, orquestacion o logica de negocio.
- **Dependencias:** Ninguna.

#### Paso 1.2 — Extraer `PosCartService` (carrito y totales)

- **Objetivo:** Centralizar calculo de lineas, descuentos, impuestos y total.
- **Archivos nuevos:** `app/Services/Pos/PosCartService.php`, DTO opcional `app/Data/Pos/CartState.php`.
- **Archivos a modificar:** `POSLive.php`.
- **Tareas:**
  1. Mover logica de agregar/quitar items, aplicar cupon y calcular totales al servicio.
  2. `POSLive` solo mantiene estado serializable minimo o delega estado al servicio via session/cache por usuario.
  3. Tests unitarios para totales, cupones y lineas mixtas.
- **Criterios de aceptacion:**
  - `POSLive` no contiene formulas de totalizacion.
  - Tests cubren al menos: venta simple, cupon, multiples items.
- **Dependencias:** Paso 1.1.

#### Paso 1.3 — Extraer `PosSaleOrchestrator` (confirmacion de venta)

- **Objetivo:** Unificar el flujo de confirmacion que hoy llama a `VentaService` desde multiples ramas.
- **Archivos nuevos:** `app/Services/Pos/PosSaleOrchestrator.php`.
- **Archivos a modificar:** `POSLive.php`, `VentaService.php` (solo si hace falta interfaz mas clara).
- **Tareas:**
  1. Definir metodo `completeSale(CartState $cart, PaymentContext $payment): Venta`.
  2. Encapsular validaciones de caja abierta, cliente requerido, stock (cuando exista).
  3. Manejar venta contado vs credito en un solo punto.
- **Criterios de aceptacion:**
  - Un solo entry point para confirmar venta desde POS.
  - Feature test: venta contado y venta credito siguen funcionando.
- **Dependencias:** Paso 1.2.

#### Paso 1.4 — Extraer sub-componentes Livewire anidados

- **Objetivo:** Dividir UI de POS en piezas reutilizables.
- **Archivos nuevos sugeridos:**
  - `app/Livewire/POS/Concerns/ManagesPosCart.php` (trait)
  - `app/Livewire/POS/Concerns/ManagesPosCustomer.php` (trait)
  - `app/Livewire/POS/Components/ProductPicker.php` (opcional, Livewire anidado)
- **Tareas:**
  1. Extraer busqueda/seleccion de cliente a trait o componente.
  2. Extraer panel de productos/servicios.
  3. Extraer modal de pago.
  4. Objetivo: `POSLive` < 500 LOC al cerrar este paso.
- **Criterios de aceptacion:**
  - LOC de `POSLive` reducido al menos 40%.
  - Sin regresion en flujo manual de caja abierta → venta → comprobante.
- **Dependencias:** Pasos 1.2, 1.3.

#### Paso 1.5 — Reservar CustomerDebts para operacion

- **Objetivo:** Dejar claro que `CustomerDebts` es bandeja operativa, no reporte.
- **Archivos:** `routes/web.php`, `CustomerDebts.php`, sidebar, breadcrumbs.
- **Tareas:**
  1. Documentar en componente que ruta `reportes.cuentas-por-cobrar` sera reemplazada (coordinar con plan Analitica).
  2. Asegurar que acciones de cobro requieren `punto_venta.ver` o permiso de cobro dedicado.
  3. Agregar banner contextual en vista cuando se accede desde reportes (temporal hasta migracion analitica).
- **Criterios de aceptacion:**
  - Cobro desde reportes identificado como deuda tecnica documentada.
  - Permisos de cobro verificados en tests.
- **Dependencias:** Coordinacion con [05-analitica-plan-mejora.md](./05-analitica-plan-mejora.md) Paso 1.1.

#### Paso 1.6 — Consolidar DailyOperationsDebtService

- **Objetivo:** Evitar duplicacion de calculos de saldo en POS, checking y ficha.
- **Archivos:** `DailyOperationsDebtService.php`, consumidores en `CheckingLive`, `POSLive`, `ClientePerfilLive`.
- **Tareas:**
  1. Definir contrato de salida unificado: `{ total, vencido, por_vencer, items[] }`.
  2. Eliminar calculos paralelos en componentes.
  3. Tests de consistencia: mismo cliente → mismo resumen en checking y POS.
- **Criterios de aceptacion:**
  - Un solo metodo publico de resumen por cliente.
  - Test de paridad entre modulos consumidores.
- **Dependencias:** Ninguna critica; puede paralelizarse con 1.2.

---

### Fase 2 — Permisos, caja y trazabilidad

**Objetivo de fase:** Endurecer seguridad operativa y trazabilidad por sucursal.

#### Paso 2.1 — Permiso checking.ver

- **Objetivo:** Restringir acceso a checking como el resto de modulos operativos.
- **Archivos:** `database/seeders/*Permission*`, `routes/web.php`, `sidebar.blade.php`, roles existentes.
- **Tareas:**
  1. Crear permiso `checking.ver` (y opcionalmente `checking.crear`, `checking.editar` si no estan centralizados).
  2. Agregar middleware `permission:checking.ver` a `checking.index`.
  3. Actualizar semilla de roles: recepcion, admin, etc.
  4. Ocultar item sidebar si usuario no tiene permiso.
- **Criterios de aceptacion:**
  - Usuario sin permiso recibe 403 en ruta.
  - Tests de autorizacion.
- **Dependencias:** Ninguna.

#### Paso 2.2 — Unificar nomenclatura Operaciones / Operacion diaria

- **Objetivo:** Resolver INC-01.
- **Archivos:** `sidebar.blade.php`, `breadcrumbs.blade.php`, docs.
- **Tareas:**
  1. Elegir label canonico (recomendado: **Operaciones** en UI, **Operacion diaria** solo en docs de arquitectura).
  2. Aplicar mismo termino en breadcrumbs.
  3. Actualizar matriz de consistencia.
- **Criterios de aceptacion:**
  - Un solo label visible para el usuario en sidebar y breadcrumbs.
- **Dependencias:** Ninguna.

#### Paso 2.3 — Trazabilidad de caja por sucursal

- **Objetivo:** Garantizar que movimientos de caja no crucen sucursales.
- **Archivos:** `CajaService.php`, `CajaLive.php`, modelos `Caja`, `CajaMovimiento`.
- **Tareas:**
  1. Auditar queries sin filtro `sucursal_id`.
  2. Forzar scope de sucursal activa via `SucursalContext`.
  3. Agregar test: caja de sucursal A no visible en sucursal B.
- **Criterios de aceptacion:**
  - 100% queries de caja filtradas por sucursal en servicio.
- **Dependencias:** Ninguna.

#### Paso 2.4 — Refinar CreditSales y PaymentForm (cuotas)

- **Objetivo:** Alinear ventas a credito y cobro de cuotas con mismos agregadores de deuda.
- **Archivos:** `CreditSales.php`, `Enrollments/Installments/PaymentForm.php`, `EnrollmentInstallmentService.php`.
- **Tareas:**
  1. Reutilizar `DailyOperationsDebtService` para badges de estado.
  2. Reutilizar `ClientDebtService` / flujo de pago unificado donde aplique.
  3. Enlazar desde ficha cliente como atajo, no como logica duplicada.
- **Criterios de aceptacion:**
  - Saldo mostrado en credit sales coincide con ficha cliente.
- **Dependencias:** Paso 1.6.

---

### Fase 3 — Integracion BioTime y acceso

**Objetivo de fase:** Coherencia entre acceso biometrico y checking manual.

#### Paso 3.1 — Decision de clasificacion BioTime

- **Objetivo:** Resolver INC-05 con decision explicita documentada.
- **Opciones:**
  - **A)** Mover BioTime al grupo Operaciones (recomendado si es uso diario recepcion).
  - **B)** Mantener en Administracion como configuracion de dispositivos + widget en Checking.
- **Tareas:**
  1. Documentar decision en ADR breve en `docs/architecture/`.
  2. Ajustar sidebar y breadcrumbs segun opcion.
- **Criterios de aceptacion:**
  - Decision registrada y aplicada en navegacion.
- **Dependencias:** Coordinacion con [06-administracion-plan-mejora.md](./06-administracion-plan-mejora.md).

#### Paso 3.2 — Puente BioTime → Asistencia

- **Objetivo:** Sincronizacion visible desde checking.
- **Archivos:** `BioTimeSyncService.php`, `CheckingLive.php`, `AsistenciaService.php`.
- **Tareas:**
  1. Exponer ultima sync y estado en panel de checking (solo lectura).
  2. Marcar asistencias con origen `manual` vs `biotime`.
  3. Evitar doble ingreso mismo dia mismo cliente.
- **Criterios de aceptacion:**
  - Checking muestra origen de ultima asistencia.
  - Regla anti-duplicado testeada.
- **Dependencias:** Paso 3.1.

#### Paso 3.3 — PosAlquilerReservaService como unico escritor desde POS

- **Objetivo:** Reservas desde POS no dupliquen logica de Recursos.
- **Archivos:** `PosAlquilerReservaService.php`, `RentalService.php`.
- **Tareas:**
  1. Delegar toda escritura de `Rental` a `RentalService`.
  2. POS solo invoca servicio con DTO de reserva.
- **Criterios de aceptacion:**
  - POS no hace `Rental::create` directo.
- **Dependencias:** [04-recursos-plan-mejora.md](./04-recursos-plan-mejora.md) Paso 1.2.

---

## 4. Riesgos y mitigaciones

| Riesgo | Mitigacion |
| --- | --- |
| Regresion en ventas al fragmentar POS | Feature tests por tipo de venta antes de extraer |
| Downtime en caja | Desplegar por pasos; feature flags si es necesario |
| Permiso checking bloquea usuarios | Migracion de roles en semilla + script one-off |

---

## 5. Criterios de cierre del modulo

- [ ] `POSLive` < 400 LOC o justificado con sub-componentes
- [ ] `DailyOperationsDebtService` es unica fuente de resumen operativo
- [ ] `CustomerDebts` solo en rutas `pos.*`
- [ ] Permiso `checking.ver` activo
- [ ] BioTime clasificado y reflejado en navegacion
- [ ] Trazabilidad caja/ventas por sucursal verificada

---

## 6. Dependencias con otros modulos

| Modulo | Dependencia |
| --- | --- |
| Analitica | Reemplazo de `CustomerDebts` en reportes |
| Clientes | Ficha consume mismo resumen de deuda |
| Recursos | `RentalService` unificado para reservas POS |
| Administracion | Clasificacion BioTime |

---

## Avance de implementacion (2026-06-24)

### Completado
- [x] **1.2** `PosCartService`, `CartTotals`, trait `ManagesPosCartTotals`
- [x] **1.3** `PosSaleOrchestrator` — unico entry point de confirmacion de venta
- [x] **1.5** `CustomerDebts` solo operativo (`punto_venta.ver`); reporte analitico separado
- [x] **1.6** Eliminado codigo muerto en `cargarItemsConSaldo`; consolidado `DailyOperationsDebtService`
- [x] **2.1** Permiso `checking.ver` en ruta y sidebar
- [x] **2.2** Breadcrumbs usan label **Operaciones**
- [x] **3.1** ADR BioTime — **corregido 2026-08-27:** la decision final NO fue moverlo al grupo Operaciones; BioTime quedo como grupo de sidebar propio, independiente. Ver `docs/architecture/adr-biotime-clasificacion.md` (actualizado).
- [x] **3.2** Panel ultima sync BioTime en Checking; origen en asistencias recientes
- [x] **3.3** `PosAlquilerReservaService` ya delegaba a `RentalService` (verificado)
- [x] Reporte analitico: `ReporteCuentasPorCobrarLive` + `FinanceAnalyticsService`
- [x] Tests unitarios `PosCartServiceTest`

### Pendiente (siguiente iteracion)
- [ ] **1.4** Extraer traits adicionales (`ManagesPosCustomer`, cobro membresia) — objetivo POSLive < 500 LOC
- [ ] **2.3** Test automatizado trazabilidad caja por sucursal
- [ ] **2.4** `CreditSales` / `PaymentForm` badges via `DailyOperationsDebtService`
- [ ] Regla anti-duplicado BioTime + ingreso manual mismo dia (mas alla de ingreso en curso)
