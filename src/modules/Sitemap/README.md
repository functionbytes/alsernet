# Sitemap Module

Módulo completo para la generación automática de sitemaps XML para Laravel.

## Características

- Generación automática de sitemap.xml
- Soporte para múltiples modelos
- Caché configurable
- Trait `HasSitemapItems` para modelos
- Comando artisan para generar sitemap
- Programación automática diaria
- Sitemaps específicos por tipo de contenido
- Sitemap index para sitios grandes

## Instalación

El módulo ya está instalado. Solo necesitas configurarlo.

## Configuración

### 1. Publicar la configuración (opcional)

```bash
php artisan vendor:publish --tag=config --provider="Modules\Sitemap\Providers\SitemapServiceProvider"
```

### 2. Configurar modelos

Edita `modules/Sitemap/config/config.php`:

```php
'models' => [
    \Modules\Page\Models\Page::class,
    \Modules\Post\Models\Post::class,
],
```

### 3. Agregar el Trait a tus modelos

En tu modelo (ejemplo: `Page.php`):

```php
use Modules\Sitemap\Traits\HasSitemapItems;

class Page extends Model
{
    use HasSitemapItems;

    // Opcional: personalizar URL
    public function getUrlAttribute(): string
    {
        return route('pages.show', $this->slug);
    }

    // Opcional: personalizar prioridad
    public function getSitemapPriorityAttribute(): string
    {
        return $this->is_featured ? '0.9' : '0.7';
    }

    // Opcional: personalizar frecuencia de cambio
    public function getSitemapChangefreqAttribute(): string
    {
        return 'weekly';
    }
}
```

## Uso

### Generar Sitemap manualmente

```bash
php artisan sitemap:generate
```

### Acceder al Sitemap

El sitemap estará disponible en:

- `/sitemap.xml` - Sitemap principal
- `/sitemap-pages.xml` - Solo páginas
- `/sitemap-posts.xml` - Solo posts
- `/sitemap-index.xml` - Índice de sitemaps

### Agregar URLs manualmente

```php
$sitemap = app('sitemap');

$sitemap->add(
    url('/contact'),
    now()->toAtomString(),
    '0.8',
    'monthly'
);

$sitemap->generate();
```

### Programación automática

El sitemap se regenera automáticamente todos los días a las 2:00 AM.

Para cambiar la frecuencia, edita `SitemapServiceProvider.php`:

```php
$schedule->command('sitemap:generate')->hourly();
// o
$schedule->command('sitemap:generate')->weekly();
```

## Caché

El sitemap se cachea por 24 horas por defecto. Para limpiar el caché:

```bash
php artisan sitemap:generate
```

O manualmente:

```php
cache()->forget('sitemap-xml');
```

## Personalización del Trait

### Filtrar items

Por defecto, el trait filtra por `status = 'published'`. Puedes personalizar:

```php
public static function getSitemapItems()
{
    return static::where('is_active', true)
        ->where('published_at', '<=', now())
        ->orderBy('updated_at', 'desc')
        ->get();
}
```

### Frecuencias de cambio disponibles

- `always`
- `hourly`
- `daily`
- `weekly`
- `monthly`
- `yearly`
- `never`

### Prioridades

- `0.0` a `1.0`
- `1.0` = Máxima prioridad (homepage)
- `0.5` = Prioridad media
- `0.0` = Mínima prioridad

## Robots.txt

Agrega esto a tu `public/robots.txt`:

```
User-agent: *
Allow: /

Sitemap: https://tudominio.com/sitemap.xml
```

## API del Builder

```php
$sitemap = app('sitemap');

// Agregar URL individual
$sitemap->add($url, $lastmod, $priority, $changefreq);

// Agregar modelo completo
$sitemap->addModel(Page::class);

// Agregar sub-sitemap
$sitemap->addSitemap($url, $lastmod);

// Generar archivo
$sitemap->generate();

// Renderizar XML
$xml = $sitemap->render();

// Limpiar items
$sitemap->clear();
```

## Testing

```bash
# Generar y verificar
php artisan sitemap:generate

# Ver el sitemap
curl http://localhost/sitemap.xml

# Validar con Google
# https://search.google.com/search-console
```

## Límites

- Máximo 50,000 URLs por sitemap
- Máximo 50MB por archivo
- Para sitios más grandes, usa sitemap index

## Troubleshooting

### El sitemap no se actualiza

```bash
php artisan cache:clear
php artisan sitemap:generate
```

### No se generan URLs

Verifica que:
1. El modelo tiene el trait `HasSitemapItems`
2. El modelo está en el config
3. Hay registros con `status = 'published'`

### Errores de permisos

```bash
chmod 775 public/
```

## License

MIT
