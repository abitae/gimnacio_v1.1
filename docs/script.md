# Scripts útiles

## Sincronizar roles y permisos (recomendado)

```bash
composer permissions:sync
```

Equivalente a:

```bash
php artisan permissions:sync
```

Crea/actualiza permisos desde `PermissionCatalog`, migra roles legacy y asigna permisos por rol.

## Auditar roles y permisos (sin modificar)

```bash
composer permissions:audit
```

Para auditar y sincronizar en un solo paso:

```bash
php artisan permissions:audit --roles --sync
```

## Seed manual (alternativa)

```bash
php artisan db:seed --class=RoleSeeder
```
