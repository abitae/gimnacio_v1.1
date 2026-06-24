# Plan de mejora: Recursos

> **Referencia:** [module-consistency-matrix.md](../architecture/module-consistency-matrix.md)  
> **Prioridad:** Media (orden global #6)  
> **Ultima actualizacion:** 2026-06-24

---

## 1. Contexto y diagnostico

### Alcance funcional
Catalogo de productos (categorias, stock), servicios externos, espacios alquilables, calendario, reservas, reporte de ingresos por alquiler.

### Estado actual

| Elemento | Situacion |
| --- | --- |
| `ProductoService` / `ProductoLive` | Bien encapsulados |
| `InventarioService` | ~101 LOC; delgado |
| `RentalService` | Existe; no es unico punto de escritura |
| Puntos de reserva | Recursos, POS, Bienestar, Ficha cliente (4 entradas) |
| `ServicioExterno` | CRUD basico; poca trazabilidad operativa |

### Riesgo principal
Stock fragil en POS; reservas duplicadas entre modulos.

### Fuentes de verdad objetivo
`productos`, `movimientos_inventario`, `servicios_externos`, `rentable_spaces`, `rentable_space_rates`, `rentals`, `rental_payments`

---

## 2. Objetivos

1. Dividir modulo en: catalogo, inventario, alquileres.
2. `RentalService` como unico escritor de reservas.
3. `InventarioService` robusto con movimientos auditables.
4. Bandeja operativa de alquileres del dia.
5. Servicios externos con trazabilidad comercial minima.

---

## 3. Plan por fases

### Fase 1 — Unificacion de alquileres

**Objetivo de fase:** Una sola forma de crear/editar/cancelar reservas.

#### Paso 1.1 — Auditar puntos de escritura Rental

- **Objetivo:** Inventario de todos los `Rental::create/update/delete`.
- **Archivos a buscar:** `PosAlquilerReservaService`, `ClientWellnessService`, `ClientePerfilLive`, `Rentals/Bookings/Form`, `RentalService`.
- **Tareas:**
  1. Grep y documentar cada caller.
  2. Clasificar: valido vs debe migrar.
- **Criterios de aceptacion:**
  - Lista completa de call sites documentada.

#### Paso 1.2 — Fortalecer RentalService como API unica

- **Objetivo:** Todos los modulos delegan aqui.
- **Archivos:** `app/Services/RentalService.php`.
- **Metodos sugeridos:**
  ```text
  createBooking(RentalBookingData $data): Rental
  updateBooking(Rental $rental, RentalBookingData $data): Rental
  cancelBooking(Rental $rental, string $reason): void
  checkAvailability(int $spaceId, Carbon $start, Carbon $end): bool
  listForCliente(int $clienteId): Collection
  listForDate(Carbon $date, ?int $sucursalId): Collection
  ```
- **Tareas:**
  1. Validaciones: solapamiento, tarifas, cliente activo, sucursal.
  2. Eventos opcionales: `RentalCreated`, `RentalCancelled`.
  3. Tests unitarios disponibilidad y solapamiento.
- **Criterios de aceptacion:**
  - 100% creaciones pasan por servicio tras migracion.

#### Paso 1.3 — Migrar PosAlquilerReservaService

- **Objetivo:** POS delega a RentalService.
- **Archivos:** `PosAlquilerReservaService.php`, `POSLive.php`.
- **Criterios de aceptacion:**
  - Reserva desde POS visible en calendario Recursos.
- **Dependencias:** Paso 1.2; [00-operaciones-plan-mejora.md](./00-operaciones-plan-mejora.md).

#### Paso 1.4 — Migrar bienestar y ficha cliente

- **Objetivo:** Eliminar escritura directa en wellness/perfil.
- **Archivos:** `ClientWellnessService`, `ClientePerfilLive`, `GestionNutricionalUnificadoLive`.
- **Tareas:**
  1. Modales reserva llaman `RentalService::createBooking`.
  2. UI puede permanecer como atajo contextual.
- **Criterios de aceptacion:**
  - Cero `Rental::create` fuera RentalService y Form Recursos.
- **Dependencias:** [02-bienestar-plan-mejora.md](./02-bienestar-plan-mejora.md), [01-clientes-plan-mejora.md](./01-clientes-plan-mejora.md).

#### Paso 1.5 — Bandeja operativa de alquileres

- **Objetivo:** Vista operativa del dia para recepcion.
- **Archivos nuevos:** `app/Livewire/Rentals/Operations/Dashboard.php` (o extender Calendar).
- **Contenido:**
  - Reservas hoy / proximas 48h
  - Pendientes confirmacion
  - Pagos pendientes (`rental_payments`)
  - Espacios ocupados ahora
- **Ruta sugerida:** `rentals.operations.index` bajo permiso `alquiler.ver`.
- **Criterios de aceptacion:**
  - Recepcion ve estado del dia sin abrir calendario mensual.

---

### Fase 2 — Inventario y stock

**Objetivo de fase:** Movimientos consistentes con ventas POS.

#### Paso 2.1 — Modelo de movimientos de inventario

- **Objetivo:** Definir tipos de movimiento.
- **Tipos sugeridos:** `entrada`, `salida_venta`, `ajuste`, `devolucion`.
- **Archivos:** `MovimientoInventario`, `InventarioService`.
- **Tareas:**
  1. Enum o constantes de tipo movimiento.
  2. Cada movimiento: producto, cantidad, referencia (venta_id opcional), usuario, sucursal.
- **Criterios de aceptacion:**
  - Migracion si faltan columnas; tipos documentados.

#### Paso 2.2 — Expandir InventarioService

- **Objetivo:** API clara para stock.
- **Metodos sugeridos:**
  ```text
  getStock(Producto $producto): int
  registerEntry(...)
  registerSaleExit(VentaItem $item): void
  registerAdjustment(...)
  getLowStockProducts(int $threshold): Collection
  ```
- **Tareas:**
  1. Stock calculado desde movimientos o campo denormalizado `stock_actual` sincronizado.
  2. Transacciones DB en venta + movimiento.
- **Criterios de aceptacion:**
  - Test: venta POS reduce stock correctamente.

#### Paso 2.3 — Integrar con VentaService / POS

- **Objetivo:** Venta producto fisico descuenta inventario.
- **Archivos:** `VentaService.php`, `PosSaleOrchestrator` (cuando exista).
- **Tareas:**
  1. Al confirmar venta con items producto: `registerSaleExit` por linea.
  2. Validar stock insuficiente antes de confirmar.
  3. Config sucursal: permitir venta sin stock (opcional).
- **Criterios de aceptacion:**
  - Feature test venta reduce stock; venta sin stock falla o advierte segun config.
- **Dependencias:** [00-operaciones-plan-mejora.md](./00-operaciones-plan-mejora.md), Paso 2.2.

#### Paso 2.4 — UI stock en ProductoLive

- **Objetivo:** Ver historial movimientos y stock actual.
- **Tareas:**
  1. Tab o seccion movimientos en ficha producto.
  2. Accion ajuste manual con permiso `producto.editar`.
- **Criterios de aceptacion:**
  - Usuario ve trazabilidad stock.

#### Paso 2.5 — Alertas stock bajo en reportes

- **Objetivo:** Alinear con `ReporteProductosServiciosLive`.
- **Dependencias:** [05-analitica-plan-mejora.md](./05-analitica-plan-mejora.md).
- **Criterios de aceptacion:**
  - Reporte usa `InventarioService::getLowStockProducts`.

---

### Fase 3 — Catalogo unificado y servicios

**Objetivo de fase:** Experiencia coherente productos/servicios vendibles.

#### Paso 3.1 — Sub-grupos sidebar Recursos

- **Objetivo:** Catalogo vs Alquileres.
- **Archivos:** `sidebar.blade.php`.
- **Estructura:**
  - Catalogo: categorias, productos, servicios
  - Alquileres: espacios, calendario, bandeja operativa, ingresos

#### Paso 3.2 — ServicioExterno trazabilidad

- **Objetivo:** Vincular ventas de servicio a `ServicioExterno`.
- **Tareas:**
  1. Verificar `VentaItem` referencia servicio.
  2. Reporte productos-servicios incluye servicios vendidos.
- **Criterios de aceptacion:**
  - Show servicio muestra conteo ventas periodo.

#### Paso 3.3 — RentableSpaceService consolidacion

- **Objetivo:** Tarifas y disponibilidad en un servicio.
- **Archivos:** `RentableSpaceService.php`.
- **Tareas:**
  1. Metodos tarifa por franja horaria.
  2. Usado por RentalService y reporte ingresos.
- **Criterios de aceptacion:**
  - Precio reserva calculado en servicio, no Livewire.

#### Paso 3.4 — Reporte ingresos alquiler

- **Objetivo:** `Rentals/Report.php` usa agregaciones de RentalService.
- **Criterios de aceptacion:**
  - Totales coinciden con pagos registrados.

---

## 4. Riesgos y mitigaciones

| Riesgo | Mitigacion |
| --- | --- |
| Reservas existentes inconsistentes | Script reconciliacion pre-migracion |
| Stock negativo | Validacion + config sucursal |
| Performance movimientos | Indice producto_id + created_at |

---

## 5. Criterios de cierre del modulo

- [ ] RentalService unico escritor
- [ ] Bandeja operativa alquileres activa
- [ ] Venta POS descuenta inventario
- [ ] InventarioService > cobertura CRUD basica
- [ ] Sidebar catalogo vs alquileres separado
- [ ] Servicios externos en reportes ventas

---

## 6. Dependencias con otros modulos

| Modulo | Dependencia |
| --- | --- |
| Operaciones | POS venta stock; reservas POS |
| Clientes / Bienestar | Atajos reserva sin escritura local |
| Analitica | Reportes productos y alquileres |

---

## 7. Avance de implementación (2026-06-24)

### Completado (Fase 1 parcial)
- `RentalService`: `createBooking`, `listForDate`, `listForCliente`, `cancelBooking`
- Wellness/ficha delegan reservas a `RentalService`
- `Rentals/Operations/Dashboard` + ruta `rentals.operations.index`
- Sidebar: Catálogo vs Alquileres

### Pendiente
- `InventarioService` ampliado + venta POS descuenta stock
- `RentableSpaceService` consolidación tarifas
