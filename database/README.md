# Base de datos y seeders

## Instalación ligera (por defecto)

Catálogo mínimo y usuario administrador:

```bash
php artisan migrate
php artisan db:seed
```

## Datos completos desde dump empaquetado (MySQL)

Los archivos `data/backup_part1.sql`, `backup_part2.sql` y `backup_part3.sql` contienen estructura y datos. Solo aplican con conexión **MySQL**.

Tras migraciones limpias:

```bash
php artisan migrate:fresh --seeder=BundledSqlBackupSeeder
```

O si la base ya está migrada:

```bash
php artisan db:seed --class=BundledSqlBackupSeeder
```

**No** ejecutes `DatabaseSeeder` (BaseCatalog) en el mismo proceso que `BundledSqlBackupSeeder`: el dump ya incluye catálogo y datos; mezclarlos puede provocar duplicados.

El seeder termina ejecutando `AdminUserSeeder` para asegurar el super administrador definido en ese seeder.
