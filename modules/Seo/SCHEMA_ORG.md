# Schema.org Structured Data - Seo Module

Este módulo implementa Schema.org structured data (JSON-LD) según la especificación v13.

## Características

- Generación automática de schemas JSON-LD
- Soporte para múltiples tipos de schema: Article, Organization, Breadcrumb, FAQ, WebPage, Product, LocalBusiness
- Trait `HasStructuredData` para modelos
- Servicio `SchemaOrgService` con métodos dedicados
- Configuración flexible
- Comando artisan para testing
- Integración con SeoService

## Instalación y Configuración

### 1. Configuración

El archivo de configuración está en `modules/Seo/config/Seo.php`. Las opciones principales son:

```php
'schema' => [
    'enabled' => true, // Habilitar/deshabilitar schemas globalmente

    'organization' => [
        'type' => 'Organization',
        'name' => 'Mi Empresa',
        'url' => 'https://example.com',
        'logo' => 'https://example.com/logo.png',
        'email' => 'info@example.com',
        'phone' => '+1-555-123-4567',
        // ... más configuraciones
    ],

    'local_business' => [
        'type' => 'Restaurant',
        'name' => 'Mi Negocio Local',
        // ... más configuraciones
    ],
]
```

### 2. Variables de Entorno

Agrega a tu `.env`:

```env
SEO_SCHEMA_ENABLED=true
SEO_ORGANIZATION_NAME="Mi Empresa"
SEO_ORGANIZATION_URL="https://example.com"
SEO_ORGANIZATION_LOGO="https://example.com/logo.png"
SEO_ORGANIZATION_EMAIL="info@example.com"
SEO_ORGANIZATION_PHONE="+1-555-123-4567"
```

## Uso Básico

### 1. Usar HasStructuredData Trait en Modelos

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Seo\Traits\HasSeo;
use Modules\Seo\Traits\HasStructuredData;

class Post extends Model
{
    use HasSeo, HasStructuredData;

    /**
     * Definir el tipo de schema para este modelo.
     */
    public function getSchemaType(): string
    {
        return 'BlogPosting'; // o 'Article', 'NewsArticle', etc.
    }

    /**
     * Opciones personalizadas para el schema.
     */
    public function getSchemaOptions(): array
    {
        return [
            'author' => $this->author->name ?? 'Admin',
        ];
    }

    /**
     * Breadcrumbs para este modelo.
     */
    public function getBreadcrumbItems(): ?array
    {
        return [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Blog', 'url' => route('blog.index')],
            ['name' => $this->title, 'url' => route('blog.show', $this->slug)],
        ];
    }

    /**
     * FAQs para este modelo (opcional).
     */
    public function getFaqItems(): ?array
    {
        // Retornar null si no hay FAQs
        return null;
    }
}
```

### 2. En las Vistas Blade

#### Método 1: Usando el componente (recomendado)

```blade
<x-seo-tags :model="$post" />
```

El componente automáticamente incluirá los schemas si el modelo usa `HasStructuredData`.

#### Método 2: Render directo en el modelo

```blade
<head>
    {!! $post->renderCompleteSchemaScript() !!}
</head>
```

#### Método 3: Usando SeoService

```blade
@php
    app('seo')->loadSchemasFromModel($post);
@endphp

<head>
    @schemaScript
</head>
```

## Uso del SchemaOrgService

### Generar Schemas Manualmente

```php
use Modules\Seo\Services\SchemaOrgService;

$schemaService = app(SchemaOrgService::class);

// Article Schema
$articleSchema = $schemaService->generateArticleSchema($post, [
    'author' => 'John Doe',
]);

// Organization Schema
$orgSchema = $schemaService->generateOrganizationSchema();

// Breadcrumb Schema
$breadcrumbSchema = $schemaService->generateBreadcrumbSchema([
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'Blog', 'url' => route('blog.index')],
]);

// FAQ Schema
$faqSchema = $schemaService->generateFAQSchema([
    [
        'question' => '¿Qué es Schema.org?',
        'answer' => 'Schema.org es un proyecto colaborativo...',
    ],
]);

// WebPage Schema
$webpageSchema = $schemaService->generateWebPageSchema($page);

// Product Schema
$productSchema = $schemaService->generateProductSchema($product, [
    'brand' => 'Mi Marca',
    'currency' => 'USD',
    'rating' => [
        'value' => 4.5,
        'count' => 120,
    ],
]);

// LocalBusiness Schema
$businessSchema = $schemaService->generateLocalBusinessSchema([
    'type' => 'Restaurant',
    'phone' => '+1-555-123-4567',
    'address' => [
        'street' => '123 Main St',
        'city' => 'Anytown',
        'region' => 'CA',
        'postal_code' => '12345',
        'country' => 'US',
    ],
]);

// Graph Schema (múltiples schemas)
$graphSchema = $schemaService->generateGraphSchema([
    $orgSchema,
    $webpageSchema,
    $breadcrumbSchema,
]);

// Renderizar como script tag
echo $schemaService->renderScriptTag($articleSchema);

// O solo JSON
echo $schemaService->renderJsonLd($articleSchema, $prettyPrint = true);
```

## Uso en Controladores

```php
use Modules\Seo\Facades\Seo;
use Modules\Seo\Services\SchemaOrgService;

class BlogController extends Controller
{
    public function show($slug)
    {
        $post = Post::where('slug', $slug)->firstOrFail();

        // Opción 1: Cargar desde el modelo (si usa HasStructuredData)
        Seo::loadFromModel($post);
        Seo::loadSchemasFromModel($post);

        // Opción 2: Agregar schemas manualmente
        $schemaService = app(SchemaOrgService::class);

        Seo::addSchema($schemaService->generateArticleSchema($post, [
            'author' => $post->author->name,
        ]));

        Seo::addSchema($schemaService->generateBreadcrumbSchema([
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Blog', 'url' => route('blog.index')],
            ['name' => $post->title, 'url' => route('blog.show', $post->slug)],
        ]));

        return view('blog.show', compact('post'));
    }
}
```

## Comando Artisan para Testing

```bash
# Ver ayuda
php artisan seo:generate-schemas --help

# Generar un schema específico
php artisan seo:generate-schemas article
php artisan seo:generate-schemas organization
php artisan seo:generate-schemas breadcrumb
php artisan seo:generate-schemas faq
php artisan seo:generate-schemas webpage
php artisan seo:generate-schemas product
php artisan seo:generate-schemas localbusiness
php artisan seo:generate-schemas graph

# Con formato pretty print
php artisan seo:generate-schemas article --pretty

# Ejecutar tests de todos los schemas
php artisan seo:generate-schemas --test
```

## Tipos de Schema Disponibles

### 1. Article / BlogPosting / NewsArticle
Para contenido editorial (posts, artículos, noticias)

### 2. Organization
Información de la organización/empresa

### 3. BreadcrumbList
Navegación breadcrumb para mejor SEO

### 4. FAQPage
Preguntas frecuentes estructuradas

### 5. WebPage
Página web genérica

### 6. Product
Productos de ecommerce con precios y ratings

### 7. LocalBusiness
Negocios locales con dirección y horarios

### 8. Graph
Múltiples schemas combinados

## Ejemplo Completo

### Modelo

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Seo\Traits\HasSeo;
use Modules\Seo\Traits\HasStructuredData;

class Article extends Model
{
    use HasSeo, HasStructuredData;

    protected $fillable = ['title', 'slug', 'content', 'excerpt', 'author_id'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getSchemaType(): string
    {
        return 'Article';
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
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Articles', 'url' => route('articles.index')],
            ['name' => $this->title, 'url' => route('articles.show', $this->slug)],
        ];
    }
}
```

### Controlador

```php
<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Modules\Seo\Facades\Seo;

class ArticleController extends Controller
{
    public function show($slug)
    {
        $article = Article::with('author')->where('slug', $slug)->firstOrFail();

        // Cargar SEO y schemas desde el modelo
        Seo::loadFromModel($article);
        Seo::loadSchemasFromModel($article);

        return view('articles.show', compact('article'));
    }
}
```

### Vista

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO Meta Tags --}}
    @seoTags

    {{-- Schema.org Structured Data --}}
    @schemaScript
</head>
<body>
    <article>
        <h1>{{ $article->title }}</h1>
        <div>{!! $article->content !!}</div>
    </article>
</body>
</html>
```

O usando el componente:

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Incluye todo: SEO + Schemas --}}
    <x-seo-tags :model="$article" />
</head>
<body>
    <article>
        <h1>{{ $article->title }}</h1>
        <div>{!! $article->content !!}</div>
    </article>
</body>
</html>
```

## Validación

Para validar tus schemas:

1. **Google Rich Results Test**: https://search.google.com/test/rich-results
2. **Schema.org Validator**: https://validator.schema.org/
3. **Comando artisan**: `php artisan seo:generate-schemas --test`

## Buenas Prácticas

1. Siempre incluir `@context` y `@type`
2. Usar URLs absolutas, no relativas
3. Incluir fechas en formato ISO 8601
4. Validar el JSON-LD generado
5. No duplicar información entre schemas
6. Usar el tipo de schema más específico posible
7. Incluir imágenes cuando sea posible
8. Mantener la información consistente con el contenido visible

## Troubleshooting

### Los schemas no se generan

1. Verificar que `SEO_SCHEMA_ENABLED=true` en `.env`
2. Verificar que el modelo usa el trait `HasStructuredData`
3. Limpiar cache de configuración: `php artisan config:clear`

### Errores de validación

1. Verificar que todas las URLs son absolutas
2. Verificar formato de fechas (ISO 8601)
3. Verificar que los campos requeridos estén presentes
4. Usar el comando de test: `php artisan seo:generate-schemas --test`

## Referencia

- Schema.org: https://schema.org/
- JSON-LD: https://json-ld.org/
- Google Search Central: https://developers.google.com/search/docs/appearance/structured-data
