# Registro de un Modulo Nuevo

## 3 puntos de registro obligatorios

Para que un modulo funcione en este proyecto, hay que registrarlo en 3 lugares:

### 1. modules_statuses.json

```json
{
    "ExistingModule": true,
    "NewModule": true
}
```

Tambien se puede usar artisan:
```bash
php artisan module:enable NewModule
```

### 2. bootstrap/providers.php

Agregar al array `$allProviders` en orden alfabetico:

```php
$allProviders = [
    // ... existing ...
    'Modules\\NewModule\\Providers\\NewModuleServiceProvider' => 'NewModule',
    // ... existing ...
];
```

- Si el valor es un **string** (nombre del modulo): se filtra por modules_statuses.json
- Si el valor es **true**: siempre carga (solo para modulos criticos)

### 3. Root composer.json (autoload PSR-4)

```json
{
    "autoload": {
        "psr-4": {
            "Modules\\NewModule\\": "modules/NewModule/app/",
            "Modules\\NewModule\\Database\\Factories\\": "modules/NewModule/database/factories/",
            "Modules\\NewModule\\Database\\Seeders\\": "modules/NewModule/database/seeders/"
        }
    }
}
```

Luego ejecutar:
```bash
composer dump-autoload
```

## Flujo de carga

```
1. Laravel boot
2. bootstrap/providers.php lee modules_statuses.json
3. Filtra providers: solo modulos habilitados + criticos
4. Llama register() en cada provider
5. Llama boot() en cada provider
6. Cada boot() verifica Module::find('Name')?->isDisabled()
7. Si habilitado: registra config, views, routes, migrations, menus
8. EnsureModuleIsActive middleware verifica en cada request web
```

## Middleware de proteccion

### EnsureModuleIsActive (global en web)
- Se ejecuta en TODA request web
- Extrae nombre del modulo de la ruta
- Retorna 404 si el modulo esta deshabilitado
- Modulos criticos (Core, Auth, Role, Theme, Modules) siempre pasan

### module:ModuleName (por ruta)
```php
Route::middleware(['web', 'module:Blog'])->group(...);
```

## Verificacion

```bash
# Verificar que el modulo aparece
php artisan module:list

# Verificar rutas
php artisan route:list --name=newmodule

# Verificar migraciones
php artisan module:migrate:status NewModule
```
