# Guía de Instalación - Módulo Sitemap

## Paso 1: Verificar la instalación del módulo

El módulo ya ha sido generado. Verifica que existe:

```bash
ls -la modules/Sitemap
```

## Paso 2: Habilitar el módulo

Si usas `nwidart/laravel-modules`, asegúrate de que el módulo esté habilitado:

```bash
php artisan module:enable Sitemap
```

## Paso 3: Publicar las configuraciones (opcional)

```bash
php artisan vendor:publish --tag=config --provider="Modules\Sitemap\Providers\SitemapServiceProvider"
```

Esto copiará el archivo de configuración a `config/sitemap.php`.

## Paso 4: Configurar los modelos

Edita `modules/Sitemap/config/config.php` y agrega los modelos que quieres incluir en el sitemap:

```php
'models' => [
    \Modules\Page\Models\Page::class,
    // \Modules\Post\Models\Post::class,
    // \Modules\Product\Models\Product::class,
],
```

## Paso 5: Agregar el Trait a tus modelos

### Ejemplo con el modelo Page

```php
<?php

namespace Modules\Page\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Sitemap\Traits\HasSitemapItems;

class Page extends Model
{
    use HasSitemapItems;

    // Opcional: Personalizar la URL
    public function getUrlAttribute(): string
    {
        return route('pages.show', $this->slug);
    }
}
```

### Ejemplo con el modelo Post

```php
<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Sitemap\Traits\HasSitemapItems;

class Post extends Model
{
    use HasSitemapItems;

    public function getUrlAttribute(): string
    {
        return route('posts.show', $this->slug);
    }

    public function getSitemapPriorityAttribute(): string
    {
        return $this->is_featured ? '0.9' : '0.7';
    }
}
```

## Paso 6: Generar el sitemap

```bash
php artisan sitemap:generate
```

Esto creará el archivo `public/sitemap.xml`.

## Paso 7: Verificar el sitemap

### En el navegador

Visita: `http://localhost/sitemap.xml`

### Desde la terminal

```bash
cat public/sitemap.xml
```

### Validar el XML

```bash
xmllint --noout public/sitemap.xml
```

## Paso 8: Configurar el robots.txt

Edita o crea `public/robots.txt`:

```
User-agent: *
Allow: /

Sitemap: https://tudominio.com/sitemap.xml
```

## Paso 9: Configurar el Task Scheduler (opcional)

El módulo ya tiene configurado el scheduler para regenerar el sitemap diariamente a las 2:00 AM.

Para que funcione, asegúrate de tener el cron job configurado:

```bash
* * * * * cd /ruta-a-tu-proyecto && php artisan schedule:run >> /dev/null 2>&1
```

O ejecuta manualmente:

```bash
php artisan schedule:work
```

## Paso 10: Configurar caché (opcional)

El módulo usa caché por defecto. Para deshabilitarlo:

```php
// modules/Sitemap/config/config.php

'cache_enabled' => false,
```

## Verificación de la instalación

### Test 1: Verificar rutas

```bash
php artisan route:list | grep sitemap
```

Deberías ver:

```
GET|HEAD  sitemap.xml .................. sitemap.index
GET|HEAD  sitemap-pages.xml ............ sitemap.pages
GET|HEAD  sitemap-posts.xml ............ sitemap.posts
GET|HEAD  sitemap-index.xml ............ sitemap.sitemap-index
```

### Test 2: Verificar comandos

```bash
php artisan list | grep sitemap
```

Deberías ver:

```
sitemap:generate    Generate sitemap.xml
sitemap:ping        Ping search engines about sitemap updates
```

### Test 3: Verificar el facade

```bash
php artisan tinker
```

```php
use Modules\Sitemap\Facades\Sitemap;

Sitemap::clear();
Sitemap::add(url('/'), now()->toAtomString(), '1.0', 'daily');
Sitemap::getItems();
```

### Test 4: Generar y verificar

```bash
php artisan sitemap:generate
curl http://localhost/sitemap.xml
```

## Configuración avanzada

### Cambiar la frecuencia del scheduler

Edita `modules/Sitemap/app/Providers/SitemapServiceProvider.php`:

```php
protected function registerCommandSchedules(): void
{
    $this->app->booted(function () {
        $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

        // Opciones:
        $schedule->command('sitemap:generate')->hourly();
        $schedule->command('sitemap:generate')->daily();
        $schedule->command('sitemap:generate')->weekly();
        $schedule->command('sitemap:generate')->everyFiveMinutes();
    });
}
```

### Regenerar automáticamente con eventos

```php
// app/Providers/EventServiceProvider.php

use Modules\Page\Events\PagePublished;
use Illuminate\Support\Facades\Artisan;

protected $listen = [
    PagePublished::class => [
        function ($event) {
            Artisan::call('sitemap:generate');
        },
    ],
];
```

### Enviar notificación después de generar

```php
// app/Console/Commands/GenerateSitemapAndNotify.php

public function handle()
{
    $this->call('sitemap:generate');
    $this->call('sitemap:ping');

    // Enviar email
    Mail::to('settings@example.com')->send(new SitemapGenerated());
}
```

## Troubleshooting

### Error: "Class 'sitemap' not found"

```bash
php artisan config:clear
php artisan cache:clear
composer dump-autoload
```

### Error: "Unable to write to public/sitemap.xml"

```bash
chmod 775 public/
chown www-data:www-data public/
```

### El sitemap está vacío

1. Verifica que los modelos tengan el trait `HasSitemapItems`
2. Verifica que haya registros publicados
3. Verifica la configuración en `config/sitemap.php`

```bash
php artisan tinker
```

```php
\Modules\Page\Models\Page::getSitemapItems()->count();
```

### El sitemap no se actualiza

Limpia el caché:

```bash
php artisan cache:forget sitemap-xml
php artisan sitemap:generate
```

## Integración con Google Search Console

1. Ve a https://search.google.com/search-console
2. Selecciona tu propiedad
3. Ve a "Sitemaps"
4. Agrega: `https://tudominio.com/sitemap.xml`
5. Haz clic en "Enviar"

## Siguientes pasos

1. Lee `EXAMPLES.md` para ver ejemplos de uso avanzado
2. Lee `README.md` para documentación completa
3. Ejecuta `php artisan test` para verificar que todo funciona
4. Configura el monitoring del sitemap en Google Search Console

## Comandos útiles

```bash
# Generar sitemap
php artisan sitemap:generate

# Ping a buscadores
php artisan sitemap:ping

# Ver rutas
php artisan route:list | grep sitemap

# Limpiar caché
php artisan cache:forget sitemap-xml

# Ver el sitemap
cat public/sitemap.xml

# Contar URLs en el sitemap
grep -c "<loc>" public/sitemap.xml
```
