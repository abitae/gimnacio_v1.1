# Plan de mejora: Administracion

> **Referencia:** [module-consistency-matrix.md](../architecture/module-consistency-matrix.md)  
> **Prioridad:** Media (orden global #7)  
> **Inconsistencias vinculadas:** INC-05, INC-06  
> **Ultima actualizacion:** 2026-06-24

---

## 1. Contexto y diagnostico

### Alcance funcional
Empleados, asistencia personal, BioTime, metodos de pago, usuarios, roles. Relacionado: backups (ruta oculta), empresa/sucursales (Super admin).

### Estado actual

| Elemento | Situacion |
| --- | --- |
| `UsuarioLive` | Restriccion sucursal OK |
| BioTime | En Administracion; breadcrumbs en Operacion |
| Backups | Ruta activa; sin item sidebar |
| Fragmentacion | Seguridad, personal, integraciones, config financiera mezclados |
| `AuditLog` | Modelo existe; uso limitado |

### Riesgo principal
Permisos efectivos no visibles; backups y BioTime mal ubicados en UX.

### Fuente de verdad objetivo
`users`, `roles`, `payment_methods`, `employees`, `employee_attendances`, `biotime_*`, `audit_logs`

---

## 2. Objetivos

1. Reorganizar navegacion en subdominios claros.
2. Exponer backups en Super administracion con auditoria.
3. Reclasificar BioTime con decision documentada.
4. Pantalla permisos efectivos por usuario.
5. Auditar semilla permisos vs middleware.

---

## 3. Plan por fases

### Fase 1 — Navegacion y exposicion

**Objetivo de fase:** Resolver INC-05 e INC-06 rapidamente.

#### Paso 1.1 — ADR clasificacion BioTime

- **Objetivo:** Decision arquitectonica explicita.
- **Archivos nuevos:** `docs/architecture/adr-biotime-clasificacion.md`.
- **Opciones documentadas:**
  - A) Operaciones (uso diario recepcion)
  - B) Administracion config + widget Checking
- **Criterios de aceptacion:**
  - ADR aprobado; linked desde matriz consistencia.

#### Paso 1.2 — Aplicar decision BioTime en sidebar

- **Archivos:** `sidebar.blade.php`, `breadcrumbs.blade.php`.
- **Tareas segun ADR:**
  - Si A: mover item a grupo Operaciones.
  - Si B: mantener admin; agregar widget sync en Checking.
- **Criterios de aceptacion:**
  - Sidebar y breadcrumbs consistentes.

#### Paso 1.3 — Exponer backups en Super administracion

- **Objetivo:** Resolver INC-06.
- **Archivos:** `sidebar.blade.php`, ruta `administracion.backups.index`.
- **Tareas:**
  1. Item "Respaldos BD" en grupo Super administracion.
  2. Icono database o shield.
  3. Solo rol `super_administrador`.
- **Criterios de aceptacion:**
  - Super admin accede backups sin URL manual.

#### Paso 1.4 — Sub-grupos sidebar Administracion

- **Estructura sugerida:**
  - **Personal:** empleados, asistencia personal
  - **Seguridad:** usuarios, roles
  - **Configuracion:** metodos de pago
  - **Integraciones:** BioTime (si permanece aqui)
- **Criterios de aceptacion:**
  - Separadores visuales aplicados.

---

### Fase 2 — Permisos efectivos y seguridad

**Objetivo de fase:** UX refleja modelo real de acceso.

#### Paso 2.1 — Crear EffectivePermissionsService

- **Objetivo:** Calcular permisos efectivos usuario + rol + sucursal.
- **Archivos nuevos:** `app/Services/Admin/EffectivePermissionsService.php`.
- **Metodos:**
  ```text
  forUser(User $user, ?Sucursal $sucursal): EffectivePermissionSet
  explains(string $permission, User $user): ?string
  ```
- **Criterios de aceptacion:**
  - Unit tests rol simple, multi-rol, super admin.

#### Paso 2.2 — Pantalla permisos efectivos

- **Objetivo:** Vista lectura en UsuarioLive o pagina dedicada.
- **Archivos:** `UsuarioLive.php` o `Users/EffectivePermissions.php`.
- **Contenido:**
  - Roles asignados
  - Sucursales accesibles
  - Lista permisos agrupados por modulo
  - Indicador sucursal activa vs permisos globales
- **Criterios de aceptacion:**
  - Admin puede auditar que puede hacer un usuario sin probar manualmente.

#### Paso 2.3 — Auditoria permisos semilla vs rutas

- **Objetivo:** Detectar permisos huerfanos o rutas sin permiso.
- **Tareas:**
  1. Script artisan `permissions:audit` (opcional).
  2. Comparar `PermissionCatalog`, seeders, middleware rutas.
  3. Corregir gaps: checking, asistencia empleado report, etc.
- **Criterios de aceptacion:**
  - Informe audit sin discrepancias criticas.

#### Paso 2.4 — Fortalecer UsuarioLive restricciones

- **Tareas:**
  1. Confirmar super admin no editable desde modulo comun.
  2. Validar asignacion sucursales coherente con rol.
  3. Impedir auto-eliminacion permisos criticos.
- **Criterios de aceptacion:**
  - Tests regresion UsuarioLive.

---

### Fase 3 — Auditoria y soporte tecnico

**Objetivo de fase:** Trazabilidad cambios administrativos.

#### Paso 3.1 — AuditLog en usuarios y roles

- **Archivos:** `AuditLog`, `UsuarioLive`, `RolLive`, observer o eventos.
- **Eventos:** usuario creado/editado/desactivado, rol permisos modificados.
- **Campos:** actor, entidad, before/after JSON, ip opcional.
- **Criterios de aceptacion:**
  - Cambio rol genera entrada audit.

#### Paso 3.2 — AuditLog metodos de pago

- **Archivos:** `PaymentMethods/Index.php`.
- **Criterios de aceptacion:**
  - Alta/baja metodo pago auditada.

#### Paso 3.3 — Backups con auditoria y confirmacion

- **Archivos:** `DatabaseBackupLive.php`, `DatabaseBackupService.php`.
- **Tareas:**
  1. Log backup: quien, cuando, tamano, ruta.
  2. Restauracion: doble confirmacion + solo super admin.
  3. Listado backups descargables con metadata.
- **Criterios de aceptacion:**
  - Restauracion imposible sin confirmacion explicita.

#### Paso 3.4 — BioTime dashboard operacional

- **Archivos:** `BioTimeDashboard.php`, `BioTimeSyncService.php`.
- **Tareas:**
  1. Estado conexion, ultima sync, errores recientes.
  2. Enlace a `integration_error_logs`.
  3. Accion sync manual con throttle.
- **Criterios de aceptacion:**
  - Admin ve salud integracion sin logs servidor.

#### Paso 3.5 — Empleados y asistencia cohesion

- **Tareas:**
  1. Enlace empleado ↔ usuario sistema (opcional).
  2. Reporte asistencia empleado usa filtros sucursal.
  3. Permiso `asistencia_empleado.crear` alineado en sidebar.
- **Criterios de aceptacion:**
  - Flujo empleado → registrar asistencia documentado.

---

## 4. Riesgos y mitigaciones

| Riesgo | Mitigacion |
| --- | --- |
| Mover BioTime confunde usuarios | Comunicacion + ADR |
| Backup restore destructivo | Confirmacion multi-paso + solo super admin |
| AuditLog volumen | Retencion configurable |

---

## 5. Criterios de cierre del modulo

- [ ] BioTime clasificado y navegacion alineada
- [ ] Backups visibles en Super administracion
- [ ] EffectivePermissionsService + UI lectura
- [ ] Audit permisos semilla vs rutas limpio
- [ ] AuditLog en usuarios, roles, pagos, backups
- [ ] Sidebar administracion reorganizado

---

## 6. Dependencias con otros modulos

| Modulo | Dependencia |
| --- | --- |
| Operaciones | BioTime ↔ checking |
| Plataforma | Super admin comparte grupo backups/empresa |
| Todos | Permisos middleware alineados |

---

## 7. Avance de implementación (2026-06-24)

### Completado (Fase 1 parcial)
- ADR BioTime (`docs/architecture/adr-biotime-clasificacion.md`)
- Sidebar backups en Super administración (INC-06)
- Subgrupos: Personal / Configuración / Seguridad
- `EffectivePermissionsService`

### Pendiente
- UI permisos efectivos en UsuarioLive
- `permissions:audit` artisan
- AuditLog ampliado
