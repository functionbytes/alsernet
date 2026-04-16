# Recursos del Modulo

## Vistas

### Registrar
```php
$this->loadViewsFrom(module_path('Blog', 'resources/views'), 'blog');
```

### Usar en controller
```php
return view('blog::index');
return view('blog::posts.show', compact('post'));
return view('blog::settings.categories.index');
```

### Publicar
```php
$this->publishes([
    module_path('Blog', 'resources/views') => resource_path('views/modules/blog'),
], 'views');
```

## Configuracion

### Registrar
```php
$this->mergeConfigFrom(module_path('Blog', 'config/config.php'), 'blog');
```

### Usar
```php
config('blog.name');
config('blog.settings.option', 'default');
```

### Publicar
```php
$this->publishes([
    module_path('Blog', 'config/config.php') => config_path('blog.php'),
], 'config');
```

## Traducciones

### Registrar
```php
$this->loadTranslationsFrom(module_path('Blog', 'resources/lang'), 'blog');
```

### Usar
```php
trans('blog::messages.welcome');
@lang('blog::messages.welcome')
```

## Migraciones

### Auto-descubrimiento (configurado en este proyecto)
```php
// config/modules.php
'auto-discover' => ['migrations' => true]
```

### Manual en ServiceProvider
```php
$this->loadMigrationsFrom(module_path('Blog', 'database/migrations'));
```

### Ejecutar
```bash
php artisan module:migrate Blog
php artisan module:migrate-rollback Blog
php artisan module:migrate:status Blog
```

## Factories

### Registrar (Laravel 11+)
Las factories se auto-descubren si el namespace esta en composer.json:
```json
"Modules\\Blog\\Database\\Factories\\": "database/factories/"
```

### Usar
```php
BlogPost::factory()->create();
BlogPost::factory()->count(10)->create();
BlogPost::factory()->published()->create();
```

## Seeders

### Ejecutar
```bash
php artisan module:seed Blog
php artisan module:seed Blog --class=BlogPermissionsSeeder
```

## Assets

### Compilar (Mix legacy)
```bash
cd modules/Blog && npm run dev
cd modules/Blog && npm run production
```

### Compilar (Vite - actual)
Agregar al `vite.config.js` raiz:
```js
input: [
    'modules/Blog/resources/js/app.js',
]
```

### Publicar assets
```bash
php artisan module:publish Blog
```

## module.json

```json
{
    "name": "Blog",
    "alias": "blog",
    "description": "Blog module",
    "keywords": ["blog", "posts"],
    "priority": 0,
    "providers": [
        "Modules\\Blog\\Providers\\BlogServiceProvider"
    ],
    "aliases": {},
    "files": [],
    "requires": []
}
```

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| name | string | Nombre PascalCase (requerido) |
| alias | string | Identificador lowercase (requerido) |
| description | string | Descripcion del modulo |
| keywords | array | Palabras clave |
| priority | int | Orden de carga (mayor = antes) |
| providers | array | ServiceProviders a registrar |
| aliases | object | Facade aliases |
| files | array | Archivos adicionales a cargar |
| requires | array | Dependencias de otros modulos |
