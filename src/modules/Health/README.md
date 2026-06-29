# Módulo Health - Sistema de Monitoreo

## Descripción

Módulo de monitoreo del estado del sistema que utiliza Spatie Laravel Health para verificar servicios críticos como base de datos, caché, colas, scheduler, y espacio en disco.

## Características

### ✅ Verificaciones Automáticas

- **Environment**: Verifica el entorno de ejecución
- **Debug Mode**: Verifica que debug esté desactivado en producción
- **Database**: Conexión a base de datos
- **Cache**: Sistema de caché funcionando
- **Redis**: Estado de Redis (si está configurado)
- **Queue**: Trabajos de cola procesándose correctamente
- **Schedule**: Tareas programadas ejecutándose
- **Storage**: Directorio de almacenamiento escribible
- **Disk Space**: Espacio en disco disponible

### 🎛️ Gestión Manual desde Dashboard

El dashboard incluye 4 botones de acción para gestionar el sistema manualmente:

#### 1. Ejecutar Scheduler
- **Función**: Ejecuta `php artisan schedule:run` manualmente
- **Uso**: Cuando necesites ejecutar todas las tareas programadas inmediatamente
- **Efecto**: Actualiza el heartbeat del ScheduleCheck para marcarlo como "Ok"

#### 2. Ver Tareas Programadas
- **Función**: Lista todas las tareas del scheduler con su próxima ejecución
- **Uso**: Para verificar qué tareas están configuradas y cuándo se ejecutarán
- **Información mostrada**: Comando y tiempo hasta próxima ejecución

#### 3. Estado de la Cola
- **Función**: Muestra el estado actual del sistema de colas
- **Información mostrada**:
  - Conexión de cola configurada (database, redis, sync)
  - Workers activos (cuántos están corriendo)
  - Trabajos pendientes de procesamiento
  - Trabajos fallidos

#### 4. Procesar Cola
- **Función**: Ejecuta `php artisan queue:work --once` para procesar trabajos pendientes
- **Uso**: Procesar manualmente trabajos cuando no hay workers activos
- **Efecto**: Procesa un lote de trabajos y actualiza el QueueCheck

## Rutas API

### Endpoints Públicos (sin autenticación)

```
GET /api/health/ping          - Ping simple
GET /api/health               - Health check completo
GET /api/health/documents     - Health check específico de documentos
GET /api/health/detailed      - Información detallada (solo en modo debug)
```

### Endpoints Administrativos (requiere autenticación + rol super-admin)

```
GET  /settings/health                   - Dashboard visual
GET  /settings/health/check             - Ejecutar verificaciones (JSON)
GET  /settings/health/history           - Historial de verificaciones
POST /settings/health/schedule/run      - Ejecutar scheduler manualmente
GET  /settings/health/schedule/list     - Listar tareas programadas
GET  /settings/health/queue/status      - Estado de la cola
POST /settings/health/queue/process     - Procesar trabajos de cola
```

## Configuración de Supervisor (Recomendado para Producción)

### Generar Configuración Automáticamente

El módulo incluye un generador automático de configuración de Supervisor. Tienes 2 formas de usarlo:

#### Opción 1: Desde el Dashboard (Más Fácil)

1. Ve a `/settings/health`
2. En la sección "Configuración de Supervisor", haz clic en **"Generar configuración"**
3. El sistema mostrará las instrucciones completas de instalación
4. Haz clic en **"Descargar archivo .conf"** para obtener el archivo

#### Opción 2: Desde la Terminal

```bash
# Generar con valores por defecto (3 workers)
php artisan health:supervisor-config

# Generar con parámetros personalizados
php artisan health:supervisor-config --workers=5 --tries=3 --timeout=300

# Ver todas las opciones disponibles
php artisan health:supervisor-config --help
```

### Instalación de Supervisor

#### Ubuntu/Debian
```bash
sudo apt-get install supervisor
```

#### macOS (Homebrew)
```bash
brew install supervisor
brew services start supervisor
```

### Configurar Supervisor

Después de generar la configuración:

```bash
# 1. Copiar archivo de configuración
sudo cp modules/Health/storage/supervisor/tu-app-worker.conf /etc/supervisor/conf.d/

# 2. Recargar configuración de Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# 3. Iniciar workers
sudo supervisorctl start tu-app-worker:*

# 4. Verificar estado
sudo supervisorctl status
```

### Comandos Útiles de Supervisor

```bash
# Ver estado de todos los procesos
sudo supervisorctl status

# Reiniciar workers (después de desplegar código nuevo)
sudo supervisorctl restart tu-app-worker:*

# Detener workers
sudo supervisorctl stop tu-app-worker:*

# Ver logs en tiempo real
sudo supervisorctl tail -f tu-app-worker:* stdout
```

### Características del Archivo Generado

- ✅ **Auto-restart**: Si un worker falla, se reinicia automáticamente
- ✅ **Múltiples procesos**: Configurable (3 por defecto)
- ✅ **Logs rotativos**: Máximo 10MB con 5 backups
- ✅ **Graceful shutdown**: Espera hasta 3600s para completar jobs largos
- ✅ **Rutas absolutas**: PHP y proyecto detectados automáticamente

## Solución de Problemas

### Queue Check Fallando

**Problema**: `Queue jobs running failed`

**Soluciones**:
1. **Método Manual (Desarrollo)**: Usa el botón "Procesar Cola" en el dashboard
2. **Método CLI**: Ejecuta `php artisan queue:work --once`
3. **Método Supervisor (Producción - Recomendado)**: Usa el generador de configuración de Supervisor

**Comandos útiles**:
```bash
# Verificar estado de la cola
php artisan queue:monitor default

# Procesar trabajos manualmente
php artisan queue:work --once

# Enviar health check jobs
php artisan health:queue-check-heartbeat

# Generar configuración de Supervisor
php artisan health:supervisor-config
```

### Schedule Check Fallando

**Problema**: `The schedule did not run yet`

**Soluciones**:
1. **Método Manual**: Usa el botón "Ejecutar Scheduler" en el dashboard
2. **Método CLI**: Ejecuta `php artisan schedule:run`
3. **Producción**: Configura crontab para ejecutar el scheduler cada minuto

**Crontab requerido**:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

**Comando útil**:
```bash
# Ver lista de tareas programadas
php artisan schedule:list
```

### Optimized App Check Fallando

**Problema**: `Configs are not cached`

**Solución** (solo en producción):
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Nota**: No uses cache en desarrollo/local, dificulta el desarrollo.

### Used Disk Space Warning

**Problema**: `The disk is almost full (84% used)`

**Soluciones**:
```bash
# Limpiar logs antiguos
php artisan log:clear

# Limpiar caché
php artisan cache:clear

# Limpiar vistas compiladas
php artisan view:clear

# Limpiar backups antiguos
php artisan backup:clean
```

## Configuración

### Personalizar Checks

Edita `modules/Health/app/Providers/HealthServiceProvider.php` para:
- Agregar nuevos checks
- Modificar umbrales (ej: porcentaje de disco)
- Habilitar/deshabilitar checks según entorno

### Notificaciones

Configura en `modules/Health/config/health.php`:
- Email notifications
- Slack notifications (requiere instalación de paquete)
- Throttling de notificaciones

## Mejores Prácticas

### Desarrollo Local
- **APP_ENV=local**: Desactiva checks de producción (Queue, Schedule)
- Usa botones manuales cuando necesites probar servicios
- No configures cache de config/routes/views

### Producción
- **APP_ENV=production**: Activa todos los checks
- Configura Supervisor para queue workers
- Configura crontab para el scheduler
- Habilita notificaciones por email/Slack
- Monitorea el endpoint `/api/health` con herramientas externas

## Auto-actualización

El dashboard incluye un switch de "Auto-actualizar (30s)" que:
- Ejecuta verificaciones automáticamente cada 30 segundos
- Útil para monitoreo en tiempo real
- Se desactiva automáticamente al cerrar/recargar la página

## Historial

El módulo guarda historial de verificaciones en la base de datos:
- Retención: 5 días (configurable)
- Tabla: `health_check_result_history_items`
- Accesible vía API: `/settings/health/history?days=7`
