# Ejemplos de uso del módulo Sitemap

## 1. Implementar el Trait en un modelo

### Ejemplo básico (Page Model)

```php
<?php

namespace Modules\Page\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Sitemap\Traits\HasSitemapItems;

class Page extends Model
{
    use HasSitemapItems;

    // El trait proporciona:
    // - getSitemapItems()
    // - getSitemapPriorityAttribute()
    // - getSitemapChangefreqAttribute()
    // - getUrlAttribute()
}
```

### Ejemplo con personalización completa

```php
<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Sitemap\Traits\HasSitemapItems;

class Post extends Model
{
    use HasSitemapItems;

    /**
     * Personalizar qué items incluir en el sitemap
     */
    public static function getSitemapItems()
    {
        return static::where('status', 'published')
            ->where('published_at', '<=', now())
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Personalizar la URL del item
     */
    public function getUrlAttribute(): string
    {
        return route('blog.show', ['slug' => $this->slug]);
    }

    /**
     * Prioridad basada en condiciones
     */
    public function getSitemapPriorityAttribute(): string
    {
        if ($this->is_featured) {
            return '0.9';
        }

        if ($this->views > 1000) {
            return '0.8';
        }

        return '0.6';
    }

    /**
     * Frecuencia de cambio basada en tipo
     */
    public function getSitemapChangefreqAttribute(): string
    {
        if ($this->type === 'news') {
            return 'daily';
        }

        if ($this->type === 'blog') {
            return 'weekly';
        }

        return 'monthly';
    }
}
```

## 2. Configurar el módulo

### config/sitemap.php

```php
<?php

return [
    'name' => 'Sitemap',

    'cache_enabled' => true,
    'cache_duration' => 86400, // 24 horas

    'max_items' => 50000,

    'models' => [
        \Modules\Page\Models\Page::class,
        \Modules\Blog\Models\Post::class,
        \Modules\Product\Models\Product::class,
        \Modules\Event\Models\Event::class,
    ],
];
```

## 3. Uso del Builder manualmente

### En un Controller

```php
<?php

namespace App\Http\Controllers;

use Modules\Sitemap\Facades\Sitemap;

class SitemapController extends Controller
{
    public function generate()
    {
        // Limpiar sitemap anterior
        Sitemap::clear();

        // Agregar homepage
        Sitemap::add(
            url('/'),
            now()->toAtomString(),
            '1.0',
            'daily'
        );

        // Agregar páginas estáticas
        Sitemap::add(url('/about'), null, '0.8', 'monthly');
        Sitemap::add(url('/contact'), null, '0.7', 'monthly');
        Sitemap::add(url('/services'), null, '0.9', 'weekly');

        // Agregar modelos dinámicos
        Sitemap::addModel(\Modules\Page\Models\Page::class);
        Sitemap::addModel(\Modules\Blog\Models\Post::class);

        // Generar archivo
        Sitemap::generate();

        return response()->json([
            'success' => true,
            'message' => 'Sitemap generated',
            'items' => count(Sitemap::getItems()),
        ]);
    }
}
```

### En un Job

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Sitemap\Facades\Sitemap;

class GenerateSitemapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        Sitemap::clear();

        // Agregar todos los modelos
        foreach (config('sitemap.models') as $model) {
            if (class_exists($model)) {
                Sitemap::addModel($model);
            }
        }

        Sitemap::generate();

        // Limpiar caché
        cache()->forget('sitemap-xml');
    }
}
```

## 4. Sitemap Index (para sitios grandes)

### Crear múltiples sitemaps

```php
<?php

namespace App\Http\Controllers;

use Modules\Sitemap\Builder\SitemapBuilder;

class SitemapIndexController extends Controller
{
    public function index()
    {
        $sitemap = new SitemapBuilder();

        // Agregar sub-sitemaps
        $sitemap->addSitemap(route('sitemap.pages'));
        $sitemap->addSitemap(route('sitemap.posts'));
        $sitemap->addSitemap(route('sitemap.products'));

        return response($sitemap->render('index'), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    public function pages()
    {
        $sitemap = new SitemapBuilder();
        $sitemap->addModel(\Modules\Page\Models\Page::class);

        return response($sitemap->render(), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    public function posts()
    {
        $sitemap = new SitemapBuilder();
        $sitemap->addModel(\Modules\Blog\Models\Post::class);

        return response($sitemap->render(), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
```

### Rutas

```php
Route::get('/sitemap.xml', [SitemapIndexController::class, 'index']);
Route::get('/sitemap-pages.xml', [SitemapIndexController::class, 'pages']);
Route::get('/sitemap-posts.xml', [SitemapIndexController::class, 'posts']);
Route::get('/sitemap-products.xml', [SitemapIndexController::class, 'products']);
```

## 5. Eventos y Listeners

### Regenerar sitemap automáticamente

```php
<?php

namespace Modules\Blog\Listeners;

use Modules\Blog\Events\PostPublished;
use Illuminate\Support\Facades\Artisan;

class RegenerateSitemap
{
    public function handle(PostPublished $event)
    {
        // Regenerar sitemap cuando se publica un post
        Artisan::call('sitemap:generate');
    }
}
```

### Registrar el listener

```php
// EventServiceProvider.php

protected $listen = [
    PostPublished::class => [
        RegenerateSitemap::class,
    ],
];
```

## 6. Testing

### Feature Test

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\Page\Models\Page;

class SitemapTest extends TestCase
{
    public function test_sitemap_is_accessible()
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/xml');
    }

    public function test_sitemap_contains_published_pages()
    {
        $page = Page::factory()->create([
            'status' => 'published',
            'slug' => 'test-page',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertSee(route('pages.show', $page->slug));
    }

    public function test_sitemap_command_generates_file()
    {
        $this->artisan('sitemap:generate')
            ->assertSuccessful();

        $this->assertFileExists(public_path('sitemap.xml'));
    }
}
```

## 7. Comandos útiles

```bash
# Generar sitemap
php artisan sitemap:generate

# Ver rutas del sitemap
php artisan route:list | grep sitemap

# Limpiar caché y regenerar
php artisan cache:clear && php artisan sitemap:generate

# Ver contenido del sitemap
cat public/sitemap.xml

# Validar XML
xmllint --noout public/sitemap.xml
```

## 8. Integración con Google Search Console

### Enviar sitemap a Google

```php
use Illuminate\Support\Facades\Http;

public function submitToGoogle()
{
    $sitemapUrl = url('/sitemap.xml');

    $response = Http::get('https://www.google.com/ping', [
        'sitemap' => $sitemapUrl,
    ]);

    return $response->successful();
}
```

### Enviar a Bing

```php
public function submitToBing()
{
    $sitemapUrl = url('/sitemap.xml');
    $apiKey = config('services.bing.webmaster_key');

    $response = Http::get("https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlbatch?apikey={$apiKey}", [
        'siteUrl' => url('/'),
        'urlList' => [$sitemapUrl],
    ]);

    return $response->successful();
}
```

## 9. Personalización avanzada

### Sitemap con imágenes

```php
// Modificar la vista xml.blade.php

<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach($items as $item)
    <url>
        <loc>{{ $item['loc'] }}</loc>
        <lastmod>{{ $item['lastmod'] }}</lastmod>
        <changefreq>{{ $item['changefreq'] }}</changefreq>
        <priority>{{ $item['priority'] }}</priority>
        @if(isset($item['images']))
            @foreach($item['images'] as $image)
                <image:image>
                    <image:loc>{{ $image['url'] }}</image:loc>
                    <image:caption>{{ $image['caption'] ?? '' }}</image:caption>
                </image:image>
            @endforeach
        @endif
    </url>
@endforeach
</urlset>
```

### Agregar imágenes en el modelo

```php
public static function getSitemapItems()
{
    return static::where('status', 'published')
        ->with('images')
        ->get()
        ->map(function ($item) {
            $item->sitemap_images = $item->images->map(function ($image) {
                return [
                    'url' => $image->url,
                    'caption' => $image->alt_text,
                ];
            })->toArray();

            return $item;
        });
}
```
