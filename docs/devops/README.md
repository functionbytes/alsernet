# Documentación DevOps - Alsernet

## Índice General

Esta carpeta contiene toda la documentación relacionada con operaciones, deployment y configuración de infraestructura para el proyecto Alsernet.

---

## 📚 Guías Disponibles

### 1. [Laravel Reverb - Implementación Completa](./laravel-reverb-implementation.md)

Guía completa para implementar y configurar Laravel Reverb, el servidor WebSocket oficial de Laravel.

**Temas cubiertos:**
- ✅ Instalación y configuración de Reverb
- ✅ Creación de eventos broadcastables
- ✅ Configuración del cliente (Laravel Echo)
- ✅ Canales públicos, privados y de presencia
- ✅ Broadcasting de notificaciones
- ✅ Testing y debugging
- ✅ Integración con Supervisor para producción
- ✅ Casos de uso reales (notificaciones, chat, dashboards)

**Cuándo leer:**
- Necesitas notificaciones en tiempo real
- Quieres implementar chat o colaboración en vivo
- Deseas actualizar dashboards automáticamente
- Requieres ver usuarios conectados (presencia)

---

### 2. [Supervisor - Guía Completa](./supervisor-complete-guide.md)

Guía exhaustiva para configurar y gestionar Supervisor, el monitor de procesos para mantener tus servicios Laravel corriendo 24/7.

**Temas cubiertos:**
- ✅ Instalación de Supervisor
- ✅ Configuración para Queue Workers estándar
- ✅ Configuración para Laravel Horizon
- ✅ Configuración para Laravel Reverb
- ✅ Gestión de procesos (start, stop, restart, logs)
- ✅ Configuración passwordless sudo
- ✅ Monitoreo y rotación de logs
- ✅ Scripts de deployment
- ✅ Troubleshooting completo

**Cuándo leer:**
- Vas a poner el proyecto en producción
- Necesitas workers de queue funcionando constantemente
- Quieres que Reverb se reinicie automáticamente si falla
- Requieres gestionar múltiples procesos Laravel

---

### 3. [Configuración Sudo para Supervisor](./supervisor-sudo-setup.md)

Guía específica para configurar permisos sudo sin contraseña para que el panel web de Supervisor funcione correctamente.

**Temas cubiertos:**
- ✅ Detección automática de errores de permisos
- ✅ Configuración passwordless sudo
- ✅ Seguridad y mejores prácticas
- ✅ Verificación de configuración

**Cuándo leer:**
- El panel de Supervisor muestra errores de permisos
- Ves mensajes de "sudo: a password is required"
- Necesitas que comandos de Supervisor funcionen vía web

---

## 🚀 Flujo de Implementación Recomendado

### Paso 1: Entender los Conceptos (15 min)

1. Lee la introducción de [Laravel Reverb](./laravel-reverb-implementation.md#introducción)
2. Lee la introducción de [Supervisor](./supervisor-complete-guide.md#introducción)
3. Comprende cómo funcionan juntos

### Paso 2: Desarrollo Local (1-2 horas)

1. **Configurar Reverb localmente**:
   - Verificar archivo `.env` (ya configurado)
   - Crear evento de prueba
   - Configurar Laravel Echo en frontend
   - Probar broadcasting

   ```bash
   # Iniciar Reverb en desarrollo
   php artisan reverb:start --debug
   ```

2. **Probar notificaciones**:
   - Crear notificación broadcastable
   - Escuchar en el frontend
   - Verificar recepción en tiempo real

### Paso 3: Configurar Queues (30 min)

**Opción A: Queue Workers Estándar**
- Seguir [Configuración para Queue Workers](./supervisor-complete-guide.md#configuración-para-queue-workers)

**Opción B: Laravel Horizon (Recomendado)**
- Seguir [Configuración para Laravel Horizon](./supervisor-complete-guide.md#configuración-para-laravel-horizon)
- Acceder al dashboard: `https://manager.test/horizon`

### Paso 4: Preparar Producción (1-2 horas)

1. **Instalar Supervisor**:
   ```bash
   sudo apt-get install supervisor
   ```

2. **Configurar procesos**:
   - Crear `/etc/supervisor/conf.d/horizon.conf` (o workers)
   - Crear `/etc/supervisor/conf.d/reverb.conf`
   - Aplicar configuración:
     ```bash
     sudo supervisorctl reread
     sudo supervisorctl update
     ```

3. **Configurar permisos sudo**:
   - Seguir [Configuración Passwordless Sudo](./supervisor-complete-guide.md#configuración-passwordless-sudo)

4. **Configurar Nginx para Reverb**:
   - Agregar proxy WebSocket
   - Configurar SSL
   - Ver ejemplo en [Configuración de Nginx](./supervisor-complete-guide.md#3-configuración-de-nginx-para-reverb)

### Paso 5: Deployment (30 min)

1. **Usar script de deployment**:
   - Copiar [Script de Deployment](./supervisor-complete-guide.md#1-script-de-deployment)
   - Guardar como `scripts/deploy.sh`
   - Hacer ejecutable: `chmod +x scripts/deploy.sh`

2. **Ejecutar deployment**:
   ```bash
   ./scripts/deploy.sh
   ```

3. **Verificar estado**:
   ```bash
   sudo supervisorctl status
   ```

### Paso 6: Monitoreo Continuo (Ongoing)

1. **Revisar logs regularmente**:
   ```bash
   sudo supervisorctl tail -f horizon stdout
   sudo supervisorctl tail -f reverb stdout
   ```

2. **Monitorear Horizon Dashboard**:
   - Acceder a `/horizon`
   - Revisar métricas de throughput
   - Verificar trabajos fallidos

3. **Configurar alertas**:
   - Email cuando procesos fallan
   - Monitoring de recursos (CPU, memoria)

---

## 🎯 Casos de Uso Específicos

### Sistema de Notificaciones en Tiempo Real

**Qué necesitas:**
1. ✅ Reverb configurado y corriendo
2. ✅ Laravel Echo en el frontend
3. ✅ Notificaciones con canal `broadcast`

**Guías relacionadas:**
- [Eventos y Broadcasting](./laravel-reverb-implementation.md#eventos-y-broadcasting)
- [Configuración del Cliente](./laravel-reverb-implementation.md#configuración-del-cliente-frontend)
- [Caso de Uso: Notificaciones](./laravel-reverb-implementation.md#1-sistema-de-notificaciones-en-tiempo-real)

**Ejemplo rápido:**

Backend:
```php
User::find($userId)->notify(
    new DocumentStageAdvanced($document, 'pending', 'approved')
);
```

Frontend:
```javascript
Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
        toastr.success(notification.message);
    });
```

---

### Procesamiento de Trabajos en Background

**Qué necesitas:**
1. ✅ Horizon o Queue Workers configurados
2. ✅ Supervisor gestionando los procesos
3. ✅ Jobs implementando `ShouldQueue`

**Guías relacionadas:**
- [Configuración para Horizon](./supervisor-complete-guide.md#configuración-para-laravel-horizon)
- [Configuración para Queue Workers](./supervisor-complete-guide.md#configuración-para-queue-workers)

**Ejemplo rápido:**

Crear job:
```bash
php artisan make:job ProcessDocumentValidation
```

Despachar:
```php
ProcessDocumentValidation::dispatch($document)
    ->onQueue('high');
```

---

### Chat o Edición Colaborativa

**Qué necesitas:**
1. ✅ Reverb con canales de presencia
2. ✅ Broadcasting de eventos `toOthers()`
3. ✅ Listeners para eventos de typing, updates, etc.

**Guías relacionadas:**
- [Canal de Presencia](./laravel-reverb-implementation.md#canal-de-presencia)
- [Caso de Uso: Chat](./laravel-reverb-implementation.md#2-chat-en-tiempo-real)
- [Edición Colaborativa](./laravel-reverb-implementation.md#4-edición-colaborativa-de-documentos)

---

### Dashboards en Tiempo Real

**Qué necesitas:**
1. ✅ Reverb corriendo
2. ✅ Canal público o privado
3. ✅ Broadcasting de cambios de métricas

**Guías relacionadas:**
- [Actualización de Dashboards](./laravel-reverb-implementation.md#3-actualización-de-dashboards-en-tiempo-real)

**Ejemplo rápido:**

Backend:
```php
broadcast(new MetricsUpdated($metrics));
```

Frontend:
```javascript
Echo.channel('dashboard')
    .listen('MetricsUpdated', (e) => {
        updateChart(e.metrics);
    });
```

---

## 🛠️ Comandos de Referencia Rápida

### Reverb

```bash
# Desarrollo
php artisan reverb:start --debug

# Producción (con Supervisor)
sudo supervisorctl start reverb
sudo supervisorctl status reverb
sudo supervisorctl tail -f reverb stdout
```

### Horizon

```bash
# Manual (desarrollo)
php artisan horizon

# Con Supervisor (producción)
sudo supervisorctl start horizon
sudo supervisorctl status horizon

# Comandos de Horizon
php artisan horizon:status
php artisan horizon:pause
php artisan horizon:continue
php artisan horizon:terminate
```

### Queue Workers (sin Horizon)

```bash
# Manual
php artisan queue:work database --queue=high,default,low

# Con Supervisor
sudo supervisorctl start "laravel-worker:*"
sudo supervisorctl status "laravel-worker:*"

# Reiniciar después de deployment
php artisan queue:restart
```

### Supervisor

```bash
# Ver todos los procesos
sudo supervisorctl status

# Iniciar/Detener/Reiniciar
sudo supervisorctl start horizon
sudo supervisorctl stop horizon
sudo supervisorctl restart horizon

# Recargar configuración
sudo supervisorctl reread
sudo supervisorctl update

# Ver logs
sudo supervisorctl tail -f horizon stdout
```

---

## 🔍 Troubleshooting Rápido

### Reverb no conecta

```bash
# 1. Verificar que está corriendo
sudo supervisorctl status reverb

# 2. Verificar puerto
netstat -tuln | grep 8080

# 3. Ver logs
sudo supervisorctl tail reverb stderr

# 4. Probar manualmente
php artisan reverb:start --debug
```

### Horizon no procesa trabajos

```bash
# 1. Ver estado
php artisan horizon:status

# 2. Reiniciar
php artisan horizon:terminate
sudo supervisorctl restart horizon

# 3. Ver logs
sudo supervisorctl tail -f horizon stdout
```

### Workers detenidos

```bash
# 1. Ver estado de Supervisor
sudo supervisorctl status

# 2. Ver logs de error
sudo supervisorctl tail laravel-worker stderr

# 3. Probar comando manualmente
sudo -u www-data php artisan queue:work database --once

# 4. Reiniciar
sudo supervisorctl restart "laravel-worker:*"
```

---

## 📊 Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────┐
│                      USUARIOS FINALES                        │
└───────────────────────┬─────────────────────────────────────┘
                        │
                ┌───────▼────────┐
                │   Navegador     │
                │  (Laravel Echo) │
                └───────┬────────┘
                        │
        ┌───────────────┼───────────────┐
        │               │               │
┌───────▼────────┐ ┌───▼────────┐ ┌───▼────────┐
│  HTTP/HTTPS    │ │ WebSocket  │ │   CSRF     │
│   (Laravel)    │ │  (Reverb)  │ │   Token    │
└───────┬────────┘ └───┬────────┘ └────────────┘
        │              │
        │         ┌────▼─────────────────────┐
        │         │   Laravel Reverb         │
        │         │   (Port 8080)            │
        │         │   Supervisor: reverb     │
        │         └────┬─────────────────────┘
        │              │
┌───────▼──────────────▼───────────────────────┐
│           Aplicación Laravel                  │
│  ┌────────────────────────────────────┐      │
│  │  Broadcasting                      │      │
│  │  - Events                          │      │
│  │  - Notifications                   │      │
│  │  - Channels (routes/channels.php) │      │
│  └────────────────────────────────────┘      │
│                                               │
│  ┌────────────────────────────────────┐      │
│  │  Queue System                      │      │
│  │  - Jobs                            │      │
│  │  - Notifications                   │      │
│  │  - Events                          │      │
│  └────────────────────────────────────┘      │
└───────┬───────────────────────────────────────┘
        │
    ┌───▼────────────────────────────┐
    │   Queue Processing             │
    │                                │
    │  ┌──────────────────────────┐  │
    │  │  Laravel Horizon         │  │
    │  │  (Dashboard + Workers)   │  │
    │  │  Supervisor: horizon     │  │
    │  └──────────────────────────┘  │
    │          O                     │
    │  ┌──────────────────────────┐  │
    │  │  Queue Workers           │  │
    │  │  (8 workers)             │  │
    │  │  Supervisor: workers     │  │
    │  └──────────────────────────┘  │
    └────────┬───────────────────────┘
             │
    ┌────────▼────────┐
    │   Base de Datos │
    │   - MySQL       │
    │   - Redis       │
    └─────────────────┘

         SUPERVISOR
    ┌─────────────────────┐
    │  Process Monitor    │
    │  ┌───────────────┐  │
    │  │ horizon       │  │
    │  │ reverb        │  │
    │  │ workers (8)   │  │
    │  └───────────────┘  │
    │  Auto-restart on    │
    │  failure            │
    └─────────────────────┘
```

---

## 📝 Checklist de Implementación

### ✅ Desarrollo Local

- [ ] Reverb configurado en `.env`
- [ ] Laravel Echo instalado (`npm install laravel-echo pusher-js`)
- [ ] Echo configurado en `resources/js/bootstrap.js`
- [ ] Evento de prueba creado y funcionando
- [ ] Canal de autenticación configurado en `routes/channels.php`
- [ ] Notificación de prueba recibida en frontend

### ✅ Queues (Elige uno)

**Opción A: Horizon**
- [ ] `composer require laravel/horizon`
- [ ] `php artisan horizon:install`
- [ ] Configurado `config/horizon.php`
- [ ] Dashboard accesible en `/horizon`
- [ ] Autenticación configurada

**Opción B: Workers Estándar**
- [ ] Configurado connection en `config/queue.php`
- [ ] Probado `php artisan queue:work` manualmente

### ✅ Supervisor en Producción

- [ ] Supervisor instalado (`sudo apt-get install supervisor`)
- [ ] Archivo `horizon.conf` o `laravel-worker.conf` creado
- [ ] Archivo `reverb.conf` creado
- [ ] Permisos sudo configurados (passwordless)
- [ ] Procesos iniciados: `sudo supervisorctl start all`
- [ ] Estado verificado: `sudo supervisorctl status`

### ✅ Nginx/Apache

- [ ] Proxy WebSocket configurado para Reverb
- [ ] Certificado SSL válido
- [ ] Puerto 8080 abierto en firewall
- [ ] Probado conexión wss://

### ✅ Deployment

- [ ] Script de deployment creado (`scripts/deploy.sh`)
- [ ] Script ejecutable (`chmod +x`)
- [ ] Probado deployment completo
- [ ] Verificado que procesos se reinician correctamente

### ✅ Monitoreo

- [ ] Logs de Supervisor configurados
- [ ] Logrotate configurado
- [ ] Dashboard de Horizon accesible (si aplica)
- [ ] Alertas de email configuradas (opcional)

---

## 🔗 Enlaces Útiles

### Documentación Oficial

- [Laravel Broadcasting](https://laravel.com/docs/12.x/broadcasting)
- [Laravel Reverb](https://reverb.laravel.com)
- [Laravel Horizon](https://laravel.com/docs/12.x/horizon)
- [Laravel Queues](https://laravel.com/docs/12.x/queues)
- [Supervisor](http://supervisord.org/)

### Herramientas

- [Laravel Echo](https://github.com/laravel/echo)
- [Pusher JS](https://github.com/pusher/pusher-js)
- [Laravel Telescope](https://laravel.com/docs/12.x/telescope) - Debugging
- [Laravel Pulse](https://laravel.com/docs/12.x/pulse) - Performance monitoring

---

## 💡 Tips y Mejores Prácticas

### Performance

1. **Reverb**
   - Usar Redis para caché de autenticación
   - Configurar heartbeat apropiado
   - Usar canales privados solo cuando sea necesario

2. **Horizon**
   - Configurar auto-scaling para carga variable
   - Usar balance `simple` para carga constante
   - Monitorear wait time y throughput

3. **Supervisor**
   - No más de 2x número de CPUs para workers
   - Configurar `max-time` para evitar memory leaks
   - Usar `stopwaitsecs` > timeout más largo

### Seguridad

1. **Reverb**
   - SIEMPRE usar wss:// (SSL) en producción
   - Validar autenticación en `routes/channels.php`
   - No exponer información sensible en eventos

2. **Horizon**
   - Proteger dashboard con autenticación
   - Usar roles para acceso (`hasRole('admin')`)
   - No exponer en dominios públicos sin auth

3. **Supervisor**
   - Usar passwordless sudo solo para comandos específicos
   - Permisos 0440 en archivos sudoers
   - Usuario web con privilegios mínimos

### Debugging

1. **Reverb**
   - Habilitar `--debug` en desarrollo
   - Usar `window.Pusher.logToConsole = true`
   - Verificar CSRF token válido

2. **Horizon**
   - Revisar dashboard de métricas
   - Verificar failed jobs
   - Usar `horizon:status` para diagnóstico

3. **Supervisor**
   - `supervisorctl tail -f` para logs en vivo
   - Probar comandos manualmente con `sudo -u www-data`
   - Verificar permisos de archivos y directorios

---

**Última actualización**: 2025-01-11
**Mantenido por**: Equipo DevOps Alsernet
**Contacto**: Para dudas o problemas, revisar la sección Troubleshooting de cada guía
