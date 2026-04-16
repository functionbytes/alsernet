# Comandos Artisan

## Comandos de gestion

| Comando | Descripcion | Ejemplo |
|---------|-------------|---------|
| `module:make` | Crear modulo(s) | `php artisan module:make Blog` |
| `module:make` | Crear multiples | `php artisan module:make Blog User Auth` |
| `module:make --plain` | Crear sin recursos | `php artisan module:make Blog -p` |
| `module:list` | Listar modulos | `php artisan module:list` |
| `module:enable` | Habilitar modulo | `php artisan module:enable Blog` |
| `module:disable` | Deshabilitar modulo | `php artisan module:disable Blog` |
| `module:delete` | Eliminar modulo | `php artisan module:delete Blog` |
| `module:use` | Establecer modulo activo (CLI) | `php artisan module:use Blog` |
| `module:unuse` | Desactivar modulo activo | `php artisan module:unuse` |
| `module:update` | Actualizar dependencias | `php artisan module:update Blog` |
| `module:dump` | Dump autoload | `php artisan module:dump` |
| `module:install` | Instalar modulo externo | `php artisan module:install nwidart/hello` |

## Comandos de base de datos

| Comando | Descripcion | Ejemplo |
|---------|-------------|---------|
| `module:migrate` | Ejecutar migraciones | `php artisan module:migrate Blog` |
| `module:migrate-rollback` | Revertir migraciones | `php artisan module:migrate-rollback Blog` |
| `module:migrate-refresh` | Refresh migraciones | `php artisan module:migrate-refresh Blog` |
| `module:migrate-reset` | Reset migraciones | `php artisan module:migrate-reset Blog` |
| `module:migrate:status` | Estado de migraciones | `php artisan module:migrate:status Blog` |
| `module:seed` | Ejecutar seeders | `php artisan module:seed Blog` |

## Comandos generadores (make)

| Comando | Genera | Opciones especiales |
|---------|--------|-------------------|
| `module:make-command` | Comando consola | |
| `module:make-controller` | Controller | |
| `module:make-model` | Model | `--fillable=campo1,campo2` `-m` (crea migracion) |
| `module:make-migration` | Migration | |
| `module:make-seed` | Seeder | |
| `module:make-factory` | Factory | |
| `module:make-request` | Form Request | |
| `module:make-middleware` | Middleware | |
| `module:make-provider` | Service Provider | |
| `module:make-event` | Event | |
| `module:make-listener` | Listener | `--event=EventName` `--queued` |
| `module:make-job` | Job | `--sync` |
| `module:make-mail` | Mailable | |
| `module:make-notification` | Notification | |
| `module:make-policy` | Policy | |
| `module:make-rule` | Validation Rule | |
| `module:make-resource` | API Resource | `--collection` |
| `module:make-test` | Test | |
| `module:make-observer` | Observer | |
| `module:make-scope` | Scope | |
| `module:make-enum` | Enum | |
| `module:make-service` | Service | |
| `module:make-trait` | Trait | |
| `module:make-action` | Action | |
| `module:make-class` | Class | |
| `module:make-interface` | Interface | |
| `module:make-helper` | Helper | |
| `module:make-cast` | Cast | |
| `module:make-channel` | Channel | |
| `module:make-exception` | Exception | |
| `module:make-view` | Blade View | |
| `module:make-component` | Blade Component | |
| `module:route-provider` | Route Provider | |
| `module:make-event-provider` | Event Provider | |

## Comandos de publicacion

| Comando | Que publica |
|---------|------------|
| `module:publish` | Assets publicos |
| `module:publish-config` | Configuracion a `config/` |
| `module:publish-migration` | Migraciones a `database/migrations/` |
| `module:publish-translation` | Traducciones |

## Sintaxis general

```bash
php artisan module:make-{tipo} {Nombre} {Modulo}
```

Ejemplo:
```bash
php artisan module:make-controller PostController Blog
php artisan module:make-model Post Blog --fillable=title,body -m
php artisan module:make-migration create_posts_table Blog
php artisan module:make-request StorePostRequest Blog
php artisan module:make-event PostCreated Blog
php artisan module:make-listener SendNotification Blog --event=PostCreated --queued
php artisan module:make-test PostTest Blog
```
