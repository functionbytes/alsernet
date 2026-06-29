# Comandos Útiles - Módulo Page

## Instalación

### Instalación Completa Automática
```bash
php artisan page:install
```

### Instalación con Seeders
```bash
php artisan page:install --seed
```

### Instalación Manual
```bash
# 1. Ejecutar migraciones
php artisan migrate

# 2. Ejecutar seeders (opcional)
php artisan db:seed --class=Modules\\Page\\Database\\Seeders\\PageDatabaseSeeder

# 3. Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Migraciones

### Ejecutar Migraciones
```bash
php artisan migrate
```

### Rollback
```bash
php artisan migrate:rollback
```

### Refrescar Base de Datos
```bash
php artisan migrate:fresh
```

### Ver Estado de Migraciones
```bash
php artisan migrate:status
```

## Seeders

### Ejecutar Seeder de Páginas
```bash
php artisan db:seed --class=Modules\\Page\\Database\\Seeders\\PageDatabaseSeeder
```

### Refrescar y Seedear
```bash
php artisan migrate:fresh --seed
```

## Testing

### Crear Páginas de Prueba con Tinker
```bash
php artisan tinker
```

```php
// Crear una página
use Modules\Page\Models\Page;

$page = Page::create([
    'title' => 'Mi Primera Página',
    'slug' => 'mi-primera-pagina',
    'content' => '<h1>Hola Mundo</h1><p>Esta es mi primera página.</p>',
    'status' => 'published',
    'published_at' => now(),
]);

// Usar Factory
$page = Page::factory()->create();
$page = Page::factory()->published()->create();
$pages = Page::factory()->count(10)->create();

// Consultas
Page::published()->get();
Page::draft()->get();
Page::search('término')->get();
```

## Rutas

### Listar Todas las Rutas del Módulo
```bash
php artisan route:list | grep pages
```

### Listar Rutas Admin
```bash
php artisan route:list | grep settings.pages
```

### Listar Rutas API
```bash
php artisan route:list | grep api.*pages
```

### Limpiar Caché de Rutas
```bash
php artisan route:clear
```

### Cachear Rutas (Producción)
```bash
php artisan route:cache
```

## Caché

### Limpiar Todo el Caché
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Cachear Configuración (Producción)
```bash
php artisan config:cache
```

### Cachear Vistas (Producción)
```bash
php artisan view:cache
```

## Desarrollo

### Verificar Sintaxis PHP
```bash
php -l modules/Page/app/Models/Page.php
```

### Ver Logs en Tiempo Real
```bash
tail -f storage/logs/laravel.log
```

### Modo de Mantenimiento
```bash
# Activar
php artisan down

# Desactivar
php artisan up
```

## Publicación

### Publicar Configuración
```bash
php artisan vendor:publish --tag=page-config
```

### Publicar Vistas
```bash
php artisan vendor:publish --tag=page-module-views
```

## Base de Datos

### Conectar a Base de Datos
```bash
php artisan db
```

### Ejecutar Query Directo
```bash
php artisan tinker
```

```php
DB::table('pages')->count();
DB::table('pages')->where('status', 'published')->get();
```

## Optimización

### Optimizar para Producción
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Limpiar Optimizaciones
```bash
php artisan optimize:clear
```

## Backup

### Hacer Backup de Base de Datos (requiere mysqldump)
```bash
mysqldump -u usuario -p base_de_datos > backup_pages.sql
```

### Restaurar Backup
```bash
mysql -u usuario -p base_de_datos < backup_pages.sql
```

## Testing Avanzado

### Crear Múltiples Páginas de Prueba
```bash
php artisan tinker
```

```php
use Modules\Page\Models\Page;

// 10 páginas publicadas
Page::factory()->published()->count(10)->create();

// 5 borradores
Page::factory()->draft()->count(5)->create();

// 3 pendientes
Page::factory()->pending()->count(3)->create();

// Mezcla
collect([
    'published' => 10,
    'draft' => 5,
    'pending' => 3,
])->each(function($count, $status) {
    Page::factory()->{$status}()->count($count)->create();
});
```

## Consultas Útiles

### Páginas Publicadas
```php
use Modules\Page\Models\Page;

Page::published()->get();
Page::published()->paginate(10);
```

### Buscar Páginas
```php
Page::search('término')->get();
Page::where('title', 'like', '%término%')->get();
```

### Páginas por Template
```php
Page::where('template', 'full-width')->get();
```

### Últimas Páginas Publicadas
```php
Page::published()
    ->orderBy('published_at', 'desc')
    ->limit(5)
    ->get();
```

### Contar por Estado
```php
[
    'published' => Page::published()->count(),
    'draft' => Page::draft()->count(),
    'pending' => Page::pending()->count(),
    'total' => Page::count(),
    'trashed' => Page::onlyTrashed()->count(),
];
```

## API Testing

### Con cURL

#### Listar Páginas Públicas
```bash
curl -X GET http://tu-dominio.test/api/v1/pages
```

#### Ver Página por Slug
```bash
curl -X GET http://tu-dominio.test/api/v1/pages/mi-pagina
```

#### Crear Página (requiere auth)
```bash
curl -X POST http://tu-dominio.test/api/v1/admin/pages \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Nueva Página",
    "content": "<p>Contenido</p>",
    "status": "draft"
  }'
```

## Service Layer

### Usar el PageService
```php
use Modules\Page\Services\PageService;

$service = app(PageService::class);

// Crear
$page = $service->createPage([
    'title' => 'Mi Página',
    'content' => '<p>Contenido</p>',
    'status' => 'published',
]);

// Actualizar
$service->updatePage($page, [
    'title' => 'Nuevo Título',
]);

// Publicar
$service->publishPage($page);

// Duplicar
$newPage = $service->duplicatePage($page);

// Eliminar
$service->deletePage($page);

// Obtener con filtros
$pages = $service->getPages([
    'status' => 'published',
    'search' => 'término',
    'per_page' => 20,
]);
```

## Debugging

### Habilitar Debug
```bash
# En .env
APP_DEBUG=true
```

### Ver Queries SQL
```php
DB::enableQueryLog();

Page::published()->get();

dd(DB::getQueryLog());
```

### Inspeccionar Modelo
```php
$page = Page::first();
dd($page->toArray());
dd($page->getAttributes());
dd($page->getRelations());
```

## Mantenimiento

### Limpiar Páginas Eliminadas
```bash
php artisan tinker
```

```php
// Ver eliminadas
Modules\Page\Models\Page::onlyTrashed()->get();

// Restaurar
Modules\Page\Models\Page::onlyTrashed()->restore();

// Eliminar permanentemente
Modules\Page\Models\Page::onlyTrashed()->forceDelete();
```

### Limpiar Medios Huérfanos
```bash
php artisan media-library:clean
```

## Permisos (Spatie)

### Crear Permisos
```bash
php artisan tinker
```

```php
use Spatie\Permission\Models\Permission;

Permission::create(['name' => 'view pages']);
Permission::create(['name' => 'create pages']);
Permission::create(['name' => 'edit pages']);
Permission::create(['name' => 'delete pages']);
Permission::create(['name' => 'publish pages']);
```

### Asignar Permisos a Rol
```php
use Spatie\Permission\Models\Role;

$admin = Role::findByName('settings');
$admin->givePermissionTo([
    'view pages',
    'create pages',
    'edit pages',
    'delete pages',
    'publish pages',
]);
```

## Producción

### Verificar Instalación
```bash
# Verificar archivos
ls -la modules/Page/

# Verificar tablas
php artisan tinker
>>> DB::table('pages')->count()

# Verificar rutas
php artisan route:list | grep pages

# Verificar permisos
ls -la storage/
```

### Problemas Comunes

#### Error: "Class Page not found"
```bash
composer dump-autoload
php artisan clear-compiled
php artisan cache:clear
```

#### Error: "Table pages doesn't exist"
```bash
php artisan migrate
```

#### Error: "View not found"
```bash
php artisan view:clear
```

#### Error de permisos en storage/
```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

## Recursos Adicionales

- README.md - Documentación completa
- INSTALLATION.md - Guía de instalación
- SUMMARY.md - Resumen del módulo
- CHECKLIST.md - Lista de verificación
