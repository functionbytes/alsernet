# Production Deployment Checklist

> Última actualización: 2026-05-07
> Cubre: system Laravel + alsernetbridge PS + manager Docker + Helpdesk modules + Remarketing + Engagement

---

## 1. Variables de entorno requeridas

### system Laravel (`/Users/developerts/Herd/system/.env`)

```env
# App básico
APP_NAME=...
APP_ENV=production
APP_KEY=base64:...               # generar con php artisan key:generate
APP_URL=https://system.tudominio.com
APP_DEBUG=false                  # CRÍTICO: false en producción

# DB principal
DB_CONNECTION=mysql              # o mariadb
DB_HOST=...
DB_PORT=3306
DB_DATABASE=system_prod
DB_USERNAME=system_app
DB_PASSWORD=<usar Vault/SecretManager>

# Redis (cache + queue + sessions + Reverb)
REDIS_HOST=redis.internal
REDIS_PORT=6379
REDIS_PASSWORD=<vault>
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# HelpdeskErp — manager API
ERP_MANAGER_URL=https://manager.tudominio.com
ERP_BRIDGE_TOKEN=                # opcional
ERP_WEBHOOK_SECRET=<openssl rand -hex 32>

# HelpdeskPrestashop — alsernetbridge module
ALSERNETBRIDGE_API_URL=https://tienda.tudominio.com/modules/alsernetbridge/api.php
ALSERNETBRIDGE_WEBHOOK_SECRET=<openssl rand -hex 32>
HELPDESK_PS_CACHE_TTL=300
HELPDESK_PS_MISS_TTL=60
HELPDESK_PS_STALE_GRACE=30

# HelpdeskErp performance
HELPDESK_ERP_CACHE_TTL=600
HELPDESK_ERP_MISS_TTL=60
HELPDESK_ERP_STALE_GRACE=60

# Remarketing webhook secrets (uno por proveedor que uses)
REMARKETING_WEBHOOK_SECRET_MAILRELAY=<openssl rand -hex 32>
REMARKETING_WEBHOOK_SECRET_MAILGUN=<openssl rand -hex 32>
REMARKETING_WEBHOOK_SECRET_SENDGRID=<openssl rand -hex 32>
REMARKETING_WEBHOOK_SECRET_POSTMARK=<openssl rand -hex 32>

# Reverb (broadcasting)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=<random>
REVERB_APP_KEY=<random>
REVERB_APP_SECRET=<vault>
REVERB_HOST=ws.tudominio.com
REVERB_PORT=443
REVERB_SCHEME=https

# Sanctum
SANCTUM_STATEFUL_DOMAINS=tudominio.com,app.tudominio.com

# Pulse / Telescope
TELESCOPE_ENABLED=false          # false en prod (debug only)
PULSE_ENABLED=true

# Mail
MAIL_MAILER=smtp                 # o mailjet, mailgun, etc.
MAIL_HOST=...
MAIL_FROM_ADDRESS=noreply@tudominio.com
```

### manager (`/Users/developerts/Herd/manager/src/.env` + docker-compose env)

```env
# Webhook al system Helpdesk
SYSTEM_WEBHOOK_URL=https://system.tudominio.com
SYSTEM_WEBHOOK_SECRET=<MISMO valor que ERP_WEBHOOK_SECRET en system>

# Oracle ERP
ORACLE_HOST=...
ORACLE_SERVICE_NAME=GESTCENT
ORACLE_USERNAME=lectura
ORACLE_PASSWORD=<vault>
```

### PrestaShop alsernetbridge (`aalv_configuration` table)

```sql
INSERT INTO aalv_configuration (name, value, date_add, date_upd) VALUES
('ALSERNETBRIDGE_WEBHOOK_SECRET', '<MISMO que en system>', NOW(), NOW()),
('ALSERNETBRIDGE_HELPDESK_WEBHOOK_URL', 'https://system.tudominio.com/api/helpdeskprestashop/webhook', NOW(), NOW()),
('ALSERNETBRIDGE_INTEGRATION_ID', '1', NOW(), NOW())
ON DUPLICATE KEY UPDATE value=VALUES(value);
```

## 2. Migrations

```bash
cd /var/www/system
php artisan migrate --force

# Verificar
php artisan migrate:status | grep -i pending
```

Si falta correr migrations específicas:
- `2026_05_07_000001_create_helpdesk_data_access_log_table` (GDPR audit log)
- Todas las `engagement_*`
- Todas las `remarketing_*`

## 3. Permission seeders

```bash
php artisan db:seed --class="Modules\\HelpdeskErp\\Database\\Seeders\\HelpdeskErpPermissionsSeeder"
php artisan db:seed --class="Modules\\HelpdeskPrestashop\\Database\\Seeders\\HelpdeskPrestashopPermissionsSeeder"
php artisan db:seed --class="Modules\\Engagement\\Database\\Seeders\\EngagementPermissionsSeeder"
php artisan db:seed --class="Modules\\Remarketing\\Database\\Seeders\\RemarketingPermissionsSeeder"
```

## 4. Caches Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

⚠️ **NO** cachear si hay closures en routes (Laravel rechazará) — busca rutas con closures antes de cachear.

## 5. Workers de queue (Supervisor)

`/etc/supervisor/conf.d/system-queue.conf`:

```ini
[program:system-default-worker]
command=php /var/www/system/artisan queue:work redis --queue=default --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/system-default.log

[program:system-helpdesk-erp-worker]
command=php /var/www/system/artisan queue:work redis --queue=helpdesk-erp,helpdesk-erp-warming --sleep=3 --tries=3
numprocs=2
user=www-data

[program:system-helpdesk-ps-worker]
command=php /var/www/system/artisan queue:work redis --queue=helpdesk-ps,helpdesk-ps-warming --sleep=3 --tries=3
numprocs=2
user=www-data

[program:system-remarketing-worker]
command=php /var/www/system/artisan queue:work redis --queue=remarketing,remarketing-webhooks --sleep=3 --tries=3
numprocs=4
user=www-data

[program:system-engagement-worker]
command=php /var/www/system/artisan queue:work redis --queue=engagement,engagement-events --sleep=3 --tries=3
numprocs=4
user=www-data

[program:system-reverb]
command=php /var/www/system/artisan reverb:start --host=0.0.0.0 --port=8080
autorestart=true
numprocs=1
user=www-data
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

## 6. Schedule (cron)

`/etc/cron.d/system-schedule`:

```cron
* * * * * www-data cd /var/www/system && php artisan schedule:run >> /dev/null 2>&1
```

Schedule activo verificable con:
```bash
php artisan schedule:list
```

Items críticos:
- `helpdeskerp:warm-cache` cada 30min
- `helpdeskprestashop:warm-cache` cada 30min
- `engagement:anonymize-old-ips` daily 03:30 (GDPR)
- `helpdesk:check-sla` cada 5min
- alsernetbridge cron retry queue (en PS, no Laravel — separate cron)

## 7. PrestaShop alsernetbridge crons

Servidor PS (no en system Laravel):

```cron
# Cada minuto: procesar webhook retry queue
* * * * * curl -s -H "X-Alsernet-Cron-Secret: $WEBHOOK_SECRET" https://tienda.tudominio.com/modules/alsernetbridge/cron-webhook-retry.php

# Cada 6h: catalog sync
0 */6 * * * curl -s -H "X-Alsernet-Cron-Secret: $WEBHOOK_SECRET" "https://tienda.tudominio.com/modules/alsernetbridge/cron.php?page=1"
```

## 8. Permisos filesystem

```bash
chown -R www-data:www-data /var/www/system/storage /var/www/system/bootstrap/cache
chmod -R 775 /var/www/system/storage /var/www/system/bootstrap/cache

# Cache PS alsernetbridge
mkdir -p /var/www/prestashop/var/cache/alsernetbridge
chown www-data:www-data /var/www/prestashop/var/cache/alsernetbridge
```

## 9. Reverse proxy / nginx

Asegurar:
- HTTPS enforced (HSTS)
- WebSocket upgrade headers para `/app/{appkey}` (Reverb)
- Proxy timeout >30s para endpoints ERP que pueden ser lentos
- Gzip on
- Rate limiting global complementario al de Laravel

## 10. Monitoring

- **Pulse** (`/pulse`): habilitado para CTOs/devops
- **Health check**: `GET /up` de Laravel + `GET /modules/alsernetbridge/health.php` en PS
- **Logs**: stack a `/var/log/system/` con rotación diaria (logrotate)
- **Alerting**: configurar `BACKUP_NOTIFICATION_EMAIL` y `BACKUP_SLACK_WEBHOOK`
- **Métricas Prometheus**: scrape `https://tienda/modules/alsernetbridge/metrics.php?secret=$SECRET` cada 60s

## 11. Verificación post-deploy

```bash
# Endpoints críticos
curl -sf https://system.tudominio.com/up
curl -sf https://manager.tudominio.com/up
curl -sf https://tienda.tudominio.com/modules/alsernetbridge/health.php

# Workers vivos
sudo supervisorctl status | grep -E "system-.*RUNNING"

# Schedule corriendo
tail -50 /var/log/syslog | grep "schedule:run"

# Logs sin errores recientes
tail -100 /var/www/system/storage/logs/laravel.log | grep -iE "error|critical"
```

## 12. Rollback plan

Si algo falla:

```bash
# 1. Volver al commit anterior
cd /var/www/system && git checkout HEAD~1

# 2. Composer dependencies del commit anterior
composer install --no-interaction --no-dev --optimize-autoloader

# 3. Deshacer migrations si las nuevas son incompatibles
php artisan migrate:rollback --step=N --force

# 4. Reiniciar workers
sudo supervisorctl restart all

# 5. Limpiar caches
php artisan optimize:clear
php artisan config:cache
```

## 13. Secrets management

**NUNCA commits**:
- `.env` (siempre `.gitignore`)
- `oauth-private.key`, `oauth-public.key`
- Keys de servicios externos

**Usar**:
- Vault, AWS Secrets Manager, Doppler, o similar
- Inyectar via env del runtime (Docker `environment:` o systemd)

## 14. Backups

- DB diario (mysqldump comprimido a S3/Backblaze)
- `storage/app/` diario
- `storage/api-docs/` (OpenAPI specs si aplica)
- Retención 30 días

```bash
# Cron diario
0 3 * * * php /var/www/system/artisan backup:run
```

## 15. GDPR / compliance

- Audit log activo: `helpdesk_data_access_log` registra cada acceso a customer data
- IP anonymization activo: `engagement:anonymize-old-ips` corre diario
- Consent flow activo: `SendDoubleOptinMailJob` operativo (verificar que el job está siendo procesado)
- DSR endpoints disponibles: `POST /eng/api/sdk/gdpr/{export,delete}` y `POST /api/r/dsr/{export,delete}` (Remarketing)
- Suppression list respetada: `Suppression` model + `isSuppressed()` consultado en cada envío

## Smoke tests post-deploy

```bash
# 1. Login admin → ver popover de un ticket → tabs ERP+PS+Timeline cargan
# 2. Crear suppression → confirmar que email no recibe campaign
# 3. Trigger webhook PS → confirmar evento en helpdesk_data_access_log
# 4. Reverb event broadcast → confirmar recepción en frontend (network tab WS)
# 5. Cron retry queue PS → confirmar que webhook_queue se reduce
```
