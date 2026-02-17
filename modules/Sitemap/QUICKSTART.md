# Quick Start - Módulo Sitemap

Guía rápida para empezar a usar el módulo Sitemap en 5 minutos.

## 1. Habilitar el módulo

```bash
php artisan module:enable Sitemap
```

## 2. Agregar el Trait a tu modelo

Edita tu modelo (ejemplo: `modules/Page/Models/Page.php`):

```php
<?php

namespace Modules\Page\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Sitemap\Traits\HasSitemapItems;

class Page extends Model
{
    use HasSitemapItems;

    // Define cómo obtener la URL
    public function getUrlAttribute(): string
    {
        return route('pages.show', $this->slug);
    }
}
```

## 3. Configurar los modelos

Edita `modules/Sitemap/config/config.php`:

```php
'models' => [
    \Modules\Page\Models\Page::class,
],
```

## 4. Generar el sitemap

```bash
php artisan sitemap:generate
```

## 5. Verificar

Visita en tu navegador:
```
http://localhost/sitemap.xml
```

O desde la terminal:
```bash
cat public/sitemap.xml
```

## 6. Configurar robots.txt

Edita o crea `public/robots.txt`:

```
User-agent: *
Allow: /

Sitemap: https://tudominio.com/sitemap.xml
```

## ¡Listo!

Tu sitemap está generado y será regenerado automáticamente cada día a las 2:00 AM.

---

## Comandos útiles

```bash
# Generar sitemap
php artisan sitemap:generate

# Notificar a buscadores
php artisan sitemap:ping

# Ver rutas disponibles
php artisan route:list | grep sitemap

# Limpiar caché
php artisan cache:forget sitemap-xml

# Ver el sitemap
curl http://localhost/sitemap.xml

# Contar URLs
grep -c "<loc>" public/sitemap.xml
```

---

## Personalización básica

### Cambiar prioridad de un modelo

```php
public function getSitemapPriorityAttribute(): string
{
    return $this->is_featured ? '0.9' : '0.7';
}
```

### Cambiar frecuencia de actualización

```php
public function getSitemapChangefreqAttribute(): string
{
    return 'weekly';
}
```

### Filtrar items del sitemap

```php
public static function getSitemapItems()
{
    return static::where('status', 'published')
        ->where('is_active', true)
        ->orderBy('updated_at', 'desc')
        ->get();
}
```

---

## Rutas disponibles

```
/sitemap.xml          - Sitemap principal (todos los items)
/sitemap-pages.xml    - Solo páginas
/sitemap-posts.xml    - Solo posts
/sitemap-index.xml    - Índice de sitemaps
```

---

## Uso programático

```php
use Modules\Sitemap\Facades\Sitemap;

// Agregar URL manualmente
Sitemap::clear();
Sitemap::add(url('/'), now()->toAtomString(), '1.0', 'daily');
Sitemap::add(url('/about'), null, '0.8', 'monthly');

// Agregar modelo completo
Sitemap::addModel(\Modules\Page\Models\Page::class);

// Generar archivo
Sitemap::generate();

// O renderizar directamente
$xml = Sitemap::render();
```

---

## Troubleshooting rápido

### El sitemap está vacío
```bash
# Verifica que hay registros publicados
php artisan tinker
>>> \Modules\Page\Models\Page::where('status', 'published')->count()
```

### El sitemap no se actualiza
```bash
# Limpia caché y regenera
php artisan cache:forget sitemap-xml
php artisan sitemap:generate
```

### Error de permisos
```bash
# Da permisos de escritura
chmod 775 public/
```

---

## Próximos pasos

1. Lee `README.md` para documentación completa
2. Lee `EXAMPLES.md` para casos de uso avanzados
3. Lee `INSTALLATION.md` para configuración detallada
4. Configura Google Search Console para monitorear el sitemap

---

## Soporte

Si tienes problemas:
1. Revisa los logs: `storage/logs/laravel.log`
2. Verifica la configuración: `modules/Sitemap/config/config.php`
3. Ejecuta los tests: `php artisan test --filter SitemapTest`
4. Lee la documentación completa en `README.md`
