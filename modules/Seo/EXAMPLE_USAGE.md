# Ejemplos de Uso - Schema.org Structured Data

## 1. Ejemplo Completo: Blog Post con Schema

### Modelo (BlogPost.php)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Seo\Traits\HasSeo;
use Modules\Seo\Traits\HasStructuredData;

class BlogPost extends Model
{
    use HasSeo, HasStructuredData;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'author_id',
        'category_id',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // Relaciones
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Schema.org configuration
    public function getSchemaType(): string
    {
        return 'BlogPosting';
    }

    public function getSchemaOptions(): array
    {
        return [
            'author' => $this->author->name ?? 'Admin',
        ];
    }

    public function getBreadcrumbItems(): ?array
    {
        return [
            ['name' => 'Inicio', 'url' => route('home')],
            ['name' => 'Blog', 'url' => route('blog.index')],
            ['name' => $this->category->name ?? 'General', 'url' => route('blog.category', $this->category->slug ?? 'general')],
            ['name' => $this->title, 'url' => route('blog.show', $this->slug)],
        ];
    }
}
```

### Controlador (BlogPostController.php)

```php
<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Modules\Seo\Facades\Seo;

class BlogPostController extends Controller
{
    public function show($slug)
    {
        $post = BlogPost::with(['author', 'category'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Cargar SEO y schemas desde el modelo
        Seo::loadFromModel($post);
        Seo::loadSchemasFromModel($post);

        return view('blog.show', compact('post'));
    }
}
```

### Vista (blog/show.blade.php)

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO Meta Tags + Schema.org --}}
    <x-seo-tags :model="$post" />
</head>
<body>
    <article>
        <h1>{{ $post->title }}</h1>
        <p class="author">Por {{ $post->author->name }}</p>
        <div class="content">
            {!! $post->content !!}
        </div>
    </article>
</body>
</html>
```

## 2. Ejemplo: Página con FAQs

### Modelo (Page.php)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Seo\Traits\HasSeo;
use Modules\Seo\Traits\HasStructuredData;

class Page extends Model
{
    use HasSeo, HasStructuredData;

    protected $fillable = ['title', 'slug', 'content', 'faqs'];

    protected $casts = [
        'faqs' => 'array',
    ];

    public function getSchemaType(): string
    {
        return 'WebPage';
    }

    public function getBreadcrumbItems(): ?array
    {
        return [
            ['name' => 'Inicio', 'url' => route('home')],
            ['name' => $this->title, 'url' => route('page.show', $this->slug)],
        ];
    }

    public function getFaqItems(): ?array
    {
        // Asumiendo que FAQs está guardado como JSON en la BD
        if (empty($this->faqs)) {
            return null;
        }

        return $this->faqs;

        // Formato esperado:
        // [
        //     ['question' => '¿Pregunta 1?', 'answer' => 'Respuesta 1'],
        //     ['question' => '¿Pregunta 2?', 'answer' => 'Respuesta 2'],
        // ]
    }
}
```

### Vista

```blade
<x-seo-tags :model="$page" />
{{-- Genera automáticamente WebPage, Breadcrumb y FAQ schemas --}}
```

## 3. Ejemplo: Producto de eCommerce

### Modelo (Product.php)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Seo\Traits\HasSeo;
use Modules\Seo\Traits\HasStructuredData;

class Product extends Model
{
    use HasSeo, HasStructuredData;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'sku',
        'brand',
        'image',
        'stock',
    ];

    public function getSchemaType(): string
    {
        return 'Product';
    }

    public function getSchemaOptions(): array
    {
        return [
            'brand' => $this->brand,
            'price' => $this->price,
            'currency' => 'USD',
            'availability' => $this->stock > 0
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'rating' => [
                'value' => $this->average_rating ?? 0,
                'count' => $this->reviews_count ?? 0,
            ],
        ];
    }

    public function getBreadcrumbItems(): ?array
    {
        return [
            ['name' => 'Inicio', 'url' => route('home')],
            ['name' => 'Tienda', 'url' => route('shop.index')],
            ['name' => $this->category->name ?? 'Productos', 'url' => route('shop.category', $this->category->slug ?? 'productos')],
            ['name' => $this->name, 'url' => route('shop.show', $this->slug)],
        ];
    }
}
```

## 4. Ejemplo: Schemas Manuales

### En un Controlador

```php
<?php

namespace App\Http\Controllers;

use Modules\Seo\Services\SchemaOrgService;
use Modules\Seo\Facades\Seo;

class HomeController extends Controller
{
    public function index(SchemaOrgService $schemaService)
    {
        // Configurar SEO básico
        Seo::setTitle('Inicio');
        Seo::setDescription('Bienvenido a nuestro sitio web');

        // Agregar Organization schema
        Seo::addSchema($schemaService->generateOrganizationSchema());

        // Agregar WebPage schema
        Seo::addSchema($schemaService->generateWebPageSchema([
            'title' => 'Página de Inicio',
            'description' => 'Bienvenido a nuestro sitio web',
            'url' => route('home'),
        ]));

        return view('home');
    }
}
```

### En una Vista Blade (sin modelo)

```blade
@php
    $schemaService = app(\Modules\Seo\Services\SchemaOrgService::class);

    // Generar breadcrumbs
    $breadcrumbSchema = $schemaService->generateBreadcrumbSchema([
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Contacto', 'url' => route('contact')],
    ]);

    app('seo')->addSchema($breadcrumbSchema);
@endphp

<head>
    @seoTags
    @schemaScript
</head>
```

## 5. Ejemplo: LocalBusiness Schema

### Configuración (.env)

```env
SEO_SCHEMA_ENABLED=true

# Organization/Business Info
SEO_ORGANIZATION_NAME="Mi Restaurante"
SEO_ORGANIZATION_URL="https://mirestaurante.com"
SEO_ORGANIZATION_LOGO="https://mirestaurante.com/logo.png"
SEO_ORGANIZATION_EMAIL="info@mirestaurante.com"
SEO_ORGANIZATION_PHONE="+34-123-456-789"

# Address
SEO_ORGANIZATION_ADDRESS_STREET="Calle Principal 123"
SEO_ORGANIZATION_ADDRESS_CITY="Madrid"
SEO_ORGANIZATION_ADDRESS_REGION="Comunidad de Madrid"
SEO_ORGANIZATION_ADDRESS_POSTAL="28001"
SEO_ORGANIZATION_ADDRESS_COUNTRY="ES"

# Social Media
SEO_SOCIAL_FACEBOOK="https://facebook.com/mirestaurante"
SEO_SOCIAL_INSTAGRAM="https://instagram.com/mirestaurante"
```

### En Controlador

```php
public function about(SchemaOrgService $schemaService)
{
    // Generar LocalBusiness schema
    $businessSchema = $schemaService->generateLocalBusinessSchema([
        'type' => 'Restaurant',
        'price_range' => '$$',
        'opening_hours' => [
            [
                'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'opens' => '12:00',
                'closes' => '23:00',
            ],
            [
                'days' => ['Saturday', 'Sunday'],
                'opens' => '13:00',
                'closes' => '00:00',
            ],
        ],
    ]);

    app('seo')->addSchema($businessSchema);

    return view('about');
}
```

## 6. Testing de Schemas

### Comando Artisan

```bash
# Ejecutar todos los tests
php artisan seo:generate-schemas --test

# Generar schema específico
php artisan seo:generate-schemas article --pretty
php artisan seo:generate-schemas faq --pretty
php artisan seo:generate-schemas product --pretty

# Ver opciones disponibles
php artisan seo:generate-schemas --help
```

### En Tests PHPUnit

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\BlogPost;
use Modules\Seo\Services\SchemaOrgService;

class SchemaOrgTest extends TestCase
{
    public function test_blog_post_generates_valid_schema()
    {
        $post = BlogPost::factory()->create([
            'title' => 'Test Post',
            'content' => 'Test content',
        ]);

        $schema = $post->generateSchema();

        $this->assertArrayHasKey('@context', $schema);
        $this->assertArrayHasKey('@type', $schema);
        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('BlogPosting', $schema['@type']);
    }

    public function test_schema_service_generates_valid_json()
    {
        $service = app(SchemaOrgService::class);

        $schema = $service->generateBreadcrumbSchema([
            ['name' => 'Home', 'url' => 'https://example.com'],
        ]);

        $json = $service->renderJsonLd($schema);

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals('BreadcrumbList', $decoded['@type']);
    }
}
```

## 7. Validación de Schemas

### Google Rich Results Test

1. Visita: https://search.google.com/test/rich-results
2. Ingresa la URL de tu página
3. Verifica que los schemas sean válidos

### Schema.org Validator

1. Visita: https://validator.schema.org/
2. Pega el JSON-LD generado
3. Verifica errores y advertencias

### Validación Programática

```php
use Modules\Seo\Services\SchemaOrgService;

$service = app(SchemaOrgService::class);
$schema = $service->generateArticleSchema($post);

// Verificar que se pueda codificar como JSON
$json = json_encode($schema);
if (json_last_error() !== JSON_ERROR_NONE) {
    throw new \Exception('Invalid JSON: ' . json_last_error_msg());
}

// Verificar campos requeridos
if (!isset($schema['@context']) || !isset($schema['@type'])) {
    throw new \Exception('Missing required schema fields');
}
```

## 8. Tips y Mejores Prácticas

### Múltiples Schemas

```php
// En controlador
public function show($slug)
{
    $post = BlogPost::findBySlug($slug);

    $schemaService = app(SchemaOrgService::class);

    // Agregar múltiples schemas
    app('seo')->setSchemas([
        $schemaService->generateArticleSchema($post),
        $schemaService->generateBreadcrumbSchema([
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Blog', 'url' => route('blog.index')],
            ['name' => $post->title, 'url' => route('blog.show', $post->slug)],
        ]),
        $schemaService->generateOrganizationSchema(),
    ]);

    return view('blog.show', compact('post'));
}
```

### Schema Condicional

```php
public function getFaqItems(): ?array
{
    // Solo devolver FAQs si existen y están publicadas
    if (!$this->has_faqs || !$this->published_at) {
        return null;
    }

    return $this->faqs;
}
```

### Override de Schema Type

```php
// En el modelo, cambiar dinámicamente el tipo
public function getSchemaType(): string
{
    return $this->is_news
        ? 'NewsArticle'
        : 'BlogPosting';
}
```

### Pretty Print en Desarrollo

```blade
{{-- En layouts/app.blade.php --}}
{!! $post->renderCompleteSchemaScript(config('app.debug')) !!}
{{-- Formatea el JSON cuando APP_DEBUG=true --}}
```
