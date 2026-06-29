# Módulo Page

Módulo completo de gestión de páginas estáticas para Laravel con soporte para múltiples plantillas, SEO, y gestión de medios.

## Características

- CRUD completo de páginas
- Gestión de estados (borrador, publicado, pendiente)
- Múltiples plantillas (default, full-width, no-sidebar, landing, contact)
- Soporte para imágenes destacadas con Spatie Media Library
- Campos SEO completos (título, descripción, keywords)
- Soft deletes
- Sistema de slugs únicos automáticos
- Filtros y búsqueda avanzada
- API REST completa
- Vistas públicas y administrativas
- Factory para testing
- Políticas de autorización

## Instalación

### 1. Ejecutar Migraciones

```bash
php artisan migrate
```

### 2. Publicar Configuración (Opcional)

```bash
php artisan vendor:publish --tag=page-config
```

### 3. Ejecutar Seeders (Opcional)

```bash
php artisan db:seed --class=Modules\\Page\\Database\\Seeders\\PageDatabaseSeeder
```

## Uso

### Rutas Administrativas

Todas las rutas admin requieren autenticación:

- `GET /admin/pages` - Listar páginas
- `GET /admin/pages/create` - Formulario crear
- `POST /admin/pages` - Guardar nueva página
- `GET /admin/pages/{page}` - Ver página
- `GET /admin/pages/{page}/edit` - Formulario editar
- `PUT /admin/pages/{page}` - Actualizar página
- `DELETE /admin/pages/{page}` - Eliminar página
- `POST /admin/pages/{page}/publish` - Publicar
- `POST /admin/pages/{page}/unpublish` - Despublicar
- `POST /admin/pages/{page}/duplicate` - Duplicar

### Rutas Públicas

- `GET /pages` - Listar páginas públicas
- `GET /{slug}` - Ver página por slug

### API REST

**Públicas:**
- `GET /api/v1/pages` - Listar páginas publicadas
- `GET /api/v1/pages/{slug}` - Ver página por slug

**Protegidas (requieren autenticación):**
- `GET /api/v1/admin/pages` - Listar todas
- `POST /api/v1/admin/pages` - Crear
- `GET /api/v1/admin/pages/{id}` - Ver
- `PUT /api/v1/admin/pages/{id}` - Actualizar
- `DELETE /api/v1/admin/pages/{id}` - Eliminar

## Configuración

El archivo de configuración `config/page.php` permite personalizar:

```php
return [
    // Plantillas disponibles
    'templates' => [
        'default' => 'Default',
        'full-width' => 'Full Width',
        'no-sidebar' => 'No Sidebar',
        'landing' => 'Landing Page',
        'contact' => 'Contact Page',
    ],

    // Paginación
    'per_page' => 20,

    // Configuración de medios
    'media' => [
        'max_file_size' => 2048, // KB
        'allowed_mimes' => ['jpeg', 'png', 'jpg', 'gif', 'webp'],
    ],

    // SEO
    'seo' => [
        'title_max_length' => 60,
        'description_max_length' => 160,
        'keywords_max_length' => 255,
    ],

    // Caché
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // segundos
    ],
];
```

## Uso Programático

### Service Layer

```php
use Modules\Page\Services\PageService;

$pageService = app(PageService::class);

// Crear página
$page = $pageService->createPage([
    'title' => 'Mi Página',
    'content' => '<p>Contenido...</p>',
    'status' => 'published',
]);

// Actualizar página
$pageService->updatePage($page, [
    'title' => 'Nuevo Título',
]);

// Eliminar página
$pageService->deletePage($page);

// Publicar/Despublicar
$pageService->publishPage($page);
$pageService->unpublishPage($page);

// Duplicar
$newPage = $pageService->duplicatePage($page);

// Obtener páginas con filtros
$pages = $pageService->getPages([
    'status' => 'published',
    'search' => 'término',
    'template' => 'default',
]);
```

### Modelo

```php
use Modules\Page\Models\Page;

// Scopes
$published = Page::published()->get();
$drafts = Page::draft()->get();
$pending = Page::pending()->get();
$results = Page::search('término')->get();

// Helpers
$page->isPublished(); // bool
$page->isDraft(); // bool
$page->isPending(); // bool
$page->publish(); // Publicar
$page->unpublish(); // Despublicar
$page->getExcerpt(150); // Extracto

// Accesores
$page->url; // URL completa
$page->featured_image; // URL imagen destacada

// Media
$page->addMedia($file)->toMediaCollection('featured');
$page->getFirstMedia('featured');
```

## Plantillas

### Crear una Plantilla Personalizada

1. Crea el archivo de vista en `resources/views/public/templates/mi-plantilla.blade.php`
2. Agrega la plantilla al config:

```php
'templates' => [
    'mi-plantilla' => 'Mi Plantilla Personalizada',
],
```

3. Usa la plantilla al crear/editar una página

## Testing

```php
use Modules\Page\Models\Page;

// Factory
$page = Page::factory()->create();
$published = Page::factory()->published()->create();
$draft = Page::factory()->draft()->create();
$pending = Page::factory()->pending()->create();

// Batch
$pages = Page::factory()->count(10)->create();
```

## Permisos (Opcional)

Si usas Spatie Permission, estos son los permisos sugeridos:

- `view pages` - Ver páginas
- `create pages` - Crear páginas
- `edit pages` - Editar páginas
- `delete pages` - Eliminar páginas
- `publish pages` - Publicar/despublicar

## Dependencias

- Laravel 10+
- Spatie Media Library
- Nwidart Laravel Modules

## Estructura de Archivos

```
modules/Page/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── PageController.php
│   │   │   └── PublicController.php
│   │   ├── Middleware/
│   │   │   └── CheckPagePublished.php
│   │   ├── Requests/
│   │   │   ├── CreatePageRequest.php
│   │   │   └── UpdatePageRequest.php
│   │   └── Resources/
│   │       ├── PageResource.php
│   │       └── PageCollection.php
│   ├── Models/
│   │   └── Page.php
│   ├── Policies/
│   │   └── PagePolicy.php
│   ├── Providers/
│   │   └── PageServiceProvider.php
│   └── Services/
│       └── PageService.php
├── config/
│   └── config.php
├── database/
│   ├── factories/
│   │   └── PageFactory.php
│   ├── migrations/
│   │   └── 2026_02_08_000001_create_pages_table.php
│   └── seeders/
│       └── PageDatabaseSeeder.php
├── resources/
│   └── views/
│       ├── admin/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   ├── edit.blade.php
│       │   └── form.blade.php
│       └── public/
│           ├── index.blade.php
│           ├── show.blade.php
│           └── templates/
│               ├── default.blade.php
│               ├── full-width.blade.php
│               └── no-sidebar.blade.php
├── routes/
│   ├── api.php
│   └── web.php
└── README.md
```

## Licencia

MIT
