# nwidart/laravel-modules - Documentacion Completa

## Indice

1. [Introduccion](01-introduccion.md) - Que es, version, modulos del proyecto, criticos
2. [Configuracion](02-configuracion.md) - config/modules.php completo: stubs, paths, generators, activators, scan, cache
3. [Comandos Artisan](03-comandos-artisan.md) - 50+ comandos: gestion, base de datos, generadores, publicacion
4. [Facade y Metodos](04-facade-y-metodos.md) - Module:: facade, metodos de instancia, helper module_path(), namespaces
5. [ServiceProvider](05-service-provider.md) - Patron completo: boot, register, config, views, routes, NavService, variaciones
6. [Eventos](06-eventos.md) - EventServiceProvider, listeners, observers, registro
7. [Registro de Modulo](07-registro-modulo.md) - 3 puntos obligatorios: providers.php + statuses.json + composer.json
8. [Recursos del Modulo](08-recursos-modulo.md) - Vistas, config, traducciones, migraciones, factories, seeders, assets, module.json

## Referencia rapida

### Crear modulo
```bash
php artisan module:make Blog
```

### Generar componentes
```bash
php artisan module:make-controller PostController Blog
php artisan module:make-model Post Blog -m
php artisan module:make-migration create_posts_table Blog
php artisan module:make-request StorePostRequest Blog
```

### Gestionar modulos
```bash
php artisan module:list
php artisan module:enable Blog
php artisan module:disable Blog
php artisan module:migrate Blog
php artisan module:seed Blog
```

### Facade Module::
```php
Module::all();                    // Todos los modulos
Module::allEnabled();             // Solo habilitados
Module::find('Blog');             // Buscar modulo
Module::find('Blog')->enable();   // Habilitar
Module::find('Blog')->disable();  // Deshabilitar
Module::has('Blog');              // Existe?
module_path('Blog');              // Ruta del modulo
module_path('Blog', 'routes');    // Ruta a subdirectorio
```

### Namespaces
```php
view('blog::index');              // Vista
config('blog.setting');           // Config
trans('blog::messages.hello');    // Traduccion
```

## Fuentes
- [Documentacion oficial v6](https://nwidart.com/laravel-modules/v6/introduction)
- [GitHub](https://github.com/nWidart/laravel-modules)
- config/modules.php del proyecto
- bootstrap/providers.php del proyecto
- Analisis de 40 modulos existentes
