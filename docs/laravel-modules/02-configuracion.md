# Configuracion - config/modules.php

## Publicar configuracion
```bash
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"
```

## Campos principales

### namespace (linea 16)
```php
'namespace' => 'modules',
```
Namespace base para todos los modulos. En este proyecto es `modules` (minusculas) pero el namespace PHP es `Modules\` (por composer.json autoload).

### stubs (lineas 26-77)
```php
'stubs' => [
    'enabled' => false,  // DESHABILITADO en este proyecto
    'path' => base_path('vendor/nwidart/laravel-modules/src/Commands/stubs'),
    'files' => [
        'routes/web' => 'routes/web.php',
        'routes/api' => 'routes/api.php',
        'views/index' => 'resources/views/v5.blade.php',
        'scaffold/config' => 'config/config.php',
        'composer' => 'composer.json',
        'vite' => 'vite.config.js',
        'package' => 'package.json',
    ],
    'replacements' => [
        // Tokens: LOWER_NAME, STUDLY_NAME, KEBAB_NAME, MODULE_NAMESPACE, etc.
    ],
    'gitkeep' => true,
],
```
**Nota**: Hay 67 custom stubs en `/stubs/nwidart-stubs/` pero estan deshabilitados.

### paths (lineas 78-185)
```php
'paths' => [
    'modules' => base_path('modules'),     // Donde viven los modulos
    'assets' => public_path('modules'),     // Assets publicos
    'migration' => base_path('database/migrations'),
    'app_folder' => 'app/',                 // Subcarpeta de app
    'generator' => [
        // 35+ tipos de generators con path y generate boolean
        'controller' => ['path' => 'app/Http/Controllers', 'generate' => true],
        'model' => ['path' => 'app/Models', 'generate' => false],
        'provider' => ['path' => 'app/Providers', 'generate' => true],
        'migration' => ['path' => 'database/migrations', 'generate' => true],
        'seeder' => ['path' => 'database/seeders', 'generate' => true],
        'factory' => ['path' => 'database/factories', 'generate' => true],
        'views' => ['path' => 'resources/views', 'generate' => true],
        'routes' => ['path' => 'routes', 'generate' => true],
        // ... etc
    ],
],
```

### auto-discover (lineas 196-217)
```php
'auto-discover' => [
    'migrations' => true,    // Migraciones SI se auto-descubren
    'translations' => false, // Traducciones NO se auto-descubren
],
```

### scan (lineas 242-247)
```php
'scan' => [
    'enabled' => false,  // Escaneo de vendor deshabilitado
    'paths' => ['vendor/*/*'],
],
```

### activators (lineas 290-298)
```php
'activators' => [
    'file' => [
        'class' => FileActivator::class,
        'statuses-file' => base_path('modules_statuses.json'),
    ],
],
'activator' => 'file',
```

### cache (recomendado para proyectos grandes)
```php
'cache' => [
    'enabled' => false,
    'key' => 'laravel-modules',
    'lifetime' => 60,
],
```

## modules_statuses.json

Archivo que controla que modulos estan activos:
```json
{
    "Blog": true,
    "Analytics": true,
    "Campaign": false
}
```

Comandos para gestionar:
```bash
php artisan module:enable Blog
php artisan module:disable Blog
```
