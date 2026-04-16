# nwidart/laravel-modules - Introduccion

## Que es

Paquete para organizar aplicaciones Laravel en modulos independientes. Cada modulo tiene su propia estructura MVC (controllers, models, views, routes, migrations, etc.) y puede habilitarse/deshabilitarse.

## Version en este proyecto

- **Paquete**: nwidart/laravel-modules v12.0
- **Namespace**: `Modules\` (configurado en `config/modules.php`)
- **Directorio**: `modules/` (no `Modules/` por defecto)
- **Activador**: `FileActivator` (lee `modules_statuses.json`)

## Instalacion

```bash
composer require nwidart/laravel-modules
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"
```

Autoloading PSR-4 en `composer.json` raiz:
```json
{
  "autoload": {
    "psr-4": {
      "Modules\\": "modules/"
    }
  }
}
```

Ejecutar `composer dump-autoload` despues de cambios.

## Convenciones de nombre

- **StudlyCase** para nombres de modulo: `Blog`, `UserManagement`
- El modulo se carga via PSR-4, por eso StudlyCase es obligatorio
- Alias (lowercase): se usa para rutas, vistas, config

## Estructura basica generada

```
modules/Blog/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   └── Providers/
│       └── BlogServiceProvider.php
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── views/
│   └── lang/
├── routes/
│   ├── web.php
│   └── api.php
├── tests/
├── composer.json
├── module.json
└── package.json
```

## Modulos en este proyecto (40 total)

### Habilitados (32)
Activity, Analytics, Attention, Auth, Backup, Blog, Cache, Captcha, Cookie, Core, Database, Forms, Health, Helpdesk, Locales, Mailer, Mailrelay, MailsSettings, Media, Modules, Newsletter, Notification, Optimize, Page, Queue, Reviews, Reverb, Role, Seo, Shortcode, Sitemap, Storage, System, Template, Theme, User

### Deshabilitados (8)
Campaign, Mailing, Menu, Pulse, Slug, Subscriber, Webhook, Widget

### Criticos (siempre cargan)
Core, Auth, Role, Theme, Modules
