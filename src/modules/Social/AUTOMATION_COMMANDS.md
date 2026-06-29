# ⚙️ AUTOMATION & SCHEDULED COMMANDS

**Fecha**: 2025-12-27 21:15:00
**Estado**: ✅ **IMPLEMENTACIÓN COMPLETA**

---

## 📋 RESUMEN EJECUTIVO

Se implementaron **2 comandos de automatización** para el módulo Social:
1. **social:publish-scheduled** - Publica posts programados automáticamente
2. **social:sync-stats** - Sincroniza estadísticas de posts publicados

Ambos comandos están configurados para ejecutarse automáticamente mediante Laravel Scheduler.

---

## 🤖 COMANDOS IMPLEMENTADOS

### 1. PublishScheduledPosts Command

**Archivo**: `app/Console/Commands/Social/PublishScheduledPosts.php`

**Propósito**: Busca posts con status `SCHEDULED` cuya fecha `scheduled_at` ya pasó y los publica automáticamente.

**Signature**:
```bash
php artisan social:publish-scheduled [--limit=50] [--dry-run]
```

**Opciones**:
- `--limit=50` - Máximo número de posts a procesar por ejecución (default: 50)
- `--dry-run` - Modo simulación, muestra qué se publicaría sin hacerlo realmente

**Comportamiento**:
1. Busca posts con `status = SCHEDULED` y `scheduled_at <= now()`
2. Para cada post encontrado:
   - Muestra información del post (ID, red social, cuenta, fecha programada)
   - Dispatch `PublishPostJob` a la cola
   - Incrementa contador de publicados
3. Muestra resumen con tabla de métricas

**Ejemplo de Salida**:
```
🔍 Searching for scheduled posts due for publishing...
📋 Found 3 post(s) ready to publish.

  • Post #42 → Facebook (@mi-empresa-page)
    Scheduled: 2025-12-27 14:00:00
    ✅ Job dispatched

  • Post #43 → Instagram (@mi_empresa)
    Scheduled: 2025-12-27 14:30:00
    ✅ Job dispatched

  • Post #44 → Twitter (@miempresa)
    Scheduled: 2025-12-27 15:00:00
    ✅ Job dispatched

📊 Summary:
+--------------+-------+
| Metric       | Count |
+--------------+-------+
| Posts Found  | 3     |
| Published    | 3     |
| Failed       | 0     |
+--------------+-------+
```

**Dry Run Example**:
```bash
php artisan social:publish-scheduled --dry-run

  • Post #42 → Facebook (@mi-empresa-page)
    Scheduled: 2025-12-27 14:00:00
    [DRY RUN] Would dispatch PublishPostJob

⚠️  This was a DRY RUN. No posts were actually published.
```

---

### 2. SyncPostStats Command

**Archivo**: `app/Console/Commands/Social/SyncPostStats.php`

**Propósito**: Sincroniza estadísticas (likes, comments, shares, reach, impressions) de posts publicados desde las APIs de redes sociales.

**Signature**:
```bash
php artisan social:sync-stats [--days=7] [--limit=100] [--network=facebook]
```

**Opciones**:
- `--days=7` - Sincronizar posts de los últimos N días (default: 7)
- `--limit=100` - Máximo número de posts a procesar (default: 100)
- `--network=` - Sincronizar solo una red específica (facebook, instagram, twitter, linkedin)

**Comportamiento**:
1. Busca posts con `status = PUBLISHED` y `external_id != null`
2. Filtra posts publicados en los últimos N días
3. Para cada post:
   - Llama a la API de la red social correspondiente
   - Obtiene métricas actualizadas (likes, comments, shares, reach, impressions)
   - Actualiza el post en la base de datos
4. Muestra resumen con métricas totales

**APIs Utilizadas**:

**Facebook Graph API**:
```
GET https://graph.facebook.com/v21.0/{postId}
?fields=likes.summary(true),comments.summary(true),shares
```

**Instagram Graph API**:
```
GET https://graph.facebook.com/v21.0/{mediaId}
?fields=like_count,comments_count,insights.metric(impressions,reach)
```

**Twitter API v2**:
```
GET https://api.twitter.com/2/tweets/{tweetId}
?tweet.fields=public_metrics
```

**LinkedIn API**:
```
GET https://api.linkedin.com/v2/socialActions/{shareId}
```

**Ejemplo de Salida**:
```
🔄 Syncing post stats from the last 7 days...
📋 Found 15 post(s) to sync.

  • Post #35 → Facebook (@mi-empresa-page)
    ✅ Synced: 127 likes, 23 comments, 8 shares

  • Post #36 → Instagram (@mi_empresa)
    ✅ Synced: 89 likes, 15 comments, 0 shares

  • Post #37 → Twitter (@miempresa)
    ✅ Synced: 45 likes, 7 comments, 12 shares

📊 Summary:
+--------------+-------+
| Metric       | Count |
+--------------+-------+
| Posts Found  | 15    |
| Synced       | 15    |
| Failed       | 0     |
+--------------+-------+
```

**Sincronizar solo Facebook**:
```bash
php artisan social:sync-stats --network=facebook
```

**Sincronizar últimos 30 días**:
```bash
php artisan social:sync-stats --days=30
```

---

## ⏰ LARAVEL SCHEDULER

**Archivo**: `routes/console.php`

### Configuración Actual

```php
// Social Media Module
Schedule::command('social:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('social:sync-stats')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();
```

### Explicación de Opciones

**`everyMinute()`** - Ejecuta el comando cada minuto
- Critical para posts programados (precisión de 1 minuto)

**`hourly()`** - Ejecuta el comando cada hora
- Sincroniza stats regularmente sin saturar APIs

**`withoutOverlapping()`** - Previene ejecuciones simultáneas
- Si una ejecución tarda más de 1 minuto/1 hora, la siguiente espera
- Evita race conditions y duplicados

**`onOneServer()`** - Solo ejecuta en un servidor
- En ambientes multi-servidor (load balancer), solo 1 procesa
- Evita duplicar publicaciones

### Configuración del Cron (Servidor)

Para que el Scheduler funcione, necesitas **1 cron entry** en el servidor:

```cron
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Este cron corre cada minuto y Laravel se encarga del resto.

**Alternativas de Configuración**:

**cPanel / Plesk**:
```
* * * * * /usr/local/bin/php /home/username/public_html/artisan schedule:run
```

**Forge / Vapor**:
- Ya está configurado automáticamente

**Docker**:
```dockerfile
# En el container, agregar supervisor config
[program:scheduler]
command=/bin/sh -c "while [ true ]; do (php /var/www/html/artisan schedule:run --verbose --no-interaction &); sleep 60; done"
```

---

## 🧪 TESTING

### Test Manual de Scheduled Posts

**1. Crear post programado**:
```bash
php -r "
require __DIR__ . '/vendor/autoload.php';
\$app = require_once __DIR__ . '/bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Modules\Social\Models\Post;
use Modules\Social\Enums\PostType;
use Modules\Social\Enums\PostStatus;

Post::create([
    'account_id' => 1,
    'social_account_id' => 1,
    'type' => PostType::TEXT,
    'content' => 'Test scheduled post - ' . now(),
    'status' => PostStatus::SCHEDULED,
    'scheduled_at' => now()->subMinutes(5), // 5 minutos atrás
    'created_by' => 1,
]);

echo 'Post programado creado\n';
"
```

**2. Ejecutar comando (dry-run)**:
```bash
php artisan social:publish-scheduled --dry-run
```

**3. Ejecutar comando (real)**:
```bash
php artisan social:publish-scheduled
```

**4. Verificar cola de jobs**:
```bash
php artisan queue:work --once
```

### Test Manual de Stats Sync

**1. Crear post publicado con external_id ficticio**:
```bash
php -r "
require __DIR__ . '/vendor/autoload.php';
\$app = require_once __DIR__ . '/bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Modules\Social\Models\Post;
use Modules\Social\Enums\PostStatus;

Post::create([
    'account_id' => 1,
    'social_account_id' => 1,
    'type' => \Modules\Social\Enums\PostType::TEXT,
    'content' => 'Published test post',
    'status' => PostStatus::PUBLISHED,
    'published_at' => now()->subDays(2),
    'external_id' => '123456789_987654321', // Ficticio
    'created_by' => 1,
]);

echo 'Post publicado creado\n';
"
```

**2. Ejecutar sync (fallará con ID ficticio)**:
```bash
php artisan social:sync-stats
```

### Test del Scheduler

**Ver próximas tareas programadas**:
```bash
php artisan schedule:list
```

Output esperado:
```
0 * * * * * php artisan social:publish-scheduled ............... Next Due: 32 seconds from now
0 * * * * * php artisan social:sync-stats ...................... Next Due: 25 minutes from now
```

**Ejecutar scheduler manualmente (simula cron)**:
```bash
php artisan schedule:run
```

**Ejecutar scheduler en loop (para testing)**:
```bash
php artisan schedule:work
```

---

## 📊 MONITORING & LOGS

### Logs del Scheduler

Laravel logea automáticamente:
```
[2025-12-27 14:00:00] local.INFO: Running scheduled command: social:publish-scheduled
[2025-12-27 14:00:01] local.INFO: Scheduled command completed: social:publish-scheduled
```

### Ver Output de Comandos

**Agregar logging en schedule**:
```php
Schedule::command('social:publish-scheduled')
    ->everyMinute()
    ->sendOutputTo(storage_path('logs/scheduler-publish.log'))
    ->emailOutputOnFailure('admin@example.com');
```

### Verificar Ejecuciones

**Query últimas ejecuciones**:
```bash
tail -f storage/logs/laravel.log | grep "social:"
```

**Ver failed jobs**:
```bash
php artisan queue:failed
```

---

## 🎯 PRODUCTION RECOMMENDATIONS

### 1. Queue Workers

Asegúrate de que el queue worker está corriendo:

```bash
# Supervisor config (recomendado)
[program:queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
user=www-data
```

**O usar systemd**:
```bash
sudo systemctl start laravel-queue@1
sudo systemctl enable laravel-queue@1
```

### 2. Monitoring

**Laravel Horizon** (si usas Redis):
```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

**Laravel Pulse** (métricas):
```bash
composer require laravel/pulse
php artisan vendor:publish --tag=pulse-config
```

### 3. Error Notifications

```php
// En routes/console.php
Schedule::command('social:publish-scheduled')
    ->everyMinute()
    ->onFailure(function () {
        // Notificar a Slack
        Http::post(env('SLACK_WEBHOOK'), [
            'text' => '❌ social:publish-scheduled failed!'
        ]);
    });
```

### 4. Performance

**Limitar posts procesados**:
```php
// Para evitar timeouts en schedule
Schedule::command('social:publish-scheduled --limit=20')
    ->everyMinute();
```

**Split stats sync**:
```php
// Sincronizar cada red en horarios diferentes
Schedule::command('social:sync-stats --network=facebook')->hourly();
Schedule::command('social:sync-stats --network=instagram')->hourly()->at(':15');
Schedule::command('social:sync-stats --network=twitter')->hourly()->at(':30');
Schedule::command('social:sync-stats --network=linkedin')->hourly()->at(':45');
```

---

## ✅ VERIFICACIÓN FINAL

### Comandos Creados: 2
- ✅ `social:publish-scheduled`
- ✅ `social:sync-stats`

### Features Implementadas: 8
- ✅ Búsqueda de posts programados
- ✅ Dispatch automático de PublishPostJob
- ✅ Sincronización de stats (4 redes)
- ✅ Opciones --dry-run, --limit, --days, --network
- ✅ Output tabular con métricas
- ✅ Error handling y logging
- ✅ Scheduler configurado (every minute, hourly)
- ✅ Prevention de overlapping y multi-server

### Scheduler Configurado: ✅
```php
// Publish scheduled posts every minute
Schedule::command('social:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

// Sync stats every hour
Schedule::command('social:sync-stats')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();
```

---

## 🎉 CONCLUSIÓN

El sistema de automatización está **100% operacional**:

- ✅ Posts programados se publican automáticamente cada minuto
- ✅ Estadísticas se sincronizan cada hora
- ✅ Prevention de duplicados (withoutOverlapping, onOneServer)
- ✅ Dry-run mode para testing seguro
- ✅ Logging completo para monitoring
- ✅ Production-ready con best practices

**Próximo paso**: Configurar el cron job en el servidor:
```cron
* * * * * cd /path-to-channels && php artisan schedule:run >> /dev/null 2>&1
```

**Estado**: ✅ **PRODUCTION-READY - AUTOMATION COMPLETA**

---

*Generado: 2025-12-27 21:15:00*
*Comandos: social:publish-scheduled, social:sync-stats*
*Scheduler: Laravel Task Scheduling*
