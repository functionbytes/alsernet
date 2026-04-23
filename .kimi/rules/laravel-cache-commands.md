# Rule: laravel-cache-commands

> **Applies to:** "**/*.php,modules_statuses.json,composer.json,bootstrap/providers.php,routes/**/*.php,config/**/*.php"


# Laravel Cache & Autoload Commands

When to run each command during development.

## composer dump-autoload

**Cuando**: Despues de crear/mover/renombrar clases PHP que no se auto-cargan

**Ejecutar despues de**:
- Crear nuevo modulo (nueva entrada PSR-4 en composer.json)
- Agregar nuevo namespace a `autoload.psr-4` en composer.json del proyecto
- Modificar autoload-dev (tests)
- "Class not found" errors despues de crear archivos

**NO necesario para**: Editar archivos PHP existentes (Laravel los detecta automaticamente).

## php artisan cache:clear

**Cuando**: Borrar el cache de la aplicacion (Cache::get/put data)

**Ejecutar despues de**:
- Modificar logica que usa `Cache::remember()`
- Cambiar TTL de caches
- Invalidar datos cacheados en pruebas locales
- Cache corrupto o con datos obsoletos

## php artisan config:cache

**Cuando**: PRODUCCION ONLY - Cachear config para performance

**Ejecutar solo en deployment de produccion**, NO en desarrollo.

## php artisan config:clear

**Cuando**: Limpiar cache de configuracion

**Ejecutar despues de**:
- Editar archivos en `config/`
- Editar `config/modules.php`
- Editar `config/*.php` de modulos
- Cambiar variables en `.env` (si estaban cacheadas)
- Cambios no se reflejan en `config()`

## php artisan route:clear

**Cuando**: Limpiar cache de rutas

**Ejecutar despues de**:
- Editar cualquier archivo `routes/*.php`
- Editar `modules/*/routes/*.php`
- Cambiar middleware asignado a rutas
- Renombrar/mover controllers referenciados en rutas
- Rutas no funcionan o dan 404 inesperados

## php artisan view:clear

**Cuando**: Limpiar vistas Blade compiladas

**Ejecutar despues de**:
- Cambios a Blade que no se reflejan
- Editar templates de mail
- Cambiar layouts base
- Views con cache corrupto (raro)

## php artisan optimize:clear

**Cuando**: Limpiar TODOS los caches (config + route + view + compiled)

**Ejecutar como "nuclear option"** cuando:
- Algo no funciona y no estas seguro que cache limpiar
- Despues de pull con muchos cambios
- Al cambiar de branch con cambios grandes
- Primer paso de troubleshooting "no se que pasa"

**Incluye**: `cache:clear`, `config:clear`, `route:clear`, `view:clear`, `event:clear`, `compiled:clear`

## Matriz de decision

| Cambiaste... | Comando a ejecutar |
|-------------|-------------------|
| `config/*.php` | `php artisan config:clear` |
| `routes/*.php` o `modules/*/routes/*.php` | `php artisan route:clear` |
| `*.blade.php` | `php artisan view:clear` (solo si no se refleja) |
| `composer.json` autoload | `composer dump-autoload` |
| `modules_statuses.json` | `composer dump-autoload` |
| `bootstrap/providers.php` | `composer dump-autoload` |
| Datos cacheados via `Cache::` | `php artisan cache:clear` |
| Cualquier cosa y no funciona | `php artisan optimize:clear` |

## Despues de crear nuevo modulo

Secuencia obligatoria:
```bash
composer dump-autoload                    # Nuevo namespace PSR-4
php artisan optimize:clear                # Limpia todo
php artisan module:list                   # Verifica que aparece
php artisan route:list --name={alias}     # Verifica rutas
```

## Despues de crear migrations

```bash
php artisan module:migrate {ModuleName}   # Ejecuta migrations del modulo
php artisan module:migrate:status {ModuleName}  # Verifica estado
```

## Despues de crear permissions seeder

```bash
php artisan module:seed {ModuleName} --class={ModuleName}PermissionsSeeder
php artisan cache:clear                   # Spatie cache de permisos
```

## NUNCA usar en desarrollo

- `php artisan migrate:fresh` (BLOQUEADO - destroys all data)
- `php artisan config:cache` (cachea config, dificulta debug)
- `php artisan route:cache` (cachea rutas, dificulta debug)
- `php artisan view:cache` (cachea views, dificulta debug)
