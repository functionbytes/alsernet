# Page Cache System - Documentación Completa

## 📋 Descripción General

Sistema avanzado de caching para páginas del módulo Page con soporte para:
- ✅ Estadísticas de caché (hit/miss ratio)
- ✅ Cache warming automático
- ✅ Múltiples locales/idiomas
- ✅ Auditoría de caché
- ✅ Whitelist/Blacklist
- ✅ Invalidación inteligente con tags
- ✅ Dashboard en tiempo real
- ✅ API para gestión remota
- ✅ Comandos Artisan

---

## 🚀 Características Implementadas

### 1. **PageCacheService Mejorado**
```php
use Modules\Page\Services\PageCacheService;

// Cachear una página
PageCacheService::set($page);

// Cachear para idioma específico
PageCacheService::set($page, 'en');

// Obtener página cacheada
$page = PageCacheService::get('about-us');

// Warm cache para todas las locales
PageCacheService::warm($page);

// Warm páginas populares
PageCacheService::warmPopular(10);

// Limpiar caché
PageCacheService::forget('about-us');
PageCacheService::forgetAllLocales('about-us');
PageCacheService::flushAll();

// Obtener estadísticas
$stats = PageCacheService::getStats();
```

### 2. **Comandos Artisan**
```bash
# Precachear páginas populares
php artisan page:cache-warm

# Precachear con límite personalizado
php artisan page:cache-warm --limit=20

# Precachear TODAS las páginas
php artisan page:cache-warm --all

# Ver estadísticas
php artisan page:cache-stats

# Limpiar caché
php artisan page:cache-clear

# Limpiar caché y estadísticas
php artisan page:cache-clear --stats
```

### 3. **API REST**
```bash
# Obtener estadísticas
GET /api/v1/cache/stats

# Warm cache
POST /api/v1/cache/warm

# Limpiar caché
POST /api/v1/cache/clear

# Ver auditoría
GET /api/v1/cache/audits
```

### 4. **Dashboard en Vivo**
Accede en: `/pages/cache/dashboard`

Muestra:
- Hit/Miss Ratio en tiempo real
- Número de páginas cacheadas
- Tamaño total del caché
- Últimas acciones (auditoría)
- Botones para warm/clear

### 5. **Auditoría Completa**
Se registran:
- Acciones de caché (cached, cleared, warmed, etc.)
- Usuario que realizó la acción
- Slug de página
- Timestamp

Tabla: `page_cache_audits`

### 6. **Soporte Multi-locale**
```php
// Configurar locales soportados
// .env
PAGE_SUPPORTED_LOCALES=es,en,fr,pt

// Cachear automáticamente por locale actual
PageCacheService::set($page); // usa app()->getLocale()

// O especificar locale
PageCacheService::set($page, 'en');
```

### 7. **Whitelist/Blacklist de Páginas**
Tabla: `page_cache_configs`

```php
// Desactivar caché para una página
$config = PageCacheConfig::create([
    'page_id' => 1,
    'cache_enabled' => false,
]);
```

### 8. **Cache Tags para Invalidación Inteligente**
```php
// El caché se organiza por tags
Cache::tags(['pages', 'pages:123'])->put($key, $data, $ttl);

// Invalidar todas las páginas
Cache::tags(['pages'])->flush();

// Invalidar página específica
Cache::tags(['pages:123'])->flush();
```

### 9. **Estadísticas en Tiempo Real**
```php
$stats = PageCacheService::getStats();

// Retorna:
[
    'enabled' => true,
    'ttl_minutes' => 1440,
    'total_hits' => 1523,
    'total_misses' => 42,
    'total_requests' => 1565,
    'hit_ratio' => 97.32,
    'size_bytes' => 5242880,
    'cached_pages_count' => 145,
    'last_cleared' => '2026-02-24 10:30:45',
    'last_warmed' => '2026-02-24 09:15:20',
]
```

---

## ⚙️ Configuración

### .env
```bash
PAGE_CACHE_ENABLED=true
PAGE_CACHE_TTL=1440
PAGE_SUPPORTED_LOCALES=es,en
CACHE_DRIVER=redis
```

### config/page.php
```php
return [
    'cache' => [
        'enabled' => env('PAGE_CACHE_ENABLED', true),
        'ttl_minutes' => env('PAGE_CACHE_TTL', 1440),
        'warm_on_publish' => true,
        'tags' => ['pages'],
    ],
    'supported_locales' => explode(',', env('PAGE_SUPPORTED_LOCALES', 'es,en')),
];
```

---

## 📊 Flujo de Operación

### Acceso a Página
```
1. Solicitud GET a página pública
2. Middleware/Controlador intenta obtener de caché
3. Si existe → Retorna desde caché + registra HIT
4. Si no existe → Obtiene de BD + registra MISS
5. Cachea para próximas solicitudes
6. Retorna respuesta
```

### Actualización de Página
```
1. Admin actualiza página
2. Observer PageObserver::updated() se dispara
3. Limpia caché (todas las locales)
4. Si warm_on_save=true → Warm automáticamente
5. Registra en auditoría
```

### Eliminación de Página
```
1. Admin elimina página
2. Observer PageObserver::deleted() se dispara
3. Limpia caché
4. Registra eliminación
```

---

## 🔒 Seguridad

- ✅ Autenticación requerida para dashboard y API
- ✅ Autorización con policies
- ✅ No cachea contenido de usuarios autenticados
- ✅ Validación de roles en caché
- ✅ Auditoría de todas las acciones
- ✅ Logs de errores

---

## 📈 Performance

- **Redis Tags**: Invalidación rápida sin scan de keys
- **Locale Separation**: Cache separado por idioma
- **Statistics**: Mínimo overhead (solo incrementos en Redis)
- **Lazy Loading**: Solo cachea contenido necesario
- **Compression**: Compatible con Redis compression

---

## 🛠️ Mantenimiento

### Limpiar cache periódicamente
```bash
# Scheduler (app/Console/Kernel.php)
$schedule->command('page:cache-clear')->daily();

// O manual
Schedule::command('page:cache-clear')->daily()->at('02:00');
```

### Warm cache en horario de baja actividad
```bash
# Warming automático cada 6 horas
$schedule->command('page:cache-warm --all')->everyFourHours();
```

### Monitorear estadísticas
```bash
# Verificar hit ratio
php artisan page:cache-stats

# Ver auditoría de cambios
php artisan tinker
>>> DB::table('page_cache_audits')->latest()->limit(20)->get();
```

---

## 📦 Migraciones

Ejecutar:
```bash
php artisan migrate
```

Crea tablas:
- `page_cache_audits` - Auditoría de caché
- `page_cache_configs` - Configuración por página

---

## 🔄 Integración Existente

### PublicController
```php
// Obtiene del caché primero
$cachedPage = PageCacheService::get($slug);
if ($cachedPage) {
    $page = (object) $cachedPage;
} else {
    $page = Page::where('slug', $slug)->firstOrFail();
    PageCacheService::set($page);
}
```

### PageObserver
```php
// Limpia caché al actualizar
public function updated(Page $page): void
{
    PageCacheService::forget($page->slug);
    if ($page->wasChanged('slug')) {
        PageCacheService::forget($page->getOriginal('slug'));
    }
}
```

---

## 🚨 Troubleshooting

### Cache no se carga
1. Verificar que caché esté habilitado: `page:cache-stats`
2. Verificar Redis está corriendo
3. Verificar TTL > 0

### Página no se actualiza
1. Verificar que observer esté registrado
2. Limpiar caché: `page:cache-clear`
3. Check logs: `tail storage/logs/laravel.log`

### Problemas de performance
1. Monitorear hit ratio
2. Aumentar TTL si es bajo
3. Warm cache para páginas populares

---

## 📞 Soporte

Para problemas o mejoras, contactar al equipo de desarrollo.
