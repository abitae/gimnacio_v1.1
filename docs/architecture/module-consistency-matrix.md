# Matriz de Consistencia de Modulos

## Objetivo
Centralizar una vista operativa del sistema para alinear navegacion, permisos, servicios, fuentes de verdad y UX entre modulos.

## Matriz Base
| Modulo | Rutas eje | Livewire eje | Servicio eje | Fuente de verdad | Riesgos actuales | Ajuste recomendado |
| --- | --- | --- | --- | --- | --- | --- |
| Clientes | `routes/web.php` (`clientes.*`) | `app/Livewire/Clientes/ClienteLive.php`, `app/Livewire/Clientes/ClientePerfilLive.php` | `app/Services/ClienteService.php` | `clientes` | El perfil cruza comercial, salud y CRM | Mantener `ClienteLive` como CRUD y `ClientePerfilLive` como detalle |
| Comercial | `routes/web.php` (`membresias.*`, `cliente-matriculas.*`, `clases.*`) | `app/Livewire/Membresias/MembresiaLive.php`, `app/Livewire/ClienteMatriculas/ClienteMatriculaLive.php` | `app/Services/ClienteMatriculaService.php`, `app/Services/ClientEnrollmentService.php` | `cliente_matriculas` | Convive con `cliente_membresias` legacy | Priorizar `cliente_matriculas` y dejar legacy en lectura |
| Operacion diaria | `routes/web.php` (`checking.*`, `cajas.*`, `pos.*`) | `app/Livewire/Checking/CheckingLive.php`, `app/Livewire/Cajas/CajaLive.php`, `app/Livewire/POS/POSLive.php` | `app/Services/CajaService.php`, `app/Services/VentaService.php`, `app/Services/AsistenciaService.php` | caja, ventas, asistencias | Cobros duplicados en menu y reportes | Separar operacion de analitica y usar accesos rapidos con permiso |
| Bienestar | `routes/web.php` (`gestion-nutricional.*`, `ejercicios.*`, `rutinas-base.*`, `ejercicios-rutinas.*`) | `app/Livewire/GestionNutricional/GestionNutricionalUnificadoLive.php`, `app/Livewire/Exercises/Index.php`, `app/Livewire/Routines/Templates/Index.php` | `app/Services/SeguimientoNutricionService.php`, `app/Services/EvaluacionMedidasNutricionService.php`, `app/Services/ClientRoutineService.php` | `health_records`, seguimientos, rutinas | `GestionNutricionalUnificadoLive` mezcla consultas directas y flujos cruzados | Reducir logica directa en Livewire y aislar operaciones cruzadas |
| CRM | `routes/web.php` (`crm.*`) | `app/Livewire/Crm/CrmPipelineLive.php`, `app/Livewire/Crm/LeadsListLive.php` | `app/Services/Crm/LeadService.php`, `app/Services/Crm/ConvertLeadToClientService.php` | `crm_leads`, deals, tasks | `StoreLeadRequest` autoriza con permiso incorrecto | Alinear requests, policy y middleware con `crm.create/update` |
| Recursos | `routes/web.php` (`categorias-productos.*`, `productos.*`, `servicios.*`, `rentals.*`) | `app/Livewire/Productos/ProductoLive.php`, `app/Livewire/Servicios/ServicioExternoLive.php`, `app/Livewire/Rentals/*` | `app/Services/ProductoService.php`, `app/Services/ServicioExternoService.php` | catalogos, rentals | Alquileres y catalogos quedan dispersos en la navegacion | Reagrupar en un bloque de recursos |
| Analitica | `routes/web.php` (`reportes.*`) | `app/Livewire/Reportes/ReporteIndexLive.php` y reportes derivados | `app/Services/ReporteModuloService.php`, `app/Services/ReporteService.php` | agregaciones multi-modulo | Parte de reportes reutiliza pantallas operativas y controladores sin permiso uniforme | Concentrar accesos en un unico grupo y aplicar autorizacion uniforme |
| Administracion | `routes/web.php` (`usuarios.*`, `roles.*`, `payment-methods.*`, `biotime.*`, `employees.*`) | `app/Livewire/Usuarios/UsuarioLive.php`, `app/Livewire/Roles/RolLive.php`, `app/Livewire/Settings/PaymentMethods/Index.php`, `app/Livewire/Employees/*` | servicios administrativos especificos | usuarios, roles, metodos de pago, empleados, integracion | BioTime y personal estan separados aunque son soporte del sistema | Consolidar navegacion y revisar permisos realmente consumidos |

## Observaciones Transversales
- La navegacion global vive en `resources/views/components/layouts/app/sidebar.blade.php`.
- Las migas de pan en `resources/views/components/breadcrumbs.blade.php` no cubren todas las rutas visibles del sistema.
- La mayoria de modulos sigue `Livewire -> Service -> Model`, pero `GestionNutricionalUnificadoLive` y algunos perfiles/reportes todavia consultan modelos de forma directa.
- `database/seeders/RoleSeeder.php` siembra un catalogo mas amplio que el uso real de permisos.
- `database/seeders/DatabaseSeeder.php` sirve para demo, no para cargas masivas ni casos limite.

## Fuente de Verdad Recomendada
- Clientes: `clientes`
- Comercial: `cliente_matriculas`
- Salud: `health_records` con sincronizacion legacy solo para compatibilidad
- Caja y ventas: `cajas`, `ventas`, `pagos`, `caja_movimientos`
- CRM: `crm_leads`, `deals`, `crm_tasks`, `crm_activities`
- Reportes: servicios agregadores, no componentes operativos reutilizados como destino principal de navegacion
