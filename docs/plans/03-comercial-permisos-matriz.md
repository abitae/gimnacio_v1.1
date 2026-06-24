# Matriz de permisos CRM (INC-10)

| Acción | Permiso |
| --- | --- |
| Ver CRM | `crm.ver` |
| Crear/editar leads | `crm.crear`, `crm.editar` |
| Convertir lead | `crm.convertir` (o `crm.editar` en flujo actual) |
| Mensajes WhatsApp | `crm_mensaje.ver`, `crm_mensaje.enviar` |
| Campañas | `crm.campana.ver`, `crm.campana.editar` (vía `crm.ver`) |
| Cupones | `cupon.ver`, `cupon.crear`, `cupon.editar` |

Auditar rutas `crm.*` y componentes `App\Livewire\Crm/*` para `authorize()` en mount/acciones.
