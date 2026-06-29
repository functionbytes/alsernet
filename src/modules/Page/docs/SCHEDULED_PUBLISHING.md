# Programación Automática de Publicación/Despublicación

Este documento describe la funcionalidad de programación automática de publicación y despublicación de páginas implementada en el módulo Page.

## Características

- **Publicación Programada**: Programa una página borrador para que se publique automáticamente en una fecha y hora específica
- **Despublicación Programada**: Programa una página publicada para que se despublique automáticamente en una fecha y hora específica
- **Notificaciones por Email**: Los usuarios reciben notificaciones cuando sus páginas se publican o despublican automáticamente
- **Comando Manual**: Ejecuta la publicación/despublicación programada manualmente cuando sea necesario
- **Ejecución Automática**: Los jobs se ejecutan automáticamente cada hora mediante el scheduler de Laravel

## Campos de Base de Datos

Se agregaron dos nuevos campos a la tabla `pages`:

- `publish_at` (timestamp, nullable): Fecha y hora en que la página se publicará automáticamente
- `unpublish_at` (timestamp, nullable): Fecha y hora en que la página se despublicará automáticamente

## Uso en el Modelo

### Scopes

```php
// Obtener páginas programadas para publicación
$pages = Page::scheduledForPublishing()->get();

// Obtener páginas programadas para despublicación
$pages = Page::scheduledForUnpublishing()->get();
```

### Métodos

```php
$page = Page::find(1);

// Verificar si la página está programada
if ($page->isScheduled()) {
    // La página tiene una fecha de publicación programada
}

// Verificar si la página se publicará en el futuro
if ($page->willBePublished()) {
    echo "Se publicará el: " . $page->publish_at->format('d/m/Y H:i');
}

// Verificar si la página se despublicará en el futuro
if ($page->willBeUnpublished()) {
    echo "Se despublicará el: " . $page->unpublish_at->format('d/m/Y H:i');
}
```

## Uso en el Formulario

En las vistas de creación y edición de páginas (`create.blade.php`, `edit.blade.php`), ahora hay campos para:

1. **Publicar el (Programado)**: Campo datetime-local para programar la publicación
2. **Despublicar el (Programado)**: Campo datetime-local para programar la despublicación

### Validaciones

- `publish_at` debe ser una fecha en el futuro o igual al momento actual
- `unpublish_at` debe ser posterior a `publish_at`
- Ambos campos son opcionales

## Jobs

### PublishScheduledPagesJob

Este job se encarga de:
1. Buscar todas las páginas con estado `draft` y `publish_at <= now()`
2. Cambiar su estado a `published`
3. Establecer `published_at` a la fecha actual
4. Enviar notificación al autor de la página
5. Limpiar el campo `publish_at`

### UnpublishScheduledPagesJob

Este job se encarga de:
1. Buscar todas las páginas con estado `published` y `unpublish_at <= now()`
2. Cambiar su estado a `draft`
3. Enviar notificación al autor de la página
4. Limpiar el campo `unpublish_at`

## Notificaciones

### PagePublishedNotification

Se envía cuando una página se publica automáticamente. Incluye:
- Título de la página
- URL de la página
- Tipo de publicación (manual o programada)

### PageUnpublishedNotification

Se envía cuando una página se despublica automáticamente. Incluye:
- Título de la página
- Enlace al panel de administración
- Tipo de despublicación (manual o programada)

## Comando Artisan

### Sintaxis

```bash
php artisan page:publish-scheduled [--type=TYPE]
```

### Opciones

- `--type=all` (por defecto): Ejecuta tanto publicación como despublicación
- `--type=publish`: Solo ejecuta la publicación de páginas programadas
- `--type=unpublish`: Solo ejecuta la despublicación de páginas programadas

### Ejemplos

```bash
# Ejecutar ambos procesos
php artisan page:publish-scheduled

# Solo publicar páginas programadas
php artisan page:publish-scheduled --type=publish

# Solo despublicar páginas programadas
php artisan page:publish-scheduled --type=unpublish
```

## Programación Automática (Scheduler)

El comando se ejecuta automáticamente cada hora mediante el scheduler de Laravel:

```php
$schedule->command('page:publish-scheduled --type=all')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();
```

### Configuración del Cron

Para que el scheduler funcione, debes agregar esta entrada al crontab:

```bash
* * * * * cd /ruta-del-proyecto && php artisan schedule:run >> /dev/null 2>&1
```

## Lógica de Negocio

### Al Crear/Actualizar una Página

1. Si el estado cambia a `published`, se limpia automáticamente `publish_at`
2. Si el estado cambia a `draft`, se limpia automáticamente `unpublish_at`
3. Esto evita conflictos entre el estado manual y el programado

### En el Listado de Páginas

Las páginas programadas muestran información adicional:
- Si es borrador y tiene `publish_at`: muestra "Se publicará: DD/MM/YYYY HH:MM"
- Si está publicada y tiene `unpublish_at`: muestra "Se despublicará: DD/MM/YYYY HH:MM"

## Registro de Actividad (Logs)

Todos los eventos importantes se registran en el log de Laravel:

- Inicio y finalización de cada job
- Páginas publicadas/despublicadas con éxito
- Errores durante el proceso
- Notificaciones enviadas

Ejemplo de logs:

```
[timestamp] Starting scheduled page publishing job
[timestamp] Found 2 pages scheduled for publishing
[timestamp] Page published automatically - ID: 5, Title: "Nueva Página"
[timestamp] Scheduled page publishing completed - published: 2, failed: 0
```

## Configuración de Cola (Queue)

Los jobs utilizan la cola `pages`:

```php
$this->onQueue('pages');
```

Asegúrate de tener un worker ejecutándose:

```bash
php artisan queue:work --queue=pages
```

O configura Supervisor para mantener los workers activos.

## Casos de Uso

### 1. Publicar una Página en el Futuro

```php
$page = Page::create([
    'title' => 'Black Friday 2026',
    'content' => 'Ofertas especiales...',
    'status' => 'draft',
    'publish_at' => '2026-11-29 00:00:00',
]);
```

### 2. Publicar y Despublicar Automáticamente

```php
$page = Page::create([
    'title' => 'Evento Temporal',
    'content' => 'Evento del 1 al 5 de marzo',
    'status' => 'draft',
    'publish_at' => '2026-03-01 00:00:00',
    'unpublish_at' => '2026-03-05 23:59:59',
]);
```

### 3. Despublicar una Página Publicada

```php
$page = Page::find(1);
$page->unpublish_at = now()->addDays(7);
$page->save();
```

## Solución de Problemas

### Las páginas no se publican automáticamente

1. Verifica que el cron del scheduler esté configurado
2. Verifica que el worker de colas esté ejecutándose
3. Revisa los logs en `storage/logs/laravel.log`
4. Ejecuta manualmente: `php artisan page:publish-scheduled`

### Las notificaciones no se envían

1. Verifica la configuración de email en `.env`
2. Verifica que los usuarios tengan email configurado
3. Revisa los logs para errores de email
4. Prueba el envío de email manualmente

### Error de zona horaria

Asegúrate de que la zona horaria esté correctamente configurada en `config/app.php`:

```php
'timezone' => 'America/Bogota',
```

## Mejoras Futuras

- Dashboard de páginas programadas
- Notificaciones en la aplicación (database)
- Historial de publicaciones programadas
- Cancelación de publicaciones programadas desde la UI
- Vista previa de páginas programadas
- Reportes de publicaciones automáticas

## Testing

Para probar la funcionalidad:

```php
// Crear una página programada para dentro de 1 minuto
$page = Page::create([
    'title' => 'Test Page',
    'status' => 'draft',
    'publish_at' => now()->addMinute(),
]);

// Esperar 1 minuto y ejecutar manualmente
php artisan page:publish-scheduled

// Verificar que la página se publicó
$page->refresh();
echo $page->status; // 'published'
```
