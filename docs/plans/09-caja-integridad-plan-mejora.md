# Plan de mejora: Caja (integridad transaccional y control de acceso)

> **Referencia:** [module-consistency-matrix.md](../architecture/module-consistency-matrix.md), [00-operaciones-plan-mejora.md](./00-operaciones-plan-mejora.md) (Paso 2.3, trazabilidad de caja por sucursal — tema relacionado pero distinto)
> **Prioridad:** Alta (integridad de datos financieros y control de acceso)
> **Alcance:** `app/Services/CajaService.php`, `app/Livewire/Cajas/CajaLive.php`, `app/Models/Core/Caja.php`, `app/Models/Core/CajaMovimiento.php` — apertura/cierre de caja, movimientos, permisos
> **Última actualización:** 2026-08-27

---

## 1. Contexto y diagnóstico

### Por qué este documento es distinto del Paso 2.3 de Operaciones

`00-operaciones-plan-mejora.md` (Paso 2.3) trata que los *movimientos* de caja no crucen sucursal — ya prácticamente resuelto vía `BelongsToSucursal`. Este documento cubre un problema distinto y más severo: **ausencia de control de concurrencia y brechas de autorización** en las operaciones que abren/cierran caja y mueven dinero. No es deuda de estilo — es integridad transaccional real.

### Alcance funcional

Apertura de caja (monto inicial), registro de movimientos (ingresos/salidas manuales, ventas, cuotas, alquileres), cierre con arqueo (conteo vs. saldo esperado). Componentes: `CajaLive.php` (592 LOC), `CajaService.php` (541 LOC), modelos `Caja` y `CajaMovimiento` en `app/Models/Core/`.

### Estado actual (verificado en código, no solo documentado)

| Elemento | Situación |
| --- | --- |
| Apertura de caja | Una caja abierta por usuario (no por sucursal/turno explícito); chequeo fuera de lock |
| Cierre de caja | Calcula `diferencia_cierre` (saldo esperado vs. contado) pero no exige tolerancia ni justificación |
| Concurrencia | **Sin `lockForUpdate()` en todo el módulo**; sin constraint único en la tabla `cajas` |
| Aislamiento multi-sucursal | `Caja` usa `BelongsToSucursal` (scope por sucursal activa); el chequeo de "caja abierta" es ciego a otras sucursales |
| Permisos | `caja.ver`, `caja.crear`, `caja.editar`, `caja.movimiento_manual` — **no existe `caja.cerrar`** |
| Motivo obligatorio en salida manual | Solo se exige en el componente Livewire, no en el servicio |
| Encoding | Cadenas con codificación rota en mensajes de excepción (`CajaService.php:63,122,471,486`) |
| Tests | Cubren apertura, movimientos, cierre con diferencia; **cero cobertura de concurrencia, cambio de sucursal, o venta sin caja** |

### Riesgo principal

Dos peticiones casi simultáneas (doble clic, dos pestañas) pueden crear dos cajas abiertas para el mismo usuario, o duplicar un cierre — con impacto directo en la contabilidad diaria del gimnasio. El bypass multi-sucursal permite abrir cajas paralelas sin que el sistema lo note. El cierre no está gateado por ningún permiso explícito, solo por ser el dueño de la caja.

### Lo que ya funciona bien (no tocar)

- `VentaService::procesarVenta()` ya exige caja abierta y bloquea la venta si no existe — comportamiento correcto a preservar.
- Movimientos ya validan monto `> 0` y que la caja esté en estado `abierta`.
- `registrarIngresoPorPago`/`registrarIngresosPorPago` ya validan que `pago.sucursal_id === caja.sucursal_id`.

---

## 2. Objetivos

1. Eliminar la posibilidad de dos cajas abiertas simultáneas para un mismo usuario (misma sucursal), tanto a nivel de aplicación como de base de datos.
2. Cerrar el bypass de aislamiento multi-sucursal en la apertura de caja, con rollout gradual (decisión de producto ya tomada: bloquear, empezando en modo solo-log).
3. Introducir un permiso `caja.cerrar` granular sin degradar la capacidad actual de ningún usuario/rol que hoy cierra su propia caja.
4. Unificar la regla de "motivo obligatorio" en la capa de servicio, para que no sea evitable llamando al servicio directamente.
5. Añadir un umbral de tolerancia configurable para la diferencia de cierre.
6. Corregir el encoding roto y extraer la lógica de normalización de movimientos, sin cambiar el contrato de salida.
7. Cerrar el vacío de cobertura de tests de concurrencia, multi-sucursal y venta sin caja.

Restricción dura: **ningún flujo hoy funcional para un usuario que abre/cierra su propia caja de forma normal debe dejar de funcionar.**

---

## 3. Plan por fases (ordenado por severidad real, no por LOC)

### Fase 1 — Integridad transaccional en apertura y cierre

**Objetivo de fase:** que sea imposible, incluso con doble clic o dos pestañas, terminar con dos cajas abiertas para el mismo usuario en la misma sucursal, o con un cierre duplicado.

#### Paso 1.1 — Bloqueo pesimista dentro de transacción envolvente

- **Archivos:** `app/Services/CajaService.php` (`abrirCaja`, `cerrarCaja`).
- **Tareas:**
  1. Envolver **todo** el método (chequeo + escritura) en una única `DB::transaction()`, no solo la escritura final.
  2. Dentro de la transacción, bloquear con `lockForUpdate()` las filas candidatas: en `abrirCaja()`, sobre `Caja::abiertas()->porUsuario($id)`; en `cerrarCaja()`, `Caja::lockForUpdate()->findOrFail($cajaId)` en vez de `findOrFail()` simple.
  3. Mantener el mensaje de excepción de negocio actual ("Ya tienes una caja abierta...") sin cambiar el copy visible al usuario.
- **Criterios de aceptación:**
  - Dos peticiones concurrentes resultan en una sola caja abierta y una excepción de negocio clara en la segunda.
  - Los tests existentes (`CajaServiceTest.php`, `CajaLiveTest.php`) siguen pasando sin modificar sus aserciones.
- **Riesgo:** `lockForUpdate()` fuera de transacción lanza error en MySQL — verificar que todos los callers pasen por `DB::transaction()`.

#### Paso 1.2 — Constraint único a nivel de base de datos (defensa en profundidad)

- **Archivo nuevo:** migración `database/migrations/XXXX_add_unique_open_caja_per_user_constraint.php`.
- **Tareas:**
  1. Columna generada almacenada `apertura_unica_usuario_id` (`GENERATED ALWAYS AS (CASE WHEN estado = 'abierta' THEN usuario_id END) STORED`) + índice único — MySQL trata múltiples `NULL` como valores distintos, así que solo restringe cuando `estado = 'abierta'`.
  2. **Obligatorio antes de migrar:** script/comando de auditoría que liste usuarios con más de una caja `abierta` en producción hoy. Si existen, resolver manualmente (decisión de negocio: cuál cerrar/fusionar) antes de correr la migración — debe fallar de forma explícita y segura, no corromper datos.
  3. `down()`: eliminar índice y columna (rollback limpio, sin pérdida de datos porque es una columna derivada).
  4. Capturar la excepción de violación de unicidad (`QueryException` código `23000`/`1062`) en `abrirCaja()` y traducirla al mismo mensaje de negocio — el usuario nunca debe ver un error SQL crudo.
- **Criterios de aceptación:**
  - Migración con `down()` probado.
  - Insertar una segunda caja abierta bypaseando el servicio (vía `DB::table('cajas')->insert()` directo) también falla — prueba de que la defensa es real a nivel de BD.
- **Dependencias:** Paso 1.1.
- **Riesgo:** duplicados no detectados en producción hacen fallar la creación del índice — de ahí la auditoría previa obligatoria. Mitigación: ejecutar primero en staging con dump reciente de producción.

#### Paso 1.3 — Mismo tratamiento para cierre concurrente

- **Archivos:** `CajaService::cerrarCaja()`.
- **Tareas:** `lockForUpdate()` sobre la caja seleccionada dentro de la transacción antes de verificar `estado === 'cerrada'`.
- **Criterios de aceptación:** dos cierres concurrentes sobre la misma caja producen un único cierre efectivo y una excepción de negocio clara en el segundo intento.

#### Paso 1.4 — Tests de concurrencia

- **Archivos:** `tests/Feature/Services/CajaServiceTest.php` (casos nuevos).
- **Tareas:** simular doble apertura y doble cierre, y un test que inserte directamente en BD para validar el constraint.
- **Criterios de aceptación:** los nuevos tests fallan de forma reproducible contra el código actual (antes del fix) y pasan después.

---

### Fase 2 — Aislamiento multi-sucursal en la apertura de caja

**Objetivo de fase:** que "¿tienes caja abierta?" no ignore otras sucursales de la misma empresa. Decisión de producto ya tomada: **bloquear, con rollout gradual** (primero solo-log, luego bloqueo activo).

#### Paso 2.1 — Config y detección en modo solo-log

- **Archivo nuevo:** `config/caja.php` (mismo patrón que `config/biotime.php`, `config/pos.php`).
- **Archivos:** `CajaService::obtenerCajaAbiertaPorUsuario()`, `abrirCaja()`.
- **Tareas:**
  1. Nuevo método `obtenerCajaAbiertaPorUsuarioEnEmpresa(int $usuarioId, int $empresaId)` que use `Caja::withoutGlobalScope('active_sucursal')` filtrando explícitamente por `empresa_id` vía `whereHas('sucursal', fn ($q) => $q->where('empresa_id', $empresaId))` — nunca sin acotar a la empresa, para no filtrar toda la base de datos.
  2. `config/caja.php` → `'bloquear_apertura_cross_sucursal' => env('CAJA_BLOQUEAR_CROSS_SUCURSAL', false)` — arranca en `false`.
  3. Mientras el flag está en `false`: solo `Log::warning()` cuando se detecte el caso — permite medir cuántas veces ocurre hoy en producción antes de bloquear.
- **Criterios de aceptación:** con flag `false`, comportamiento actual sin cambios, solo aparece el log.

#### Paso 2.2 — Activar el bloqueo

- **Archivos:** los mismos del Paso 2.1.
- **Tareas:** cuando el flag está en `true`, `abrirCaja()` lanza la misma excepción de negocio indicando la sucursal donde ya está abierta, en vez de solo loguear.
- **Criterios de aceptación:** cambiar de sucursal activa y abrir caja bloquea si hay una caja abierta en otra sucursal de la misma empresa.
- **Dependencias:** Paso 2.1 con un período de observación en producción antes de activar (recomendado: al menos 1-2 semanas de logs revisados).
- **Riesgo:** si hay usuarios que hoy dependen (aunque sea accidentalmente) de cajas paralelas en dos sucursales, activar el flag cambia su flujo — de ahí el período de observación previo.

#### Paso 2.3 — Tests

- **Archivos:** `CajaServiceTest.php` — cambio de sucursal activa a mitad de flujo, verificar detección/bloqueo según el flag en ambos estados.

---

### Fase 3 — Permiso granular de cierre `caja.cerrar`

**Objetivo de fase:** que cerrar una caja requiera un permiso explícito, sin quitarle la capacidad a nadie que hoy ya cierra su propia caja legítimamente.

#### Paso 3.1 — Alta del permiso + migración de datos (patrón ya usado en el repo)

- **Archivos:** `app/Support/PermissionCatalog.php` (agregar `caja.cerrar` a `extraPermissions()`, grupo "Cajas"; agregarlo al rol `caja` en `roleDefinitions()`).
- **Archivo nuevo:** migración calcada de `database/migrations/2026_08_18_193100_add_publicidad_app_permissions.php`:
  1. `Permission::findOrCreate('caja.cerrar', $guard)`.
  2. Otorgar a `administrador_sucursal` (`super_administrador` ya tiene bypass total vía `Gate::before` en `AppServiceProvider.php:41-46`, no necesita el permiso explícito).
  3. **Clave para no romper producción:** otorgar también el permiso a **cualquier rol existente que ya tenga `caja.editar` o `caja.movimiento_manual`** (consulta dinámica sobre roles reales, no solo el rol semilla `caja`) — así ningún rol custom creado manualmente pierde la capacidad de cerrar caja.
  4. `down()`: eliminar el permiso.
- **Criterios de aceptación:**
  - Tras correr la migración en un dump de producción/staging, ningún usuario que hoy cierra su propia caja pierde esa capacidad.
  - Verificar que `roleDefinitions()` incluya `caja.cerrar` para el rol `caja`, de forma que un futuro `sync` con `reset=true` no lo pierda.

#### Paso 3.2 — Aplicar el gate junto al chequeo de ownership existente

- **Archivos:** `CajaLive.php` (acción de abrir modal de cierre), `CajaService::cerrarCaja()`.
- **Tareas:** agregar el chequeo de permiso `caja.cerrar` **además** del chequeo de ownership (`usuario_id === Auth::id()`), no en su reemplazo. Mismo chequeo defensivo en el servicio (por si se invoca fuera de Livewire).
- **Criterios de aceptación:** todos los tests actuales de cierre siguen pasando (los roles de prueba ya tienen el permiso tras la migración de datos).
- **Dependencias:** Paso 3.1.

#### Paso 3.3 — Tests de permisos

- **Archivos:** `CajaLiveTest.php`, `CajaServiceTest.php`.
- **Tareas:** dueño sin permiso → bloqueado; con permiso pero no dueño → sigue bloqueado (comportamiento actual preservado); dueño con permiso → éxito.

---

### Fase 4 — Reglas de negocio consistentes

#### Paso 4.1 — Mover "motivo obligatorio" de salida manual al servicio

- **Archivos:** `CajaService::validateMovimientoManual()`, `registrarSalidaManual()`; `CajaLive.php` (líneas 285-291).
- **Tareas:**
  1. Agregar parámetro `string $tipo` a `validateMovimientoManual(array $data, string $tipo)`, con regla condicional: `observaciones` requerido solo si `$tipo === 'salida'`.
  2. Mantener el chequeo actual en `CajaLive` como primera línea de defensa en UI (mejor UX), pero ahora el servicio también lo exige.
  3. **Actualizar el test existente** en `CajaServiceTest.php` (caso que hoy llama `registrarSalidaManual()` sin `observaciones` y espera éxito) agregando `observaciones` al payload — ese test hoy codifica el bug y se rompería si no se actualiza en el mismo paso.
- **Criterios de aceptación:** llamar `registrarSalidaManual()` directamente sin `observaciones` lanza `ValidationException`; los tests de ingreso manual (que no requieren motivo) siguen pasando sin cambios.
- **Riesgo:** cualquier otro caller futuro del servicio que omita `observaciones` en salidas empezará a fallar — es el objetivo deseado del fix.

#### Paso 4.2 — Umbral de tolerancia en diferencia de cierre

- **Archivos:** `config/caja.php` (`'tolerancia_diferencia_cierre' => env('CAJA_TOLERANCIA_DIFERENCIA', 5.00)`), `CajaService::cerrarCaja()`/`validateCierre()`.
- **Tareas:** si `abs($saldoContado - $saldoEsperado) > tolerancia`, exigir `observaciones_cierre` no vacío. No bloquea el cierre, solo exige justificación por escrito.
- **Criterios de aceptación:** cierre dentro de tolerancia no requiere observaciones (comportamiento actual preservado); fuera de tolerancia sin observaciones falla con mensaje claro.

#### Paso 4.3 — Tests de ambos controles.

---

### Fase 5 — Calidad de código sin riesgo funcional

#### Paso 5.1 — Corregir encoding roto y eliminar código muerto

- **Archivos:** `CajaService.php` (líneas 63, 122, 471, 486), `app/Models/Core/Caja.php` (método `cerrar()`, línea ~122).
- **Tareas:** corregir acentos rotos ("está", "automáticos", "sesión"); guardar en UTF-8 sin BOM. **Eliminar** `Caja::cerrar()` en vez de arreglarle el encoding — es código muerto confirmado (sin callers en `app/`), duplica `CajaService::cerrarCaja()` sin sus validaciones (no calcula `saldo_contado_cierre` ni `diferencia_cierre`).
- **Criterios de aceptación:** ningún test rompe; búsqueda de caracteres corruptos (`Ã`) da cero resultados.

#### Paso 5.2 — Extraer `movimientosNormalizados()` a un transformer dedicado

- **Archivo nuevo:** `app/Support/CajaMovimientoNormalizer.php` (mismo patrón que `app/Support/CajaMatrizTotales.php`).
- **Archivos:** `app/Models/Core/Caja.php` (mover `movimientosNormalizados()`, `detalleItemsDesdeReferencia()`, `normalizarMetodoPagoCaja()`, `ventaIdDesdePagoAlquiler()`).
- **Tareas:** mover el cuerpo tal cual a la nueva clase; `Caja::movimientosNormalizados()` queda como método delgado que delega, para no romper los call sites existentes.
- **Criterios de aceptación:** mismo array de salida para un mismo dataset de prueba (test de snapshot antes/después); la vista Blade sigue funcionando sin cambios.

---

## 4. Riesgos y mitigaciones

| Riesgo | Mitigación |
| --- | --- |
| Duplicados ya existentes en producción impiden crear el índice único (Paso 1.2) | Auditoría previa obligatoria + reparación manual antes de migrar |
| Flag cross-sucursal (Fase 2) rompe un flujo real de cajas paralelas por sucursal | Rollout en modo "solo log" antes de bloquear; período de observación acordado |
| Migración de `caja.cerrar` deja fuera roles custom creados manualmente en producción | Migración que otorga el permiso a cualquier rol con `caja.editar`/`caja.movimiento_manual` existente, no solo al rol semilla |
| Fix de "motivo obligatorio" rompe test existente que codifica el bug | Actualizar el test en el mismo paso, documentado explícitamente |
| `lockForUpdate()` fuera de transacción lanza excepción en MySQL | Revisar que todos los callers de los métodos tocados pasen por `DB::transaction()` |

## 5. Criterios de cierre del módulo

- [ ] Doble apertura/cierre concurrente imposible (lock + constraint único), con tests reproducibles.
- [ ] Flag de aislamiento cross-sucursal implementado, validado en modo log, y activado.
- [ ] Permiso `caja.cerrar` existe, migrado sin quitarle capacidad a ningún usuario/rol actual.
- [ ] Motivo obligatorio de salida manual exigido en el servicio, no solo en Livewire.
- [ ] Umbral de tolerancia de diferencia de cierre configurable y activo.
- [ ] Sin cadenas con encoding roto en `CajaService.php`; `Caja::cerrar()` muerto eliminado.
- [ ] `movimientosNormalizados()` extraído sin cambiar el contrato de salida.
- [ ] Tests nuevos: concurrencia apertura/cierre, cambio de sucursal activa, permiso de cierre, motivo obligatorio, tolerancia de diferencia.

## 6. Dependencias con otros módulos

| Módulo | Dependencia |
| --- | --- |
| Operaciones | `00-operaciones-plan-mejora.md` Paso 2.3 (trazabilidad caja por sucursal) — tema relacionado, no duplicado |
| Administración | Patrón de migración de permisos ya usado en `06-administracion-plan-mejora.md` (AuditLog, INC-11) — considerar auditar apertura/cierre/movimientos de caja una vez `AuditLog` esté activo |
| Transversal | Multi-sucursal (`SucursalContext`, `docs/architecture/sucursal-scope-audit.md`) — la Fase 2 de este plan depende del mismo modelo de `empresa_id`/`sucursal_id` |
