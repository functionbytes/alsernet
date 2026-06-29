# 🚀 DEPLOYMENT GUIDE - Social Media Module

**Versión**: 1.0
**Fecha**: 2025-12-27
**Estado**: Production-Ready

---

## 📋 TABLA DE CONTENIDOS

1. [Requisitos Previos](#requisitos-previos)
2. [Configuración de Aplicaciones en Redes Sociales](#configuración-de-aplicaciones)
3. [Variables de Entorno](#variables-de-entorno)
4. [Migraciones de Base de Datos](#migraciones)
5. [Configuración de Queue Workers](#queue-workers)
6. [Configuración del Scheduler (Cron)](#scheduler)
7. [Webhooks Configuration](#webhooks)
8. [OAuth Setup](#oauth-setup)
9. [Testing en Producción](#testing)
10. [Monitoring & Logs](#monitoring)
11. [Troubleshooting](#troubleshooting)

---

## 🎯 REQUISITOS PREVIOS

### Software Requirements
- **PHP**: 8.4+
- **Laravel**: 12.x
- **MySQL/MariaDB**: 8.0+
- **Redis**: 6.0+ (para queues y cache)
- **Composer**: 2.x
- **Node.js**: 18+ (para assets)
- **Supervisor** o **systemd** (para queue workers)

### Cuentas Requeridas

Necesitarás crear aplicaciones en las siguientes plataformas:

- ✅ **Facebook Developer Account** → https://developers.facebook.com
- ✅ **Twitter Developer Account** → https://developer.twitter.com
- ✅ **LinkedIn Developer Account** → https://www.linkedin.com/developers
- ✅ **Instagram Business Account** (conectado vía Facebook)

---

## 🔧 CONFIGURACIÓN DE APLICACIONES

### 1. Facebook / Instagram App

**Crear App en Facebook**:

1. Ir a https://developers.facebook.com/apps
2. Click "Create App" → "Business" type
3. Configurar:
   - **App Name**: "Channels Social Module" (o tu nombre)
   - **App Contact Email**: tu@email.com

**Configurar Productos**:

4. Agregar productos:
   - ✅ **Facebook Login**
   - ✅ **Instagram Basic Display** (para Instagram)

**Configuración de OAuth**:

5. En "Facebook Login" → Settings:
   ```
   Valid OAuth Redirect URIs:
   https://tudominio.com/admin/social/oauth/facebook/callback
   ```

6. En "Basic Settings":
   ```
   App Domains: tudominio.com
   Privacy Policy URL: https://tudominio.com/privacy
   Terms of Service URL: https://tudominio.com/terms
   ```

**Permisos Requeridos** (App Review):

- `pages_show_list` - Ver páginas del usuario
- `pages_read_engagement` - Leer engagement de páginas
- `pages_manage_posts` - Publicar en páginas
- `pages_manage_metadata` - Gestionar metadata de páginas
- `instagram_basic` - Acceso básico a Instagram
- `instagram_content_publish` - Publicar en Instagram

**Webhooks Setup**:

7. En "Webhooks" → "Page":
   ```
   Callback URL: https://tudominio.com/webhooks/social/facebook
   Verify Token: [genera un token aleatorio y guárdalo]
   ```

8. Subscribe to:
   - ✅ `feed` (posts en página)
   - ✅ `comments` (comentarios)
   - ✅ `reactions` (likes/reactions)
   - ✅ `messages` (mensajes de inbox)

---

### 2. Twitter App

**Crear App en Twitter**:

1. Ir a https://developer.twitter.com/en/portal/dashboard
2. Click "Create Project" → "Create App"
3. Configurar:
   - **App Name**: "Channels Social"
   - **Environment**: Production

**Configurar OAuth 2.0**:

4. En "Settings" → "User authentication settings":
   ```
   App permissions: Read and write
   Type of App: Web App

   Callback URI / Redirect URL:
   https://tudominio.com/admin/social/oauth/twitter/callback

   Website URL:
   https://tudominio.com
   ```

**API Keys**:

5. Guardar:
   - ✅ API Key (Consumer Key)
   - ✅ API Secret Key (Consumer Secret)
   - ✅ Bearer Token
   - ✅ Access Token
   - ✅ Access Token Secret

**Webhooks (Twitter Premium)**:

⚠️ **Nota**: Twitter webhooks requieren Twitter API Premium/Enterprise

6. En "Dev environments":
   ```
   Account Activity API environment
   Webhook URL: https://tudominio.com/webhooks/social/twitter
   ```

---

### 3. LinkedIn App

**Crear App en LinkedIn**:

1. Ir a https://www.linkedin.com/developers/apps
2. Click "Create app"
3. Configurar:
   - **App Name**: "Channels Social"
   - **LinkedIn Page**: [tu página de empresa]
   - **App Logo**: [subir logo]

**Productos**:

4. Solicitar acceso a:
   - ✅ **Sign In with LinkedIn** (automático)
   - ✅ **Share on LinkedIn** (requiere review)
   - ✅ **Marketing Developer Platform** (requiere review)

**OAuth 2.0 Settings**:

5. En "Auth" tab:
   ```
   Redirect URLs:
   https://tudominio.com/admin/social/oauth/linkedin/callback
   ```

**Scopes Requeridos**:

- `r_liteprofile` - Perfil básico
- `r_emailaddress` - Email
- `w_member_social` - Publicar como miembro
- `w_organization_social` - Publicar como organización

---

## 🔐 VARIABLES DE ENTORNO

Agregar en `.env`:

```bash
# ==============================================
# SOCIAL MEDIA MODULE CONFIG
# ==============================================

# Facebook / Instagram
FACEBOOK_APP_ID=your_facebook_app_id_here
FACEBOOK_APP_SECRET=your_facebook_app_secret_here
FACEBOOK_GRAPH_VERSION=v21.0
FACEBOOK_WEBHOOK_SECRET=your_random_webhook_secret_here
FACEBOOK_WEBHOOK_VERIFY_TOKEN=your_random_verify_token_here

# Twitter
TWITTER_CONSUMER_KEY=your_twitter_consumer_key_here
TWITTER_CONSUMER_SECRET=your_twitter_consumer_secret_here
TWITTER_ACCESS_TOKEN=your_twitter_access_token_here
TWITTER_ACCESS_TOKEN_SECRET=your_twitter_access_token_secret_here
TWITTER_BEARER_TOKEN=your_twitter_bearer_token_here
TWITTER_WEBHOOK_SECRET=your_random_webhook_secret_here

# LinkedIn
LINKEDIN_CLIENT_ID=your_linkedin_client_id_here
LINKEDIN_CLIENT_SECRET=your_linkedin_client_secret_here
LINKEDIN_WEBHOOK_SECRET=your_random_webhook_secret_here

# Queue Configuration (IMPORTANTE!)
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**Generar Webhook Secrets**:

```bash
# Genera tokens aleatorios para webhooks
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

Ejecutar 3 veces y usar output para:
- `FACEBOOK_WEBHOOK_SECRET`
- `FACEBOOK_WEBHOOK_VERIFY_TOKEN`
- `TWITTER_WEBHOOK_SECRET`
- `LINKEDIN_WEBHOOK_SECRET`

---

## 💾 MIGRACIONES

### 1. Ejecutar Migraciones

```bash
# Ejecutar migraciones del módulo Social
php artisan migrate --path=Modules/Social/database/migrations

# Verificar que se crearon las tablas
php artisan db:table social_posts
php artisan db:table social_accounts
```

### 2. Verificar Columnas Críticas

```bash
# Verificar que social_posts tiene external_id
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\$columns = DB::select('SHOW COLUMNS FROM social_posts');
foreach (\$columns as \$column) {
    if (in_array(\$column->Field, ['external_id', 'external_url', 'reach', 'impressions'])) {
        echo \"✅ {\$column->Field}\n\";
    }
}
"
```

Expected output:
```
✅ external_id
✅ external_url
✅ reach
✅ impressions
```

---

## ⚙️ QUEUE WORKERS

### Opción 1: Supervisor (Recomendado)

**Crear configuración**:

```bash
sudo nano /etc/supervisor/conf.d/channels-queue.conf
```

```ini
[program:channels-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=900
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/queue-worker.log
stopwaitsecs=3600
```

**Iniciar**:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start channels-queue-worker:*
```

**Verificar estado**:

```bash
sudo supervisorctl status channels-queue-worker:*
```

### Opción 2: systemd

**Crear service**:

```bash
sudo nano /etc/systemd/system/channels-queue@.service
```

```ini
[Unit]
Description=Channels Queue Worker %i
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html
ExecStart=/usr/bin/php /var/www/html/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

**Iniciar múltiples workers**:

```bash
sudo systemctl enable channels-queue@{1..4}
sudo systemctl start channels-queue@{1..4}
sudo systemctl status channels-queue@*
```

---

## ⏰ SCHEDULER (CRON)

### Configurar Cron Job

**Editar crontab**:

```bash
crontab -e
```

**Agregar línea**:

```cron
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

### Verificar Tareas Programadas

```bash
php artisan schedule:list
```

Expected output:
```
0 * * * *  php artisan social:publish-scheduled ........ Next Due: 32 seconds from now
0 * * * *  php artisan social:sync-stats ............... Next Due: 25 minutes from now
```

### Monitorear Ejecuciones

```bash
# Ver logs del scheduler
tail -f storage/logs/laravel.log | grep "social:"
```

---

## 🔗 WEBHOOKS CONFIGURATION

### URLs de Webhooks

Configurar en cada plataforma:

| Red Social | Webhook URL |
|------------|-------------|
| **Facebook** | `https://tudominio.com/webhooks/social/facebook` |
| **Instagram** | `https://tudominio.com/webhooks/social/instagram` |
| **Twitter** | `https://tudominio.com/webhooks/social/twitter` |
| **LinkedIn** | `https://tudominio.com/webhooks/social/linkedin` |

### Testing Webhooks

**Facebook Webhook Test**:

```bash
curl -X GET "https://tudominio.com/webhooks/social/facebook?hub.mode=subscribe&hub.verify_token=YOUR_VERIFY_TOKEN&hub.challenge=test_challenge_123"
```

Expected: `test_challenge_123`

**Twitter CRC Challenge**:

```bash
curl -X GET "https://tudominio.com/webhooks/social/twitter?crc_token=test_token"
```

Expected: `{"response_token":"sha256=..."}`

---

## 🔐 OAUTH SETUP

### Flow de Conexión de Cuentas

**Para el Usuario**:

1. Admin Panel → Social → Accounts
2. Click "Conectar Cuenta"
3. Seleccionar Red Social (Facebook/Instagram/Twitter/LinkedIn)
4. Autorizar aplicación
5. Seleccionar páginas/cuentas a conectar
6. Sistema guarda automáticamente:
   - `network_id` (Page ID, Instagram Business Account ID, etc.)
   - `access_token` (encriptado)
   - `username`
   - `metadata` (nombre de página, followers, etc.)

### Testing OAuth en Dev/Staging

**Configurar Test Users**:

- **Facebook**: Dev console → Roles → Test Users
- **Twitter**: Standard project permite testing
- **LinkedIn**: Usar tu cuenta personal

---

## 🧪 TESTING EN PRODUCCIÓN

### 1. Conectar Cuenta de Prueba

```bash
# Ir a: https://tudominio.com/admin/social/accounts
# Click "Conectar Cuenta" → Facebook
# Autorizar y seleccionar una página de prueba
```

### 2. Crear Post Programado

```bash
# Crear post vía tinker
php artisan tinker
```

```php
use Modules\Social\Models\Post;
use Modules\Social\Enums\PostType;
use Modules\Social\Enums\PostStatus;

$account = \Modules\Social\Models\SocialAccount::first();

Post::create([
    'account_id' => 1,
    'social_account_id' => $account->id,
    'type' => PostType::TEXT,
    'content' => '🧪 Test post from Channels - ' . now(),
    'status' => PostStatus::SCHEDULED,
    'scheduled_at' => now()->addMinutes(2),
    'created_by' => 1,
]);
```

### 3. Verificar Publicación Automática

```bash
# Esperar 2 minutos y verificar
tail -f storage/logs/laravel.log | grep "PublishPostJob"
```

### 4. Verificar Post Publicado

```bash
php artisan tinker
```

```php
$post = \Modules\Social\Models\Post::latest()->first();
echo "Status: {$post->status->value}\n";
echo "External ID: {$post->external_id}\n";
echo "External URL: {$post->external_url}\n";
```

---

## 📊 MONITORING & LOGS

### Laravel Horizon (Recomendado para Redis)

**Instalar**:

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

**Configurar Supervisor**:

```ini
[program:channels-horizon]
process_name=%(program_name)s
command=php /var/www/html/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/horizon.log
stopwaitsecs=3600
```

**Acceder**:

```
https://tudominio.com/horizon
```

### Logs a Monitorear

```bash
# Queue jobs
tail -f storage/logs/laravel.log | grep "PublishPostJob"

# Scheduler
tail -f storage/logs/laravel.log | grep "social:"

# Webhooks
tail -f storage/logs/laravel.log | grep "Webhook"

# Failed jobs
php artisan queue:failed
```

### Alertas Importantes

Configurar alertas para:

- ✅ Queue worker down
- ✅ Failed jobs > 10
- ✅ Cron job no ejecuta
- ✅ Token expiration errors

---

## 🐛 TROUBLESHOOTING

### Posts No Se Publican

**Verificar**:

1. Queue worker corriendo:
   ```bash
   sudo supervisorctl status channels-queue-worker:*
   ```

2. Scheduler ejecutándose:
   ```bash
   php artisan schedule:list
   ```

3. Post status:
   ```bash
   php artisan tinker
   $post = Post::find(X);
   echo $post->status->value;
   echo $post->error_message;
   ```

### Token Expiration Errors

**Síntomas**:
```
Error: OAuthException code 190
Error: Token expired
```

**Solución**:

1. Re-conectar cuenta vía OAuth
2. Sistema auto-detecta y marca cuenta como `status = 0`
3. Usuario debe re-autorizar en Admin Panel

### Webhooks No Funcionan

**Verificar signature**:

```bash
# Logs de webhook
tail -f storage/logs/laravel.log | grep "Invalid signature"
```

**Test manual**:

```bash
# Simular webhook de Facebook
curl -X POST https://tudominio.com/webhooks/social/facebook \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature-256: sha256=xxx" \
  -d '{"object":"page","entry":[]}'
```

### Failed Jobs Acumulándose

**Ver failed jobs**:

```bash
php artisan queue:failed
```

**Retry failed jobs**:

```bash
# Retry uno específico
php artisan queue:retry JOB_ID

# Retry todos
php artisan queue:retry all
```

**Flush failed jobs** (eliminar permanentemente):

```bash
php artisan queue:flush
```

---

## ✅ CHECKLIST DE DEPLOYMENT

### Pre-Deployment

- [ ] Apps creadas en Facebook/Twitter/LinkedIn
- [ ] OAuth redirect URLs configuradas
- [ ] Webhook URLs configuradas
- [ ] `.env` con todas las credenciales
- [ ] Migraciones ejecutadas
- [ ] Columnas `external_id` existen en `social_posts`

### Deployment

- [ ] Código deployed a servidor
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] Migrations ejecutadas

### Post-Deployment

- [ ] Queue workers corriendo (Supervisor/systemd)
- [ ] Cron job configurado y funcionando
- [ ] Horizon/Pulse instalado y corriendo
- [ ] Test de conexión OAuth exitoso
- [ ] Test post publicado exitosamente
- [ ] Webhooks respondiendo correctamente
- [ ] Logs monitoreándose

---

## 🎉 DEPLOYMENT COMPLETADO

Una vez completado este checklist, el módulo Social está **PRODUCTION-READY** ✅

**Siguiente paso**: Conectar cuentas reales y empezar a publicar!

---

*Generado: 2025-12-27*
*Módulo: Social Media Management*
*Versión: 1.0.0*
