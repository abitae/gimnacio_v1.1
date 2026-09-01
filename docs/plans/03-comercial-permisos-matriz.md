# Matriz de permisos CRM (INC-10)

> **Estado (verificado 2026-08-27): propuesta documentada, NO aplicada al código.** `crm_mensaje.ver` sigue separado de `crm.ver` en `routes/web.php` y `app/Support/PermissionCatalog.php`. Ver [`03-comercial-plan-mejora.md`](./03-comercial-plan-mejora.md) §7.

| Acción | Permiso |
| --- | --- |
| Ver CRM | `crm.ver` |
| Crear/editar leads | `crm.crear`, `crm.editar` |
| Convertir lead | `crm.convertir` (o `crm.editar` en flujo actual) |
| Mensajes WhatsApp | `crm_mensaje.ver`, `crm_mensaje.enviar` |
| Campañas | `crm.campana.ver`, `crm.campana.editar` (vía `crm.ver`) |
| Cupones | `cupon.ver`, `cupon.crear`, `cupon.editar` |

Auditar rutas `crm.*` y componentes `App\Livewire\Crm/*` para `authorize()` en mount/acciones.
