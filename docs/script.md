# Scripts útiles

## Sincronizar roles y permisos (producción)

```bash
php artisan permissions:sync
```

Crea roles y permisos **faltantes**. No modifica los permisos que ya tengan los roles existentes.

## Reset al catálogo (solo entornos nuevos o si se quiere forzar el catálogo)

```bash
php artisan permissions:sync --reset
```

Esto sí alinea cada rol al `PermissionCatalog` y puede cambiar la configuración actual.

## Auditar roles y permisos (sin modificar)

```bash
php artisan permissions:audit --roles
```

Para crear lo faltante en un solo paso (también modo seguro):

```bash
php artisan permissions:audit --roles --sync
```
