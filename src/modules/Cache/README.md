# Cache

> Cache configuration settings for admin menu, frontend menu, user avatars and sitemap

## Proposito

Proporciona una interfaz de administracion para configurar y monitorear el sistema de cache de la aplicacion. Permite habilitar/deshabilitar cache por tipo (menu, avatares, sitemap, paginas), consultar estadisticas de Redis en tiempo real y vaciar caches selectivamente sin acceder al servidor.

## Componentes principales

- **Rutas principales**:
  - `GET /panel/settings/cache` — configuracion de cache
  - `POST /panel/settings/cache` — guardar configuracion
  - `GET /panel/settings/cache/monitor` — monitor de cache en tiempo real
  - `GET /panel/settings/cache/stats` — estadisticas de PageCache (JSON, requiere modulo Page)
  - `GET /panel/settings/cache/redis-stats` — estadisticas de Redis (JSON)
  - `POST /panel/settings/cache/flush` — vaciar cache por tipo

- **Controladores**:
  - `CacheSettingsController` — gestion de configuracion y flush de cache

## Permisos (Spatie)

| Permiso | Descripcion |
|---------|-------------|
| `Cache.settings.index` | Ver configuracion de cache |
| `Cache.settings.update` | Actualizar configuracion y vaciar cache |

## Configuracion

Archivo: `config/general.php`

Las preferencias se persisten en la tabla `settings` usando el prefijo `cache.`:

| Clave | Descripcion |
|-------|-------------|
| `cache.admin_menu_enabled` | Habilitar cache del menu de administracion |
| `cache.frontend_menu_enabled` | Habilitar cache del menu publico |
| `cache.user_avatars_enabled` | Habilitar cache de avatares de usuario |
| `cache.sitemap_enabled` | Habilitar cache del sitemap |
| `cache.sitemap_ttl` | TTL del cache del sitemap (segundos) |
| `cache.pages_enabled` | Habilitar cache de paginas (requiere modulo Page) |
| `cache.pages_ttl` | TTL del cache de paginas (segundos) |

## Dependencias

- **Requeridos**: `Modules\Core\Models\Setting`, `Modules\Theme\Services\NavService`, Redis
- **Opcionales**: modulo `Page` para cache de paginas estaticas
