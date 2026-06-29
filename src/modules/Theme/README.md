# Theme

> Theme and navigation management services

## Proposito

Modulo de infraestructura que provee el servicio de navegacion (`NavService`) utilizado por todos los modulos para registrar sus items en el sidebar, mini-nav y menu de settings. Tambien expone assets del tema (CSS, JS, fuentes) via una ruta dedicada sin necesidad de publicarlos al directorio `public/`.

## Componentes principales

- **Modelos**: Ninguno
- **Servicios**: `NavService` — registro centralizado de menus
- **Providers**: `ThemeServiceProvider`, `MenuServiceProvider`
- **Rutas**: `GET /theme-asset/{path}` — sirve assets del tema directamente desde `modules/Theme/public/theme/`

## NavService

El `NavService` es el punto central de registro de navegacion. Cada modulo llama a estos metodos desde su `boot()`:

```php
use Modules\Theme\Services\NavService;

// Item en mini-nav (sidebar izquierdo, icono con tooltip)
NavService::registerMiniItem('mi-modulo', [
    'icon'       => 'fas fa-box',
    'tooltip'    => 'Mi Modulo',
    'sidebar_id' => 'mi-modulo',
    'order'      => 50,
]);

// Sidebar principal
NavService::registerSidebar('mi-modulo', [
    'title' => 'Mi Modulo',
    'items' => [
        ['label' => 'Listado', 'route' => 'mi-modulo.index'],
    ],
]);

// Seccion en sidebar de settings
NavService::registerSidebar('settings', [
    'title' => 'Mi Modulo',
    'items' => [
        ['label' => 'Configuracion', 'route' => 'settings.mi-modulo.index'],
    ],
]);
```

## Permisos

No define permisos propios. Los items de nav filtran visibilidad via el permiso `modules.view.{alias}` que cada modulo debe tener registrado.

## Configuracion

- Archivo: `config/custom.php` — formatos de fecha/hora, logos, opciones de branding
- Variables env relevantes:
  - `APP_DEFAULT_LOGO_LIGHT`
  - `APP_DEFAULT_LOGO_DARK`

## Dependencias

- **Core**: Si (comparte configuracion de fecha/hora)
- Otros: Es una dependencia de todos los modulos que registran navegacion
