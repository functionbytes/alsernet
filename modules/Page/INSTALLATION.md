# Instalación del Módulo Page

## Pasos de Instalación

### 1. Ejecutar Migraciones

```bash
php artisan migrate
```

Esto creará la tabla `pages` con todos los campos necesarios.

### 2. Verificar Configuración de Spatie Media Library

El módulo requiere Spatie Media Library. Verifica que esté instalado:

```bash
composer require spatie/laravel-medialibrary
```

Si no está configurado, ejecuta:

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="migrations"
php artisan migrate
```

### 3. Seeders (Opcional)

Para crear páginas de ejemplo:

```bash
php artisan db:seed --class=Modules\\Page\\Database\\Seeders\\PageDatabaseSeeder
```

Esto creará 5 páginas de ejemplo:
- Acerca de Nosotros
- Servicios
- Contacto
- Política de Privacidad
- Términos y Condiciones

### 4. Configuración Personalizada (Opcional)

Publica el archivo de configuración si deseas personalizarlo:

```bash
php artisan vendor:publish --tag=page-config
```

Esto copiará `config/page.php` a tu carpeta de configuración principal.

### 5. Permisos (Opcional con Spatie Permission)

Si usas Spatie Permission, crea los permisos:

```php
use Spatie\Permission\Models\Permission;

Permission::create(['name' => 'view pages']);
Permission::create(['name' => 'create pages']);
Permission::create(['name' => 'edit pages']);
Permission::create(['name' => 'delete pages']);
Permission::create(['name' => 'publish pages']);
```

### 6. Registrar Políticas (Opcional)

En `app/Providers/AuthServiceProvider.php`:

```php
use Modules\Page\Models\Page;
use Modules\Page\Policies\PagePolicy;

protected $policies = [
    Page::class => PagePolicy::class,
];
```

### 7. Verificar Rutas

Las rutas se registran automáticamente. Verifica que funcionen:

```bash
php artisan route:list | grep pages
```

## Configuración del Layout

El módulo usa el layout `page::components.layouts.master`. Puedes:

### Opción 1: Usar el Layout Existente

Edita `/modules/Page/resources/views/components/layouts/master.blade.php` según tu diseño.

### Opción 2: Cambiar a tu Layout Principal

En las vistas, cambia:

```blade
@extends('page::components.layouts.master')
```

Por:

```blade
@extends('layouts.theme') {{-- o tu layout --}}
```

## Testing de Instalación

### 1. Crear una Página de Prueba

```bash
php artisan tinker
```

```php
use Modules\Page\Models\Page;

$page = Page::create([
    'title' => 'Página de Prueba',
    'slug' => 'pagina-de-prueba',
    'content' => '<h1>Contenido de prueba</h1><p>Esta es una página de prueba.</p>',
    'status' => 'published',
    'published_at' => now(),
]);

echo $page->url;
```

### 2. Acceder a las Rutas

- Admin: `http://tu-dominio.test/admin/pages`
- Pública: `http://tu-dominio.test/pagina-de-prueba`

## Solución de Problemas

### Error: Class 'Spatie\MediaLibrary\...' not found

Instala Spatie Media Library:

```bash
composer require spatie/laravel-medialibrary
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider"
php artisan migrate
```

### Error: View [page::components.layouts.master] not found

Crea un layout básico o cambia a tu layout existente en las vistas.

### Error: SQLSTATE[42S02]: Base table or table view not found: 'pages'

Ejecuta las migraciones:

```bash
php artisan migrate
```

### Las rutas públicas no funcionan

Verifica que la ruta `/{slug}` no entre en conflicto con otras rutas. Considera mover la ruta catch-all al final de tu archivo de rutas principal o usar un prefijo:

```php
// En routes/web.php
Route::get('/page/{slug}', [PublicController::class, 'show'])
    ->name('page.show');
```

## Integración con otros Módulos

### Menú de Navegación

Para agregar páginas a tu menú:

```php
use Modules\Page\Models\Page;

$pages = Page::published()->get();

foreach ($pages as $page) {
    echo '<a href="' . $page->url . '">' . $page->title . '</a>';
}
```

### Breadcrumbs

```blade
{{ Breadcrumbs::render('page', $page) }}
```

```php
// En routes/breadcrumbs.php
Breadcrumbs::for('page', function ($trail, $page) {
    $trail->parent('home');
    $trail->push($page->title, $page->url);
});
```

## Próximos Pasos

1. Personaliza las plantillas en `resources/views/public/templates/`
2. Ajusta los estilos CSS según tu diseño
3. Configura permisos y roles
4. Crea tus páginas
5. Integra con tu sistema de menús
6. Configura el SEO según tus necesidades

## Soporte

Para problemas o preguntas, consulta el README.md o la documentación del proyecto.
