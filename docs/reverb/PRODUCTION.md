# Guía de producción - Laravel Reverb

Configuración y mejores prácticas para desplegar Reverb en producción.

## Requisitos previos

- PHP 8.1+
- Laravel 12+
- Node.js 16+ (para npm run build)
- OpenSSL para certificados HTTPS

## Configuración de producción

### 1. Variables de entorno

```ini
# Broadcasting
BROADCAST_DRIVER=reverb
REVERB_HOST=ws.tu-dominio.com
REVERB_PORT=443
REVERB_SCHEME=wss
REVERB_APP_ID=tu-app-id
REVERB_APP_KEY=tu-app-key-segura-aqui

# Servidor Reverb
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
REVERB_DEBUG=false
```

### 2. Certificados SSL/TLS

Para usar WSS (WebSocket Secure), necesitas certificados SSL:

```bash
# Usando Let's Encrypt
certbot certonly --standalone -d ws.tu-dominio.com

# Los certificados estarán en:
# /etc/letsencrypt/live/ws.tu-dominio.com/
```

### 3. Supervisor para procesos

Instala Supervisor para mantener Reverb corriendo:

```bash
sudo apt-get install supervisor
```

Crea `/etc/supervisor/conf.d/reverb.conf`:

```ini
[program:reverb]
process_name=%(program_name)s
command=php /path/to/app/artisan reverb:start --port=8080
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/reverb.log
user=www-data
numprocs=1
```

Actualiza Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb
```

Verifica el estado:

```bash
sudo supervisorctl status reverb
```

### 4. Nginx como proxy inverso

Configura Nginx para hacer proxy de las conexiones WebSocket:

```nginx
upstream reverb {
    server 127.0.0.1:8080;
}

server {
    listen 443 ssl http2;
    server_name ws.tu-dominio.com;

    ssl_certificate /etc/letsencrypt/live/ws.tu-dominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ws.tu-dominio.com/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    location / {
        proxy_pass http://reverb;
        proxy_http_version 1.1;

        # WebSocket headers
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        # Headers de proxy
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # Timeouts
        proxy_read_timeout 3600s;
        proxy_send_timeout 3600s;
        proxy_connect_timeout 7d;

        # Buffering
        proxy_buffering off;
        proxy_request_buffering off;
    }

    # Redirigir HTTP a HTTPS
    error_page 497 https://$server_name:443$request_uri;
}

# Redirigir HTTP a HTTPS
server {
    listen 80;
    server_name ws.tu-dominio.com;
    return 301 https://$server_name$request_uri;
}
```

### 5. Apache como proxy inverso

Si usas Apache:

```apache
<VirtualHost *:443>
    ServerName ws.tu-dominio.com

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/ws.tu-dominio.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/ws.tu-dominio.com/privkey.pem

    # Habilitar módulos requeridos
    # a2enmod proxy
    # a2enmod proxy_http
    # a2enmod rewrite
    # a2enmod ssl

    ProxyPreserveHost On
    ProxyPass / http://127.0.0.1:8080/
    ProxyPassReverse / http://127.0.0.1:8080/

    # WebSocket support
    RewriteEngine On
    RewriteCond %{HTTP:Upgrade} websocket [NC]
    RewriteCond %{HTTP:Connection} upgrade [NC]
    RewriteRule ^/?(.*) "ws://127.0.0.1:8080/$1" [P,L]
</VirtualHost>

# Redirigir HTTP a HTTPS
<VirtualHost *:80>
    ServerName ws.tu-dominio.com
    Redirect permanent / https://ws.tu-dominio.com/
</VirtualHost>
```

## Escalabilidad

### Múltiples procesos Reverb

Para mayor capacidad, ejecuta múltiples procesos:

```bash
# Crear 4 procesos Reverb en puertos diferentes
for port in 8080 8081 8082 8083; do
    php artisan reverb:start --port=$port &
done
```

Configura Nginx para load balance:

```nginx
upstream reverb {
    server 127.0.0.1:8080;
    server 127.0.0.1:8081;
    server 127.0.0.1:8082;
    server 127.0.0.1:8083;
}
```

### Usando Docker

Dockerfile para Reverb:

```dockerfile
FROM php:8.4-fpm

WORKDIR /app

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    && docker-php-ext-install \
    pdo_mysql \
    zip

# Copiar composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar código
COPY . /app

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader

EXPOSE 8080

CMD ["php", "artisan", "reverb:start", "--host=0.0.0.0", "--port=8080"]
```

Docker Compose:

```yaml
version: '3.8'

services:
  reverb:
    build: .
    ports:
      - "8080:8080"
      - "8081:8080"
      - "8082:8080"
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis
      - REVERB_SERVER_HOST=0.0.0.0
      - REVERB_SERVER_PORT=8080
    depends_on:
      - mysql
      - redis

  mysql:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=secret
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine

volumes:
  mysql_data:
```

## Monitoreo y logging

### Configurar logging

En `config/logging.php`:

```php
'channels' => [
    'reverb' => [
        'driver' => 'daily',
        'path' => storage_path('logs/reverb.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 7,
    ],
],
```

### Monitorar con Sentry

Instala Sentry:

```bash
composer require sentry/sentry-laravel
```

Configura en `.env`:

```ini
SENTRY_LARAVEL_DSN=https://xxx@sentry.io/xxx
SENTRY_ENVIRONMENT=production
```

### Health Check

Crea un endpoint para verificar que Reverb está activo:

```php
<?php

namespace App\Http\Controllers;

class HealthController extends Controller
{
    public function reverb()
    {
        $host = config('reverb.server.host');
        $port = config('reverb.server.port');

        $socket = @fsockopen($host, $port, $errno, $errstr, 2);
        $isHealthy = $socket !== false;

        if ($socket) {
            fclose($socket);
        }

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'reverb' => $isHealthy,
        ], $isHealthy ? 200 : 503);
    }
}
```

## Seguridad

### Validación de autenticación

Asegúrate de que los canales privados validan correctamente:

```php
// En Broadcast.php (rutas de broadcasting)
Broadcast::channel('private-user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('presence-team.{teamId}', function ($user, $teamId) {
    return $user->teams()->where('id', $teamId)->exists();
});
```

### Rate limiting

Previene abuso limitando eventos:

```php
// En tu evento
use Illuminate\Broadcasting\ShouldBroadcast;

class MyEvent implements ShouldBroadcast
{
    // Limita a 10 eventos por minuto por usuario
    public function broadcastAs(): string
    {
        return 'my-event';
    }
}
```

### CORS configuración

En `config/cors.php`:

```php
'allowed_origins' => [
    'https://tu-dominio.com',
],
```

## Performance tuning

### Optimizar Node.js/Echo client

```javascript
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],
    reconnectAfter: 5,        // Reconectar después de 5s
    maxReconnectionAttempts: 5, // Máximo 5 intentos
    authorizeEndpoint: '/api/broadcasting/auth',
});
```

### Limitar tamaño de mensajes

```php
// En config/reverb.php
'max_message_size' => 1024 * 256, // 256KB máximo
```

### Implementar heartbeat

```javascript
// Enviar ping periódicamente
setInterval(() => {
    Echo.channel('heartbeat')
        .listen('Ping', () => {
            // Verificar conexión activa
            console.log('Conexión activa');
        });
}, 30000);
```

## Backup y disaster recovery

### Backup de configuración

```bash
#!/bin/bash
# backup-reverb.sh

BACKUP_DIR="/backups/reverb"
mkdir -p $BACKUP_DIR

# Backup de configuración
tar -czf "$BACKUP_DIR/reverb-config-$(date +%Y%m%d).tar.gz" \
    /etc/letsencrypt \
    /etc/supervisor/conf.d/reverb.conf \
    /etc/nginx/sites-available/*reverb*

# Backup de logs
tar -czf "$BACKUP_DIR/reverb-logs-$(date +%Y%m%d).tar.gz" \
    /var/log/reverb.log

# Mantener solo últimos 7 días
find $BACKUP_DIR -mtime +7 -delete
```

## Troubleshooting en producción

### Conexiones se cierran

Aumenta los timeouts en Nginx:

```nginx
proxy_read_timeout 3600s;
proxy_send_timeout 3600s;
```

### Alto uso de memoria

Revisa el número de conexiones activas:

```bash
# Ver conexiones Reverb
sudo supervisorctl status reverb
ps aux | grep reverb

# Limitar memoria por proceso
# En supervisor: memory_limit=512M
```

### Errores SSL/TLS

Verifica certificados:

```bash
openssl s_client -connect ws.tu-dominio.com:443
```

Renueva certificados con cron:

```bash
# Agregar a crontab
0 0 * * * certbot renew --post-hook "supervisorctl restart reverb"
```
