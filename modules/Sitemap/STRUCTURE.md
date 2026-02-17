# Estructura del Módulo Sitemap

```
modules/Sitemap/
├── app/
│   ├── Builder/
│   │   └── SitemapBuilder.php          # Constructor principal del sitemap
│   │
│   ├── Console/
│   │   ├── GenerateSitemapCommand.php  # Comando: sitemap:generate
│   │   └── PingSitemapCommand.php      # Comando: sitemap:ping
│   │
│   ├── Facades/
│   │   └── Sitemap.php                 # Facade para acceso fácil
│   │
│   ├── Helpers/
│   │   └── SitemapHelper.php           # Funciones auxiliares
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── SitemapController.php   # Controlador de rutas
│   │   └── Middleware/
│   │       └── CacheSitemapResponse.php # Middleware de caché
│   │
│   ├── Providers/
│   │   ├── EventServiceProvider.php
│   │   ├── RouteServiceProvider.php
│   │   └── SitemapServiceProvider.php  # Provider principal
│   │
│   └── Traits/
│       └── HasSitemapItems.php         # Trait para modelos
│
├── config/
│   └── config.php                      # Configuración del módulo
│
├── database/
│   ├── migrations/
│   └── seeders/
│       └── SitemapDatabaseSeeder.php
│
├── resources/
│   ├── assets/
│   │   ├── js/
│   │   └── sass/
│   └── views/
│       ├── components/
│       │   └── layouts/
│       │       └── master.blade.php
│       ├── formats/
│       │   ├── index.blade.php         # Template para sitemap index
│       │   └── xml.blade.php           # Template para sitemap XML
│       └── v5.blade.php
│
├── routes/
│   ├── api.php
│   └── web.php                         # Rutas del sitemap
│
├── tests/
│   └── Feature/
│       └── SitemapTest.php             # Tests del módulo
│
├── composer.json
├── module.json
├── package.json
├── vite.config.js
│
└── Documentación/
    ├── README.md                       # Documentación principal
    ├── INSTALLATION.md                 # Guía de instalación
    ├── EXAMPLES.md                     # Ejemplos de uso
    └── STRUCTURE.md                    # Este archivo
```

## Descripción de componentes

### Core Components

#### SitemapBuilder.php
Clase principal que construye el sitemap. Métodos:
- `add()` - Agregar URL individual
- `addModel()` - Agregar modelo completo
- `addSitemap()` - Agregar sub-sitemap
- `render()` - Renderizar XML
- `generate()` - Generar archivo
- `clear()` - Limpiar items

#### HasSitemapItems Trait
Trait que deben usar los modelos. Proporciona:
- `getSitemapItems()` - Obtener items del modelo
- `getSitemapPriorityAttribute()` - Prioridad del item
- `getSitemapChangefreqAttribute()` - Frecuencia de cambio
- `getUrlAttribute()` - URL del item

#### SitemapHelper.php
Funciones auxiliares:
- Validación de prioridades y frecuencias
- Formateo de fechas
- Escape de XML
- Ping a buscadores

### Controllers

#### SitemapController.php
Controlador que maneja las rutas:
- `index()` - Sitemap principal
- `pages()` - Sitemap de páginas
- `posts()` - Sitemap de posts
- `sitemapIndex()` - Índice de sitemaps

### Commands

#### GenerateSitemapCommand.php
Genera el archivo sitemap.xml
```bash
php artisan sitemap:generate
```

#### PingSitemapCommand.php
Notifica a buscadores sobre actualizaciones
```bash
php artisan sitemap:ping
```

### Views

#### xml.blade.php
Template principal para sitemap XML:
```xml
<urlset>
  <url>
    <loc>URL</loc>
    <lastmod>Fecha</lastmod>
    <changefreq>Frecuencia</changefreq>
    <priority>Prioridad</priority>
  </url>
</urlset>
```

#### index.blade.php
Template para sitemap index:
```xml
<sitemapindex>
  <sitemap>
    <loc>URL del sitemap</loc>
    <lastmod>Fecha</lastmod>
  </sitemap>
</sitemapindex>
```

### Configuration

#### config.php
```php
[
    'cache_enabled' => true,
    'cache_duration' => 86400,
    'max_items' => 50000,
    'models' => [],
]
```

### Routes

#### web.php
```php
/sitemap.xml          → index()
/sitemap-pages.xml    → pages()
/sitemap-posts.xml    → posts()
/sitemap-index.xml    → sitemapIndex()
```

## Flujo de ejecución

### 1. Generación manual
```
Usuario → comando artisan → GenerateSitemapCommand
  ↓
SitemapBuilder::clear()
  ↓
SitemapBuilder::add() para cada modelo
  ↓
SitemapBuilder::generate()
  ↓
public/sitemap.xml
```

### 2. Acceso vía web
```
Usuario → /sitemap.xml → SitemapController::index()
  ↓
Cache::remember()
  ↓
SitemapBuilder::render()
  ↓
View formats/xml.blade.php
  ↓
Response XML
```

### 3. Programación automática
```
Scheduler (02:00 AM) → GenerateSitemapCommand
  ↓
Regenera sitemap
  ↓
Limpia caché
```

## Integración con otros módulos

### Módulo Page
```php
// Page.php
use Modules\Sitemap\Traits\HasSitemapItems;

class Page extends Model
{
    use HasSitemapItems;
}

// config/sitemap.php
'models' => [
    \Modules\Page\Models\Page::class,
],
```

### Módulo Post
```php
// Post.php
use Modules\Sitemap\Traits\HasSitemapItems;

class Post extends Model
{
    use HasSitemapItems;
}

// config/sitemap.php
'models' => [
    \Modules\Post\Models\Post::class,
],
```

## Extensibilidad

### Agregar nuevos formatos
1. Crear vista en `resources/views/formats/`
2. Llamar con `$sitemap->render('nombre-formato')`

### Agregar campos personalizados
1. Extender `SitemapBuilder`
2. Modificar template XML
3. Agregar métodos en el trait

### Integración con terceros
1. Implementar eventos
2. Crear listeners
3. Extender comandos

## Testing

### Unit Tests
```bash
php artisan test --filter SitemapTest
```

### Manual Testing
```bash
# Generar
php artisan sitemap:generate

# Verificar
cat public/sitemap.xml

# Validar XML
xmllint --noout public/sitemap.xml

# Contar URLs
grep -c "<loc>" public/sitemap.xml
```

## Performance

### Caché
- Duración: 24 horas (configurable)
- Key: `sitemap-xml`
- Limpieza: Automática al regenerar

### Optimizaciones
- Singleton del Builder
- Lazy loading de modelos
- Generación asíncrona posible
- Compresión XML opcional

## Seguridad

### Headers
```
Content-Type: application/xml
Cache-Control: public, max-age=86400
X-Robots-Tag: noindex, nofollow
```

### Validación
- Escapado de XML
- Validación de URLs
- Límite de items (50,000)

## Mantenimiento

### Logs
```bash
storage/logs/laravel.log
```

### Monitoreo
- Google Search Console
- Bing Webmaster Tools
- Errores de generación

### Backup
```bash
cp public/sitemap.xml backups/sitemap-$(date +%Y%m%d).xml
```
