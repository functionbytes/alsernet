# Core

> Core module for generic dashboard, settings, and user management

## Proposito

Modulo base del sistema. Provee el dashboard principal, el modelo `Setting` utilizado por todos los modulos para configuracion persistente, modelos de soporte (paises, idiomas, ubicaciones IP), y helpers de localizacion. Es una dependencia implicita de la mayoria de los modulos.

## Componentes principales

- **Modelos**: `Setting`, `Lang`, `Countrie`, `IpLocation`, `Jobs\Job`
- **Controladores**: `DashboardController`
- **Servicios**: `HttpClientService`, `CircuitBreaker`, `PhoneNumberValidator`, `WhatsAppValidator`
- **Rutas**: No registra rutas propias; el dashboard es montado por otros modulos que apuntan a sus vistas
- **Config**: `config/localization.php`, `config/languages.php`

## Uso del modelo Setting

El modelo `Setting` es compartido entre todos los modulos para guardar configuracion clave-valor en base de datos:

```php
use Modules\Core\Models\Setting;

// Leer
Setting::get('core.app_name');

// Escribir
Setting::set('core.app_name', 'Alsernet');
```

## Localizacion

`config/localization.php` define formatos de fecha, numero y nombre por locale. Se accede mediante el helper `get_localization_config()`:

```php
get_localization_config('date_full', 'ja'); // 'Y年m月d日'
```

## Configuracion

- Archivo: `config/localization.php`, `config/languages.php`
- Variables env relevantes:
  - `APP_DEFAULT_LOGO_LIGHT`
  - `APP_DEFAULT_LOGO_DARK`
  - `APP_PROFILE`
  - `APP_DRYRUN`
  - `APP_JAPAN`
  - `LICENSE_VALIDATION_ENDPOINT`

## Permisos

Este modulo no define permisos propios via seeder. Los permisos de acceso al dashboard son gestionados por el modulo **Auth** y **Role**.

## Dependencias

- **Core**: Es el modulo core — otros modulos dependen de el
- Otros: Ninguna dependencia de modulo propia
