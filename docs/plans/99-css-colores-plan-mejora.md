# Plan de mejora: Carga de CSS y sistema de colores

> **Referencia:** [99-transversal-plan-mejora.md](./99-transversal-plan-mejora.md), [03-comercial-ui-ux-plan-mejora.md](./03-comercial-ui-ux-plan-mejora.md) (ya usa el patrón de badge estático que este plan promueve a nivel de toda la app)
> **Prioridad:** Media-alta (deuda técnica transversal, no bloquea funcionalidad)
> **Alcance:** Pipeline de build CSS, sistema de personalización de tema en tiempo de ejecución, y uso de colores de estado fuera del módulo CRM
> **Última actualización:** 2026-08-26

---

## 1. Contexto y diagnóstico

### Stack
Laravel 12 + Vite 7 + `@tailwindcss/vite` (Tailwind v4, **CSS-first**: no existe `tailwind.config.js` ni `postcss.config.js` en el repo) + Flux UI.

### Build pipeline

- `resources/css/app.css`: `@import 'tailwindcss'` → `@import '../../vendor/livewire/flux/dist/flux.css'` → `@import './theme-personalizacion.css'`, con directivas `@source` (apuntan el escaneo de contenido de Tailwind a `../views`, `AppServiceProvider.php`, vistas de paginación de Laravel y stubs de Flux/Flux Pro) y `@custom-variant dark (&:where(.dark, .dark *))`.
- `resources/css/theme-personalizacion.css`: un bloque `@theme` (paleta `--color-zinc-*`, `--color-accent*`, 6 variables `--theme-state-*`), luego `@layer theme { .dark { ... } }`, y luego — **fuera de cualquier `@layer`** — una serie de bloques `html.accent-{blue,green,red,violet,indigo,amber,neutral}` / `html.dark.accent-*` (líneas 72-141). Esta es la única inconsistencia estructural real del archivo: un bloque de overrides vive dentro de `@layer theme` y el otro no, lo que puede dar sorpresas de orden de cascada si Tailwind cambia su orden interno de layers.
- `autoprefixer` está declarado en `package.json` pero **no se usa**: no hay `postcss.config.js` que lo invoque: Tailwind v4 vía `@tailwindcss/vite` hace el vendor-prefixing internamente (Lightning CSS). Es una dependencia muerta.
- `resources/views/partials/head.blade.php` es el único punto real de `@vite(['resources/css/app.css', 'resources/js/app.js'])` + `@fluxAppearance` (línea 18-19); se incluye desde `sidebar.blade.php` (el layout activo). `welcome.blade.php` y las páginas de error tienen su propia llamada a `@vite`, redundante pero de bajo impacto.
- `resources/views/components/layouts/app/header.blade.php` es **código muerto confirmado** (cero referencias en todo el árbol de vistas, verificado independientemente dos veces): un documento HTML completo alternativo, sobrante del starter-kit de Livewire, con `<html class="dark">` hardcodeado que contradice el resto de la app.

### Personalización en tiempo de ejecución

El sistema real vive en tres capas que no siempre están sincronizadas:

1. **Server-side (fuente de verdad real):** la tabla `users` tiene columnas `appearance`, `appearance_sidebar`, `appearance_header`, `accent`, `sidebar_bg`, `header_bg`, `body_bg`, `font_size` (varias migraciones, valores por defecto fijados en `2026_04_13_120000_update_default_personalization_theme.php`). `sidebar.blade.php:2` renderiza las clases/atributos `data-*` iniciales desde estas columnas en el primer render (`$bodyAppearanceClass`, `$accentClass`, etc.), inyectadas por un view-composer.
2. **Anti-FOUC + guardas server-side que fuerzan "light":**
   - `resources/views/partials/head.blade.php:3` (`<meta name="color-scheme" content="light">`) y `:7-10` (script inline que hace `classList.remove('dark'); classList.add('light')` antes de pintar).
   - `app/Models/User.php:209-216` — `static::saving()` fuerza `appearance`, `appearance_sidebar` y `appearance_header` a `'light'` en **cada guardado**, sin importar lo que se le haya asignado.
   - `sidebar.blade.php:512-513` — `resolveMode(val) { return 'light'; }`: la función de resolución de modo del JS del sidebar ignora su argumento y siempre devuelve `'light'`.
3. **UI que sigue ofreciendo la opción muerta:** `resources/views/livewire/theme-switcher.blade.php` sigue presentando "oscuro"/"sistema" como opciones seleccionables. El usuario puede "elegir" oscuro, se guarda momentáneamente, y las tres guardas de arriba lo revierten sin aviso — una UI que miente.

**Cuatro componentes Livewire/Volt redundantes** editan las mismas columnas de `users` con listas de colores válidos que no coinciden entre sí: `resources/views/livewire/settings/appearance.blade.php`, `resources/views/livewire/theme-switcher.blade.php`, `resources/views/livewire/sidebar-header-colors.blade.php` (solo permite `default,blue,green,red`) y `resources/views/livewire/personalization-modal.blade.php` (permite `default,slate,blue,green,amber,red,violet,indigo`).

**Los mapas de clases de color están triplicados**, y cualquier cambio de paleta exige editar los tres en sincronía:
- El script inline de `sidebar.blade.php:469-650` (mapas JS `sidebarBgClasses`, `headerBgClasses`, `bodyBgClasses`, etc., usados para preview instantáneo vía el evento Livewire `appearance-updated`).
- `app/Providers/AppServiceProvider.php` `boot()` (líneas ~134-155): un array inline (no un método, pese a que el comentario en `theme-personalizacion.css:143` lo referencia como `AppServiceProvider::pageHeaderGradientClasses` — desalineación de nombre/documentación) compartido a todas las vistas vía `View::composer('*', ...)`.
- El view-composer scoped a `components.layouts.app.sidebar` (líneas ~167-225 del mismo archivo), que recalcula el mismo mapeo de forma independiente para las clases iniciales del layout.

Además, el JS del sidebar aplica clases mutando `className` por **regex string-replace** sobre `#app-sidebar`, `#app-header` y `<body>` (líneas 578-600) — fràgil ante cualquier cambio futuro a las clases base de esos elementos fuera de este script.

### Colores de estado fuera de CRM

- Los 6 tokens `--theme-state-{programada,confirmada,en-curso,completada,cancelada,no-asistio}` de `theme-personalizacion.css:51-56` están definidos pero **tienen cero consumidores** en todo el árbol de `resources/views` — tokens de diseño muertos.
- `resources/views/livewire/gestion-nutricional/calendario-citas-live.blade.php` reimplementa esos mismos 6 estados como un array PHP con hex crudos, y **ya divergió del token**: usa `#64748b` (slate-500) para `completada` en vez de `#71717a` (zinc-500), que es lo que define `--theme-state-completada`. Evidencia concreta de drift, no solo un riesgo teórico.
- Matrícula (`cliente-matricula-live.blade.php`), cliente, empleados (`employees/index.blade.php`) y pagos/cuotas reimplementan cada uno su propio mapeo condicional de color con Tailwind (`green` en un archivo, `emerald` en otro, para el mismo significado "éxito/activo"). No existe un componente de badge de estado compartido fuera de CRM — sí existe uno ya construido y verificado en producción: `resources/views/components/crm/status-badge.blade.php` (paleta Tailwind estática por tipo/estado, sin inline styles).
- Las plantillas PDF/impresión (`ventas/comprobante.blade.php`, `reportes/pdf-*.blade.php`, `components/tickets/thermal-pdf-styles.blade.php`, `clientes/contrato-membresia-pdf.blade.php`) hardcodean cada una su propia paleta hex vía `style=`, sin partial compartido. Es un problema real pero de menor prioridad y **queda fuera del alcance de este plan** (los templates de impresión tienen restricciones propias de DomPDF).

### Decisiones de alcance confirmadas
- **Dark mode: se elimina por completo**, no solo se "apaga". Incluye quitar la UI que lo ofrece, las tres guardas server-side, y — como barrido gradual, no atómico — las clases `dark:` esparcidas por las vistas de toda la app.
- **Unificación de colores de estado: infraestructura + piloto.** Se construye el componente compartido y se migra el caso con drift ya identificado (citas); el resto de módulos (matrícula, cliente, empleados, pagos) queda documentado como backlog de seguimiento, no ejecutado en este plan.

---

## 2. Objetivos

1. Eliminar el dark mode de forma coherente: sin UI que ofrezca una opción muerta, sin guardas contradictorias en tres capas distintas.
2. Una sola fuente de verdad en PHP para los mapas de clases de color de personalización (accent/sidebar_bg/header_bg/body_bg), consumida por ambos composers y serializada una vez a JS — sin copias manuales duplicadas.
3. Consolidar los 4 componentes Livewire de apariencia en una única superficie de configuración con una lista de colores válidos consistente.
4. Dar uso real a los tokens de estado (`--theme-state-*` o su sucesor) vía un componente de badge compartido fuera de CRM, empezando por el caso con drift ya confirmado.
5. Limpiar dependencias y archivos muertos (`autoprefixer`, `layouts/app/header.blade.php`).

---

## 3. Plan por fases

### Fase 0 — Limpieza de infraestructura de build

**Objetivo de fase:** Eliminar deuda de bajo riesgo antes de tocar el sistema de personalización.

- **Paso 0.1** — Quitar `autoprefixer` de `package.json` (dependencia no usada, confirmado que no hay `postcss.config.js` que la invoque).
- **Paso 0.2** — Eliminar `resources/views/components/layouts/app/header.blade.php` (código muerto confirmado, cero referencias). Verificar con un grep final de `layouts.app.header|layouts/app/header` antes de borrar, por seguridad.
- **Paso 0.3** — En `theme-personalizacion.css`, mover los bloques `html.accent-*`/`html.dark.accent-*` (hoy fuera de cualquier `@layer`) dentro de `@layer theme`, junto al bloque `.dark` existente, para consistencia estructural (este paso se coordina con la Fase 1, que elimina el bloque `.dark` — hacerlos en el mismo PR).
- **Criterios de aceptación:** `npm run build` sigue funcionando; `git grep` de `layouts.app.header` sin resultados; no hay reglas CSS de accent fuera de `@layer` en el archivo.

### Fase 1 — Eliminación completa de dark mode

**Objetivo de fase:** Que no quede ni maquinaria muerta ni UI engañosa relacionada a dark mode.

- **Paso 1.1** — Quitar las opciones "oscuro"/"sistema" de `resources/views/livewire/theme-switcher.blade.php`; si el componente queda sin propósito (solo "claro" disponible), evaluar eliminarlo por completo y que su función quede cubierta por el componente de apariencia consolidado (Fase 2).
- **Paso 1.2** — Simplificar `app/Models/User.php::booted()` (líneas 209-216): ya no necesita forzar `'light'` en cada guardado si `light` es el único valor válido aceptado por la capa de validación del componente Livewire consolidado (mover la garantía de "solo light" a la validación de entrada, no a un hook silencioso que sobrescribe cualquier intento).
- **Paso 1.3** — Simplificar el script anti-FOUC de `resources/views/partials/head.blade.php:7-10` (ya no compite con nada una vez que no hay forma de setear `dark` en ningún lado).
- **Paso 1.4** — En `sidebar.blade.php`: quitar `resolveMode()` y los toggles de clase `.dark`/`.light` del script inline (líneas ~512-670), dejando solo la lógica de accent/sidebar_bg/header_bg/body_bg/font_size.
- **Paso 1.5** — Quitar el bloque `@layer theme { .dark { ... } }` de `theme-personalizacion.css` (coordinar con Paso 0.3). Evaluar si `@custom-variant dark` en `app.css:12` debe conservarse (Flux puede tener estilos internos que lo referencian) — documentar la decisión final en el propio CSS con un comentario, no dejarlo ambiguo.
- **Paso 1.6 (barrido gradual, mayor esfuerzo del plan)** — Quitar las clases `dark:` de las vistas Blade de la app, módulo por módulo (Clientes, Bienestar, Operaciones, CRM, Recursos, Analítica, Administración...), no en un solo cambio atómico. Cada módulo es una unidad de trabajo independiente y verificable por separado. El módulo CRM concentra el mayor volumen reciente (~230 ocurrencias de `dark:` a la fecha de este plan) por el trabajo de UI/UX ya hecho.
- **Criterios de aceptación:** ningún componente Livewire permite guardar un valor de apariencia distinto a `light`; `git grep -c "dark:"` decreciente por módulo a medida que avanza el Paso 1.6; verificación visual de que la app se ve igual en claro (no hay regresión).

### Fase 2 — Consolidar el sistema de personalización de tema

**Objetivo de fase:** Una sola fuente de verdad para los mapas de color y una sola superficie de configuración.

- **Paso 2.1** — Fusionar `settings/appearance.blade.php`, `sidebar-header-colors.blade.php` y `personalization-modal.blade.php` en un único componente de configuración con una lista de colores válidos consistente (resolver la discrepancia `default,blue,green,red` vs `default,slate,blue,green,amber,red,violet,indigo`).
- **Paso 2.2** — Crear una única fuente de verdad en PHP para los mapas `sidebarBgClasses`/`headerBgClasses`/`bodyBgClasses` (un método con nombre real, no un array inline en `boot()` — resolver la desalineación con el comentario `AppServiceProvider::pageHeaderGradientClasses` en `theme-personalizacion.css:143`), consumida por: el composer global de page-header, el composer del sidebar, y serializada una vez a JS (p. ej. `<script>window.__themeMaps = @json(...)</script>` en el layout) para que el script del sidebar deje de mantener su propia copia manual.
- **Paso 2.3** — Reemplazar la mutación de `className` por regex (`sidebar.blade.php:578-600`) por toggles basados en atributos `data-*` ya presentes en el `<html>` (evita que cambios futuros en las clases base de `#app-sidebar`/`#app-header`/`<body>` se pierdan silenciosamente).
- **Criterios de aceptación:** cambiar un color de fondo en un solo lugar del código se refleja en composers y en el preview JS sin editar 3 archivos; 0 componentes Livewire duplicados para la misma función.

### Fase 3 — Tokens de estado y badge compartido (infraestructura + piloto)

**Objetivo de fase:** Que los tokens de color de estado tengan un consumidor real, sin duplicar el patrón CRM que ya funciona.

- **Paso 3.1** — Crear `resources/views/components/status-badge.blade.php` (fuera del namespace `crm`, reutilizable por toda la app), replicando el patrón ya validado en `resources/views/components/crm/status-badge.blade.php`: paleta Tailwind estática por tipo/estado, sin `style=` inline.
- **Paso 3.2** — Migrar `calendario-citas-live.blade.php` (el caso con drift ya confirmado: `completada` en slate en vez de zinc) para usar el nuevo componente en vez de su array de hex propio — corrige el drift como efecto directo de la migración.
- **Paso 3.3** — Eliminar las variables `--theme-state-*` de `theme-personalizacion.css` (quedan sin consumidores una vez migrado el piloto, ya que el nuevo componente usa clases Tailwind estáticas, no variables CSS) — evita dejar tokens muertos otra vez bajo un nombre distinto.
- **Paso 3.4 (backlog, no ejecutar en este plan)** — Documentar como lista de seguimiento la migración de matrícula (`cliente-matricula-live.blade.php`), cliente, empleados (`employees/index.blade.php`) y pagos/cuotas al componente compartido, unificando `green` vs `emerald` para el mismo significado semántico.
- **Criterios de aceptación:** citas usa el componente compartido y ya no tiene un array de colores propio; 0 variables `--theme-state-*` sin uso.

### Fase 4 — QA

- Reconstruir assets (`npm run build`), limpiar y recompilar vistas (`view:cache`).
- Verificación visual en navegador (login real, recorrido de varias páginas) de que: no queda ningún rastro de opción "oscuro" en ningún selector de apariencia; la personalización de accent/fondos de sidebar y header sigue funcionando igual que antes; el calendario de citas muestra los mismos colores de estado que antes (mismo significado, ahora sin drift).

---

## 4. Riesgos y mitigaciones

| Riesgo | Mitigación |
| --- | --- |
| El barrido de `dark:` (Paso 1.6) es grande y puede convertirse en un PR gigante difícil de revisar | Ejecutar módulo por módulo como unidades independientes, no como un solo cambio atómico |
| Simplificar `User::booted()` antes de fusionar los 4 componentes Livewire (Fase 2) podría dejar una ventana donde algún componente viejo intente guardar un valor no-light sin la guarda que lo corrija | Mover la validación de "solo light" a la capa de entrada del componente consolidado en el mismo cambio que se toca `User::booted()`, no en pasos separados |
| Quitar `@custom-variant dark` de raíz podría romper estilos internos de Flux si Flux los referencia | Solo eliminar si se confirma que Flux no depende de la variante; documentar la decisión explícitamente en el CSS |
| Migrar citas al nuevo `x-status-badge` podría exponer más drift en otros consumidores del mismo array de colores (si existen) | Grep exhaustivo de todo uso del array de colores de citas antes de tocar el archivo |

---

## 5. Criterios de cierre del plan

- [ ] `autoprefixer` fuera de `package.json`; `layouts/app/header.blade.php` eliminado
- [ ] Bloques `html.accent-*` dentro de `@layer theme`
- [ ] Ningún componente permite guardar apariencia distinta a `light`; `theme-switcher` sin opciones muertas
- [ ] `User::booted()` simplificado; anti-FOUC simplificado; `resolveMode()`/toggles `.dark` fuera del JS del sidebar
- [ ] Barrido de `dark:` completado en, al menos, los módulos de mayor tráfico (documentar avance por módulo)
- [ ] Mapas de color de personalización en una sola fuente de verdad PHP, consumida por ambos composers y por el JS de preview
- [ ] 4 componentes de apariencia reducidos a 1
- [ ] `x-status-badge` compartido creado; citas migrado; `--theme-state-*` sin uso eliminado
- [ ] Backlog de matrícula/cliente/empleados/pagos documentado para una fase futura

---

## 6. Dependencias con otros documentos

| Documento | Relación |
| --- | --- |
| [03-comercial-ui-ux-plan-mejora.md](./03-comercial-ui-ux-plan-mejora.md) | Origen del patrón `x-status-badge` que este plan promueve a nivel de toda la app; el barrido de `dark:` en CRM (Paso 1.6) parte del volumen ya generado por ese plan |
| [99-transversal-plan-mejora.md](./99-transversal-plan-mejora.md) | Contexto arquitectónico general; este plan cubre un dominio (CSS/colores) que no estaba tratado allí |
