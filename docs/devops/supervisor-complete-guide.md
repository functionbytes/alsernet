# Guía Completa de Supervisor para Laravel

## Índice
- [Introducción](#introducción)
- [¿Qué es Supervisor?](#qué-es-supervisor)
- [Instalación de Supervisor](#instalación-de-supervisor)
- [Configuración para Queue Workers](#configuración-para-queue-workers)
- [Configuración para Laravel Horizon](#configuración-para-laravel-horizon)
- [Configuración para Laravel Reverb](#configuración-para-laravel-reverb)
- [Gestión de Procesos](#gestión-de-procesos)
- [Configuración Passwordless Sudo](#configuración-passwordless-sudo)
- [Monitoreo y Logs](#monitoreo-y-logs)
- [Deployment y Mejores Prácticas](#deployment-y-mejores-prácticas)
- [Troubleshooting](#troubleshooting)

---

## Introducción

Esta guía cubre la implementación completa de Supervisor para gestionar procesos de Laravel: queue workers, Horizon y Reverb. Supervisor es un monitor de procesos que mantiene tus servicios corriendo 24/7 y los reinicia automáticamente si fallan.

---

## ¿Qué es Supervisor?

**Supervisor** es un sistema de control de procesos para Linux/Unix que permite:

- ✅ **Mantener procesos corriendo** - Reinicio automático si fallan
- ✅ **Gestión centralizada** - Controlar múltiples procesos desde un solo lugar
- ✅ **Logs centralizados** - Todos los logs en un directorio
- ✅ **Inicio automático** - Los procesos se inician con el sistema
- ✅ **Control granular** - Iniciar, detener, reiniciar procesos individuales
- ✅ **Escalabilidad** - Múltiples workers para procesar colas

**Casos de uso en Laravel:**
1. **Queue Workers** - Procesar trabajos en segundo plano
2. **Laravel Horizon** - Dashboard y gestión avanzada de queues Redis
3. **Laravel Reverb** - Servidor WebSocket para broadcasting
4. **Laravel Octane** - Servidor de aplicaciones de alto rendimiento

---

## Instalación de Supervisor

### Ubuntu/Debian

```bash
# Actualizar repositorios
sudo apt-get update

# Instalar Supervisor
sudo apt-get install supervisor

# Verificar instalación
supervisorctl version

# Habilitar inicio automático
sudo systemctl enable supervisor

# Iniciar servicio
sudo systemctl start supervisor

# Verificar estado
sudo systemctl status supervisor
```

### macOS (Homebrew)

```bash
# Instalar Supervisor
brew install supervisor

# Iniciar servicio
brew services start supervisor

# Verificar que está corriendo
brew services list | grep supervisor
```

### Verificar instalación

```bash
# Debe mostrar la versión (ej: 4.2.4)
supervisorctl version

# Debe mostrar el archivo de configuración principal
supervisorctl --configuration

# Listar procesos (debe estar vacío inicialmente)
sudo supervisorctl status
```

---

## Configuración para Queue Workers

Los queue workers procesan trabajos en segundo plano. Esta configuración es para cuando **NO** usas Horizon (solo colas estándar de Laravel).

### 1. Archivo de Configuración

**Ubicación**: `/etc/supervisor/conf.d/laravel-worker.conf`

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/completa/a/tu/proyecto/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/ruta/completa/a/tu/proyecto/storage/logs/worker.log
stopwaitsecs=3600
```

### 2. Explicación de Parámetros

| Parámetro | Descripción | Valor Recomendado |
|-----------|-------------|-------------------|
| `process_name` | Nombre del proceso con número | `%(program_name)s_%(process_num)02d` |
| `command` | Comando artisan a ejecutar | `php artisan queue:work` |
| `autostart` | Iniciar con Supervisor | `true` |
| `autorestart` | Reiniciar si falla | `true` |
| `stopasgroup` | Matar proceso hijo al detener | `true` |
| `killasgroup` | Matar grupo de procesos | `true` |
| `user` | Usuario que ejecuta el proceso | `www-data` o `forge` |
| `numprocs` | Número de workers simultáneos | 4-16 (según CPU) |
| `redirect_stderr` | Redirigir errores a stdout | `true` |
| `stdout_logfile` | Archivo de logs | Ruta en storage/logs |
| `stopwaitsecs` | Segundos antes de forzar stop | `3600` (1 hora) |

### 3. Parámetros del Comando Queue:work

```bash
php artisan queue:work database \
  --sleep=3 \            # Segundos de espera si no hay trabajos
  --tries=3 \            # Intentos antes de marcar como fallido
  --max-time=3600 \      # Máximo 1 hora antes de reiniciar worker
  --timeout=300 \        # Timeout de 5 minutos por trabajo
  --queue=high,default,low  # Prioridad de colas (opcional)
```

### 4. Configuración Personalizada por Cola

Si tienes múltiples colas con diferentes prioridades:

```ini
# Worker para cola de alta prioridad
[program:laravel-worker-high]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/proyecto/artisan queue:work database --queue=high --sleep=1 --tries=3
user=www-data
numprocs=4
autostart=true
autorestart=true
stdout_logfile=/ruta/proyecto/storage/logs/worker-high.log

# Worker para cola normal
[program:laravel-worker-default]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/proyecto/artisan queue:work database --queue=default --sleep=3 --tries=3
user=www-data
numprocs=6
autostart=true
autorestart=true
stdout_logfile=/ruta/proyecto/storage/logs/worker-default.log

# Worker para cola de baja prioridad
[program:laravel-worker-low]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/proyecto/artisan queue:work database --queue=low --sleep=5 --tries=2
user=www-data
numprocs=2
autostart=true
autorestart=true
stdout_logfile=/ruta/proyecto/storage/logs/worker-low.log
```

### 5. Activar y Gestionar Workers

```bash
# Recargar configuración de Supervisor
sudo supervisorctl reread

# Aplicar cambios
sudo supervisorctl update

# Iniciar todos los workers
sudo supervisorctl start "laravel-worker:*"

# Ver estado
sudo supervisorctl status

# Detener todos los workers
sudo supervisorctl stop "laravel-worker:*"

# Reiniciar todos los workers
sudo supervisorctl restart "laravel-worker:*"

# Ver logs en tiempo real
sudo supervisorctl tail -f laravel-worker:laravel-worker_00 stdout
```

---

## Configuración para Laravel Horizon

Laravel Horizon proporciona un dashboard hermoso y configuración avanzada para colas Redis. **Importante**: Si usas Horizon, NO necesitas configurar workers estándar.

### 1. Verificar que Horizon está instalado

```bash
# Debe listar Horizon
composer show laravel/horizon

# Publicar assets
php artisan horizon:install

# Verificar configuración
cat config/horizon.php
```

### 2. Archivo de Configuración de Supervisor

**Ubicación**: `/etc/supervisor/conf.d/horizon.conf`

```ini
[program:horizon]
process_name=%(program_name)s
command=php /ruta/completa/a/tu/proyecto/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/ruta/completa/a/tu/proyecto/storage/logs/horizon.log
stopwaitsecs=3600
```

### 3. Explicación Específica de Horizon

| Aspecto | Detalle |
|---------|---------|
| **¿Por qué solo 1 proceso?** | Horizon gestiona internamente múltiples workers según `config/horizon.php` |
| **`stopwaitsecs=3600`** | Horizon necesita tiempo para completar trabajos largos antes de detenerse |
| **No `numprocs`** | Horizon controla el número de procesos, no Supervisor |

### 4. Configuración de Horizon (`config/horizon.php`)

```php
<?php

return [
    'use' => 'default',

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    'middleware' => ['web', 'auth'],

    'waits' => [
        'redis:default' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'failed' => 10080,
    ],

    'fast_termination' => false,

    'memory_limit' => 64,

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 10,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 300,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-high-priority' => [
                'connection' => 'redis',
                'queue' => ['high'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 2,
                'maxProcesses' => 8,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'tries' => 3,
                'timeout' => 300,
            ],
            'supervisor-default' => [
                'connection' => 'redis',
                'queue' => ['default'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 1,
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'tries' => 3,
                'timeout' => 600,
            ],
            'supervisor-low-priority' => [
                'connection' => 'redis',
                'queue' => ['low'],
                'balance' => 'simple',
                'processes' => 3,
                'tries' => 2,
                'timeout' => 900,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['default'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 3,
                'tries' => 3,
                'timeout' => 300,
            ],
        ],
    ],
];
```

### 5. Estrategias de Balanceo de Horizon

| Estrategia | Descripción | Cuándo Usar |
|------------|-------------|-------------|
| `auto` | Escala automáticamente según tiempo de espera | Carga variable |
| `simple` | Divide workers equitativamente | Carga constante |
| `false` | Sin balanceo, procesa en orden | Prioridad estricta |

### 6. Activar y Gestionar Horizon

```bash
# Recargar configuración de Supervisor
sudo supervisorctl reread

# Aplicar cambios
sudo supervisorctl update

# Iniciar Horizon
sudo supervisorctl start horizon

# Ver estado
sudo supervisorctl status horizon

# Ver logs en tiempo real
sudo supervisorctl tail -f horizon stdout

# Detener Horizon (espera a que terminen trabajos)
sudo supervisorctl stop horizon

# Reiniciar Horizon
sudo supervisorctl restart horizon
```

### 7. Dashboard de Horizon

Accede al dashboard en: `https://tu-dominio.com/horizon`

**Configurar autenticación** en `app/Providers/HorizonServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Laravel\Horizon\Horizon;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        Horizon::auth(function ($request) {
            // Solo usuarios autenticados con rol admin
            return $request->user() &&
                   $request->user()->hasRole('admin');
        });
    }
}
```

### 8. Comandos de Horizon

```bash
# Iniciar Horizon manualmente (para testing)
php artisan horizon

# Pausar procesamiento de trabajos
php artisan horizon:pause

# Continuar procesamiento
php artisan horizon:continue

# Pausar supervisor específico
php artisan horizon:pause-supervisor supervisor-1

# Terminar Horizon (completa trabajos actuales)
php artisan horizon:terminate

# Ver estado de Horizon
php artisan horizon:status

# Estado de supervisor específico
php artisan horizon:supervisor-status supervisor-1

# Limpiar trabajos completados/fallidos
php artisan horizon:clear

# Ver estadísticas
php artisan horizon:snapshot
```

---

## Configuración para Laravel Reverb

Laravel Reverb es el servidor WebSocket de Laravel para broadcasting en tiempo real.

### 1. Archivo de Configuración de Supervisor

**Ubicación**: `/etc/supervisor/conf.d/reverb.conf`

```ini
[program:reverb]
process_name=%(program_name)s
command=php /ruta/completa/a/tu/proyecto/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/ruta/completa/a/tu/proyecto/storage/logs/reverb.log
stopwaitsecs=60
```

### 2. Explicación Específica de Reverb

| Parámetro | Detalle |
|-----------|---------|
| **`stopwaitsecs=60`** | Reverb necesita 60 segundos para cerrar conexiones WebSocket gracefully |
| **Solo 1 proceso** | Reverb maneja múltiples conexiones en un solo proceso (event loop) |
| **`autorestart=true`** | Reinicia automáticamente si el proceso falla |

### 3. Configuración con Múltiples Servidores Reverb (Opcional)

Si necesitas escalar Reverb horizontalmente:

```ini
[program:reverb]
process_name=reverb_%(process_num)02d
command=php /ruta/proyecto/artisan reverb:start --port=%(process_num)d80
autostart=true
autorestart=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/ruta/proyecto/storage/logs/reverb-%(process_num)02d.log
stopwaitsecs=60
```

Esto creará 3 procesos Reverb en puertos 8080, 8081, 8082.

### 4. Activar y Gestionar Reverb

```bash
# Recargar configuración de Supervisor
sudo supervisorctl reread

# Aplicar cambios
sudo supervisorctl update

# Iniciar Reverb
sudo supervisorctl start reverb

# Ver estado
sudo supervisorctl status reverb

# Ver logs en tiempo real
sudo supervisorctl tail -f reverb stdout

# Detener Reverb
sudo supervisorctl stop reverb

# Reiniciar Reverb
sudo supervisorctl restart reverb
```

### 5. Verificar que Reverb está corriendo

```bash
# Verificar proceso
ps aux | grep reverb

# Verificar puerto
netstat -tuln | grep 8080
# o
lsof -i :8080

# Probar conexión WebSocket
curl -i -N -H "Connection: Upgrade" \
     -H "Upgrade: websocket" \
     -H "Host: tu-dominio.com:8080" \
     -H "Origin: https://tu-dominio.com" \
     https://tu-dominio.com:8080
```

---

## Gestión de Procesos

### Comandos Básicos

```bash
# Ver todos los procesos
sudo supervisorctl status

# Iniciar un proceso específico
sudo supervisorctl start horizon

# Detener un proceso
sudo supervisorctl stop horizon

# Reiniciar un proceso
sudo supervisorctl restart horizon

# Iniciar/detener/reiniciar todos
sudo supervisorctl start all
sudo supervisorctl stop all
sudo supervisorctl restart all
```

### Comandos con Grupos de Procesos

```bash
# Iniciar todos los workers (si usas naming pattern)
sudo supervisorctl start "laravel-worker:*"

# Detener todos los workers
sudo supervisorctl stop "laravel-worker:*"

# Reiniciar worker específico por número
sudo supervisorctl restart laravel-worker:laravel-worker_00
sudo supervisorctl restart laravel-worker:laravel-worker_01
```

### Ver Logs

```bash
# Ver últimas líneas del log
sudo supervisorctl tail horizon

# Ver log en tiempo real
sudo supervisorctl tail -f horizon stdout

# Ver últimas 100 líneas
sudo supervisorctl tail -100 horizon stdout

# Ver log de errores
sudo supervisorctl tail -f horizon stderr
```

### Recargar Configuración

```bash
# Leer archivos de configuración nuevos o modificados
sudo supervisorctl reread

# Aplicar cambios (reinicia procesos modificados)
sudo supervisorctl update

# Recargar la configuración de Supervisor completamente
sudo supervisorctl reload
```

### Estado del Sistema

```bash
# Ver versión de Supervisor
supervisorctl version

# Ver PID del proceso de Supervisor
sudo supervisorctl pid

# Detener Supervisor completamente
sudo supervisorctl shutdown

# Reiniciar el daemon de Supervisor
sudo systemctl restart supervisor
```

---

## Configuración Passwordless Sudo

Para que tu panel web de Supervisor funcione sin problemas de permisos, configura sudo sin contraseña.

### 1. Identificar el Usuario Web

```bash
# Encontrar usuario que ejecuta PHP
ps aux | grep -E '(apache|nginx|php-fpm)' | head -n 1

# Comúnmente es:
# - Ubuntu/Debian: www-data
# - CentOS/RedHat: apache o nginx
# - macOS: _www
```

### 2. Crear Archivo de Configuración Sudoers

```bash
# Crear archivo de configuración (reemplaza www-data con tu usuario web)
sudo visudo -f /etc/sudoers.d/supervisor-web
```

### 3. Contenido del Archivo

```text
# Allow www-data to run supervisorctl commands without password
www-data ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart supervisor
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl status supervisor
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl start supervisor
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl stop supervisor
```

### 4. Verificar Permisos

```bash
# El archivo debe tener permisos 0440
sudo chmod 0440 /etc/sudoers.d/supervisor-web

# Verificar permisos
ls -l /etc/sudoers.d/supervisor-web
# Debe mostrar: -r--r----- 1 root root
```

### 5. Probar Configuración

```bash
# Cambiar a usuario web
sudo -u www-data bash

# Probar comando sin contraseña
sudo -n supervisorctl status

# Si funciona sin pedir contraseña, ¡está listo!
exit
```

### 6. Alternativa: Desarrollo Local (Menos Seguro)

Solo para desarrollo local:

```bash
sudo visudo -f /etc/sudoers.d/supervisor-dev
```

```text
# Development only - Allow web user full supervisor access
www-data ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl *
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl * supervisor
```

---

## Monitoreo y Logs

### 1. Ubicación de Logs

```bash
# Logs de Supervisor mismo
/var/log/supervisor/supervisord.log

# Logs de procesos individuales (según configuración)
/ruta/proyecto/storage/logs/worker.log
/ruta/proyecto/storage/logs/horizon.log
/ruta/proyecto/storage/logs/reverb.log
```

### 2. Ver Logs en Tiempo Real

```bash
# Log de Supervisor
sudo tail -f /var/log/supervisor/supervisord.log

# Log de Horizon
sudo tail -f /ruta/proyecto/storage/logs/horizon.log

# Log de Reverb
sudo tail -f /ruta/proyecto/storage/logs/reverb.log

# Log de Workers
sudo tail -f /ruta/proyecto/storage/logs/worker.log
```

### 3. Rotación de Logs

Configurar logrotate para evitar que los logs crezcan indefinidamente:

**Archivo**: `/etc/logrotate.d/laravel-supervisor`

```text
/ruta/proyecto/storage/logs/worker.log
/ruta/proyecto/storage/logs/horizon.log
/ruta/proyecto/storage/logs/reverb.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    missingok
    create 0644 www-data www-data
    sharedscripts
    postrotate
        sudo supervisorctl restart all > /dev/null
    endscript
}
```

### 4. Monitoreo de Recursos

```bash
# Ver uso de CPU y memoria de procesos
ps aux | grep -E '(horizon|reverb|queue:work)'

# Uso detallado con top
top -p $(pgrep -d',' -f 'horizon|reverb|queue:work')

# Con htop (más visual)
htop -p $(pgrep -d',' -f 'horizon|reverb|queue:work')
```

### 5. Alertas de Email (Opcional)

Configurar Supervisor para enviar emails cuando un proceso falla:

**En `/etc/supervisor/supervisord.conf`**:

```ini
[eventlistener:crashmail]
command=/usr/local/bin/crashmail -a -m tu-email@ejemplo.com -s "Supervisor Alert"
events=PROCESS_STATE_EXITED
```

Instalar crashmail:

```bash
pip install supervisor-crashmail
```

---

## Deployment y Mejores Prácticas

### 1. Script de Deployment

**Ubicación**: `scripts/deploy.sh`

```bash
#!/bin/bash

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Iniciando deployment...${NC}"

# Cambiar al directorio del proyecto
cd /ruta/a/tu/proyecto

# Git pull
echo -e "${YELLOW}📥 Pulling latest code...${NC}"
git pull origin main

# Composer install
echo -e "${YELLOW}📦 Installing Composer dependencies...${NC}"
composer install --no-dev --optimize-autoloader

# NPM install y build
echo -e "${YELLOW}🎨 Building frontend assets...${NC}"
npm install
npm run build

# Migrations
echo -e "${YELLOW}🗄️ Running migrations...${NC}"
php artisan migrate --force

# Clear cache
echo -e "${YELLOW}🧹 Clearing cache...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
echo -e "${YELLOW}🔄 Restarting queue workers...${NC}"
php artisan queue:restart

# Restart Horizon (si lo usas)
if sudo supervisorctl status horizon > /dev/null 2>&1; then
    echo -e "${YELLOW}⏸️ Terminating Horizon...${NC}"
    php artisan horizon:terminate
    sleep 5
    sudo supervisorctl restart horizon
fi

# Restart Reverb
if sudo supervisorctl status reverb > /dev/null 2>&1; then
    echo -e "${YELLOW}🔌 Restarting Reverb...${NC}"
    sudo supervisorctl restart reverb
fi

# Restart workers (si no usas Horizon)
if sudo supervisorctl status "laravel-worker:*" > /dev/null 2>&1; then
    echo -e "${YELLOW}👷 Restarting workers...${NC}"
    sudo supervisorctl restart "laravel-worker:*"
fi

echo -e "${GREEN}✅ Deployment completed!${NC}"

# Verificar estado
echo -e "${YELLOW}📊 Process status:${NC}"
sudo supervisorctl status
```

Hacer ejecutable:

```bash
chmod +x scripts/deploy.sh
```

### 2. Mejores Prácticas

#### Para Queue Workers

- ✅ **Usar `--max-time`**: Reinicia workers periódicamente para evitar memory leaks
- ✅ **Configurar `stopwaitsecs`**: Mayor que el timeout más largo de tus trabajos
- ✅ **Usar `stopasgroup` y `killasgroup`**: Para matar procesos hijo correctamente
- ✅ **Configurar `numprocs`**: Según número de CPUs (regla general: 2x núcleos)
- ✅ **Logs rotativos**: Configurar logrotate para evitar llenar disco

#### Para Horizon

- ✅ **Usar `horizon:terminate`**: En deployment en lugar de stop/restart
- ✅ **Configurar balanceo**: Auto scaling para carga variable
- ✅ **Monitorear dashboard**: Revisar métricas de throughput y wait time
- ✅ **Configurar trim**: Limpiar trabajos antiguos automáticamente
- ✅ **Proteger dashboard**: Autenticación en HorizonServiceProvider

#### Para Reverb

- ✅ **HTTPS en producción**: Usar wss:// con certificado SSL válido
- ✅ **Configurar firewall**: Abrir puerto de Reverb (8080)
- ✅ **Usar Nginx/Apache proxy**: Para SSL termination
- ✅ **Logs detallados**: Activar `--debug` durante troubleshooting
- ✅ **Heartbeat monitoring**: Verificar que WebSocket está respondiendo

### 3. Configuración de Nginx para Reverb

```nginx
# Proxy para WebSocket de Reverb
server {
    listen 443 ssl http2;
    server_name tu-dominio.com;

    # Certificados SSL
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # Aplicación Laravel normal
    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    # WebSocket para Reverb
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 86400;
    }
}
```

### 4. Verificación Post-Deployment

```bash
# Verificar todos los procesos
sudo supervisorctl status

# Verificar logs por errores
sudo tail -n 50 /ruta/proyecto/storage/logs/horizon.log
sudo tail -n 50 /ruta/proyecto/storage/logs/reverb.log

# Verificar que Reverb escucha en el puerto
netstat -tuln | grep 8080

# Probar una notificación de prueba
php artisan tinker
>>> event(new App\Events\TestBroadcast('Deployment test'));

# Verificar queue está procesando
php artisan queue:monitor
```

---

## Troubleshooting

### Problema 1: Supervisor no inicia procesos

**Síntomas:**
```bash
sudo supervisorctl status
# muestra: FATAL, Exited too quickly
```

**Diagnóstico:**
```bash
# Ver logs de Supervisor
sudo tail -f /var/log/supervisor/supervisord.log

# Ver logs del proceso específico
sudo supervisorctl tail horizon stderr
```

**Soluciones comunes:**
- ✅ Verificar que la ruta del comando es correcta (usa ruta absoluta)
- ✅ Verificar que el usuario tiene permisos
- ✅ Verificar que PHP está en el PATH
- ✅ Probar el comando manualmente: `sudo -u www-data php artisan horizon`

### Problema 2: Procesos se detienen después de un tiempo

**Síntomas:**
- Workers dejan de procesar
- Estado muestra "STOPPED" sin razón

**Diagnóstico:**
```bash
# Ver logs
sudo supervisorctl tail horizon stdout
sudo supervisorctl tail horizon stderr

# Ver logs de Laravel
tail -f storage/logs/laravel.log
```

**Soluciones comunes:**
- ✅ Incrementar `stopwaitsecs` a 3600
- ✅ Verificar memory_limit de PHP
- ✅ Revisar si hay excepciones no manejadas
- ✅ Usar `--max-time=3600` en queue:work

### Problema 3: Horizon no responde a comandos

**Síntomas:**
```bash
php artisan horizon:terminate
# No hace nada
```

**Soluciones:**
```bash
# Forzar stop con Supervisor
sudo supervisorctl stop horizon

# Esperar 10 segundos
sleep 10

# Iniciar de nuevo
sudo supervisorctl start horizon

# Si sigue sin responder, matar proceso
sudo pkill -f "artisan horizon"
sudo supervisorctl start horizon
```

### Problema 4: Reverb no acepta conexiones WebSocket

**Síntomas:**
- Frontend muestra "Connection failed"
- No se reciben eventos en tiempo real

**Diagnóstico:**
```bash
# Verificar que Reverb está corriendo
sudo supervisorctl status reverb

# Verificar puerto
netstat -tuln | grep 8080

# Ver logs de Reverb
sudo supervisorctl tail -f reverb stdout

# Probar conexión
curl -i http://localhost:8080/app/local-key
```

**Soluciones comunes:**
- ✅ Verificar firewall: `sudo ufw allow 8080`
- ✅ Verificar configuración de Nginx/Apache proxy
- ✅ Verificar certificado SSL válido
- ✅ Verificar que REVERB_HOST en .env es correcto
- ✅ Verificar CSRF token en frontend

### Problema 5: Workers no procesan después de deployment

**Síntomas:**
- Trabajos quedan en "pending"
- Dashboard muestra workers inactivos

**Soluciones:**
```bash
# Reiniciar workers correctamente
php artisan queue:restart

# Esperar a que terminen trabajos actuales
sleep 5

# Reiniciar con Supervisor
sudo supervisorctl restart "laravel-worker:*"
# o
sudo supervisorctl restart horizon

# Verificar estado
php artisan queue:work --once
# Debe procesar un trabajo y terminar
```

### Problema 6: Permisos de sudo

**Síntomas:**
```bash
sudo: a password is required
```

**Solución:**
Ver sección [Configuración Passwordless Sudo](#configuración-passwordless-sudo) arriba.

### Problema 7: Logs muy grandes

**Síntomas:**
- Disco lleno
- Logs de 1GB+

**Soluciones:**
```bash
# Limpiar logs manualmente
> /ruta/proyecto/storage/logs/horizon.log
> /ruta/proyecto/storage/logs/worker.log

# Configurar logrotate (ver sección de Monitoreo)

# Limpiar logs viejos de Laravel
find storage/logs -name "*.log" -mtime +7 -delete
```

---

## Resumen de Comandos Rápidos

```bash
# === CONFIGURACIÓN INICIAL ===

# Instalar Supervisor
sudo apt-get install supervisor

# Crear archivo de configuración
sudo nano /etc/supervisor/conf.d/horizon.conf

# Recargar y aplicar
sudo supervisorctl reread
sudo supervisorctl update

# === GESTIÓN DIARIA ===

# Ver estado de todos los procesos
sudo supervisorctl status

# Iniciar/Detener/Reiniciar
sudo supervisorctl start horizon
sudo supervisorctl stop horizon
sudo supervisorctl restart horizon

# Ver logs en tiempo real
sudo supervisorctl tail -f horizon stdout

# === DEPLOYMENT ===

# Reiniciar workers después de deployment
php artisan queue:restart
sudo supervisorctl restart horizon
sudo supervisorctl restart reverb

# === TROUBLESHOOTING ===

# Ver logs de Supervisor
sudo tail -f /var/log/supervisor/supervisord.log

# Reiniciar Supervisor completamente
sudo systemctl restart supervisor

# Verificar configuración
sudo supervisorctl reread

# Matar proceso manualmente si es necesario
sudo pkill -f "artisan horizon"
sudo supervisorctl start horizon
```

---

## Recursos Adicionales

- **Documentación oficial de Supervisor**: http://supervisord.org/
- **Laravel Queues**: https://laravel.com/docs/12.x/queues
- **Laravel Horizon**: https://laravel.com/docs/12.x/horizon
- **Laravel Reverb**: https://reverb.laravel.com

---

**Última actualización**: 2025-01-11
**Autor**: Sistema de documentación Alsernet
