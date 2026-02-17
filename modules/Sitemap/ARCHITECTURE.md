# Arquitectura del Módulo Sitemap

## Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────────────┐
│                      MÓDULO SITEMAP                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌────────────────┐     ┌──────────────┐     ┌──────────────┐  │
│  │   Commands     │     │  Controller  │     │   Facade     │  │
│  ├────────────────┤     ├──────────────┤     ├──────────────┤  │
│  │ • Generate     │────▶│ • index()    │◀────│ Sitemap::    │  │
│  │ • Ping         │     │ • pages()    │     │   add()      │  │
│  └────────────────┘     │ • posts()    │     │   generate() │  │
│         │                └──────────────┘     └──────────────┘  │
│         │                       │                     │          │
│         └───────────────────────┼─────────────────────┘          │
│                                 │                                │
│                         ┌───────▼────────┐                       │
│                         │ SitemapBuilder │                       │
│                         ├────────────────┤                       │
│                         │ • items[]      │                       │
│                         │ • sitemaps[]   │                       │
│                         │ • add()        │                       │
│                         │ • addModel()   │                       │
│                         │ • render()     │                       │
│                         │ • generate()   │                       │
│                         └────────────────┘                       │
│                                 │                                │
│                    ┌────────────┼────────────┐                  │
│                    │            │            │                   │
│            ┌───────▼──────┐  ┌─▼────────┐  ┌▼──────────┐       │
│            │  HasSitemap  │  │  Helper  │  │   Views   │       │
│            │    Items     │  │          │  │           │       │
│            │   (Trait)    │  │ Validate │  │ • xml     │       │
│            └──────────────┘  │ Format   │  │ • index   │       │
│                    │          │ Escape   │  └───────────┘       │
│                    │          └──────────┘                       │
│                    │                                             │
└────────────────────┼─────────────────────────────────────────────┘
                     │
            ┌────────▼─────────┐
            │   User Models    │
            ├──────────────────┤
            │ • Page           │
            │ • Post           │
            │ • Product        │
            └──────────────────┘
```

---

## Flujo de Datos

### 1. Generación vía Comando

```
┌──────────────┐
│   Terminal   │
└──────┬───────┘
       │ php artisan sitemap:generate
       ▼
┌────────────────────────┐
│ GenerateSitemapCommand │
└──────┬─────────────────┘
       │ 1. clear()
       ▼
┌─────────────────┐
│ SitemapBuilder  │
└──────┬──────────┘
       │ 2. addModel(Page::class)
       ▼
┌────────────────┐
│ HasSitemapItems│ (Trait)
│ getSitemapItems()
└──────┬─────────┘
       │ 3. return published items
       ▼
┌─────────────────┐
│ SitemapBuilder  │
│ add() for each  │
└──────┬──────────┘
       │ 4. generate()
       ▼
┌─────────────────┐
│  View (xml.php) │
│  render XML     │
└──────┬──────────┘
       │ 5. save file
       ▼
┌─────────────────┐
│public/sitemap.xml
└─────────────────┘
```

### 2. Acceso vía Web

```
┌──────────────┐
│   Browser    │
└──────┬───────┘
       │ GET /sitemap.xml
       ▼
┌────────────────────┐
│ SitemapController  │
│ index()            │
└──────┬─────────────┘
       │ Cache::remember()
       ▼
┌─────────────────┐
│  Is cached?     │
└──────┬──────────┘
       │
   ┌───┴────┐
   │        │
  YES      NO
   │        │
   │        ▼
   │  ┌─────────────────┐
   │  │ SitemapBuilder  │
   │  │ • clear()       │
   │  │ • addModel()    │
   │  │ • render()      │
   │  └─────┬───────────┘
   │        │
   │        ▼
   │  ┌─────────────────┐
   │  │  View (xml)     │
   │  │  Generate XML   │
   │  └─────┬───────────┘
   │        │
   │        │ Cache for 24h
   └────────┼────────────┘
            │
            ▼
     ┌──────────────┐
     │ Response XML │
     │ Content-Type:│
     │ text/xml     │
     └──────────────┘
```

### 3. Uso Programático

```
┌────────────────┐
│  Your Code     │
└──────┬─────────┘
       │ use Sitemap facade
       ▼
┌─────────────────┐
│ Sitemap::add()  │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ SitemapBuilder  │
│ $items[]        │
└──────┬──────────┘
       │
       ▼
┌──────────────────┐
│Sitemap::generate()│
└──────┬───────────┘
       │
       ▼
┌─────────────────┐
│public/sitemap.xml
└─────────────────┘
```

---

## Arquitectura de Capas

```
┌─────────────────────────────────────────────┐
│           PRESENTATION LAYER                │
├─────────────────────────────────────────────┤
│ • Routes (web.php)                          │
│ • Controller (SitemapController)            │
│ • Views (xml.blade.php, index.blade.php)    │
│ • Middleware (CacheSitemapResponse)         │
└─────────────────────────────────────────────┘
                     ▼
┌─────────────────────────────────────────────┐
│           APPLICATION LAYER                 │
├─────────────────────────────────────────────┤
│ • Commands (Generate, Ping)                 │
│ • Facade (Sitemap)                          │
│ • Service Provider (SitemapServiceProvider) │
└─────────────────────────────────────────────┘
                     ▼
┌─────────────────────────────────────────────┐
│             DOMAIN LAYER                    │
├─────────────────────────────────────────────┤
│ • Builder (SitemapBuilder)                  │
│ • Helper (SitemapHelper)                    │
│ • Trait (HasSitemapItems)                   │
└─────────────────────────────────────────────┘
                     ▼
┌─────────────────────────────────────────────┐
│          INFRASTRUCTURE LAYER               │
├─────────────────────────────────────────────┤
│ • Cache                                     │
│ • File System                               │
│ • Scheduler                                 │
│ • Config                                    │
└─────────────────────────────────────────────┘
```

---

## Patrones de Diseño Utilizados

### 1. Singleton Pattern
```php
// SitemapServiceProvider.php
$this->app->singleton('sitemap', function ($app) {
    return new SitemapBuilder();
});
```

### 2. Facade Pattern
```php
// Sitemap.php (Facade)
use Modules\Sitemap\Facades\Sitemap;

Sitemap::add(url('/'));
```

### 3. Trait Pattern
```php
// HasSitemapItems.php
trait HasSitemapItems
{
    public static function getSitemapItems() { }
}
```

### 4. Builder Pattern
```php
// SitemapBuilder.php
$sitemap->add()
        ->addModel()
        ->addSitemap()
        ->generate();
```

### 5. Service Provider Pattern
```php
// SitemapServiceProvider.php
public function register() {
    $this->app->singleton('sitemap', ...);
}
```

---

## Ciclo de Vida de una Solicitud

### Request: GET /sitemap.xml

```
1. Route Matching
   routes/web.php → SitemapController@index

2. Middleware Pipeline
   CacheSitemapResponse → check headers

3. Controller Action
   index() → Cache::remember()

4. Cache Check
   If cached:   → return cached XML
   If not:      → continue to step 5

5. Builder Initialization
   app('sitemap') → new SitemapBuilder()

6. Data Collection
   foreach models → getSitemapItems()

7. Item Processing
   foreach item → add(url, lastmod, priority, changefreq)

8. View Rendering
   view('sitemap::formats.xml') → Blade compilation

9. XML Generation
   render() → XML string

10. Cache Storage
    Cache::put('sitemap-xml', $xml, 86400)

11. Response
    Response::make($xml, 200, ['Content-Type' => 'application/xml'])

12. Headers Added
    Middleware → Cache-Control, X-Robots-Tag

13. Send to Browser
    XML displayed/downloaded
```

---

## Dependencias del Módulo

```
SitemapModule
├── Laravel Framework (^10.0|^11.0)
│   ├── Illuminate\Support
│   ├── Illuminate\Console
│   ├── Illuminate\Http
│   ├── Illuminate\View
│   ├── Illuminate\Cache
│   └── Illuminate\Routing
│
├── nwidart/laravel-modules
│   └── Module system
│
└── PHP (^8.1|^8.2|^8.3)
    ├── SimpleXML
    ├── DateTime
    └── Reflection
```

---

## Extensibilidad

### Puntos de Extensión

1. **Nuevos Formatos de Sitemap**
```php
// Crear vista en resources/views/formats/rss.blade.php
$sitemap->render('rss');
```

2. **Personalización del Trait**
```php
trait HasAdvancedSitemap extends HasSitemapItems
{
    public function getSitemapImages() { }
    public function getSitemapVideos() { }
}
```

3. **Eventos Personalizados**
```php
Event::listen(SitemapGenerated::class, function($event) {
    // Notificar, log, etc.
});
```

4. **Comandos Adicionales**
```php
class ValidateSitemapCommand extends Command { }
class CompressSitemapCommand extends Command { }
```

5. **Middleware Personalizado**
```php
class CompressSitemapResponse { }
class RateLimitSitemapRequest { }
```

---

## Performance

### Optimizaciones Implementadas

1. **Cache Layer**
   - Duración: 24 horas
   - Key: sitemap-xml
   - Reducción: 99% de procesamiento

2. **Singleton Pattern**
   - Una instancia por request
   - Memoria compartida

3. **Lazy Loading**
   - Modelos cargados solo cuando se necesitan
   - with() para relaciones

4. **View Compilation**
   - Blade compila una vez
   - Reutiliza el compilado

5. **Chunk Processing**
   - Para sitios grandes (>50k URLs)
   - Split en múltiples sitemaps

### Benchmarks Estimados

```
Sitio pequeño (< 1,000 URLs):
- Primera generación: ~200ms
- Con caché: ~10ms

Sitio mediano (1,000 - 10,000 URLs):
- Primera generación: ~1-2 segundos
- Con caché: ~10ms

Sitio grande (10,000 - 50,000 URLs):
- Primera generación: ~5-10 segundos
- Con caché: ~10ms
```

---

## Seguridad

### Medidas Implementadas

1. **Escapado XML**
```php
SitemapHelper::escapeXml($string)
```

2. **Validación de Input**
```php
SitemapHelper::isValidPriority($priority)
SitemapHelper::isValidChangeFrequency($freq)
```

3. **Headers de Seguridad**
```php
X-Robots-Tag: noindex, nofollow
Cache-Control: public, max-age=86400
```

4. **Rate Limiting** (opcional)
- Implementar middleware
- Limitar requests a sitemap.xml

---

## Testing Strategy

### Unit Tests
- SitemapBuilder methods
- SitemapHelper functions
- Trait methods

### Integration Tests
- Command execution
- Controller responses
- Cache behavior

### Feature Tests
- End-to-end generation
- XML validation
- Route accessibility

---

## Monitoreo y Logging

### Eventos a Monitorear

1. Generación exitosa
2. Errores de generación
3. Cache hits/misses
4. Ping a buscadores
5. Requests al sitemap

### Logs Recomendados

```php
Log::info('Sitemap generated', [
    'items' => $count,
    'time' => $duration,
]);

Log::error('Sitemap generation failed', [
    'error' => $exception->getMessage(),
]);
```

---

## Roadmap

### Versión 1.1
- [ ] Soporte para imágenes
- [ ] Soporte para videos
- [ ] Compresión gzip

### Versión 1.2
- [ ] Multi-idioma
- [ ] Admin panel
- [ ] Analytics integration

### Versión 2.0
- [ ] API REST
- [ ] GraphQL support
- [ ] Cloud storage
