# 🏗️ Arquitectura de Deployment - Laravel Reverdezcámonos

## 📊 Diagrama de Arquitectura

```
┌─────────────────────────────────────────────────────────────────────┐
│                           CLIENTE WEB                               │
│                  (Browser / Aplicación Frontend)                    │
└────────────────────────────┬────────────────────────────────────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
        ▼                    ▼                    ▼
    HTTP/HTTPS          WebSocket            API Requests
    (Puerto 80,443)     (Puerto 8080)         (Port 443)
        │                    │                    │
┌───────┴────────────────────┼────────────────────┴──────────────┐
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │              REVERSE PROXY (Apache/Nginx)               │  │
│  │  Redirige requests a PHP-FPM, Reverb, etc              │  │
│  └────────┬─────────────────────────────────────────────────┘  │
│           │                                                     │
│  ┌────────┴──────────────────────────────────────────────────┐ │
│  │                                                           │ │
│  ▼                                      ▼                    │ │
│ ┌──────────────────────┐    ┌──────────────────────┐        │ │
│ │   PHP-FPM (8.4)      │    │  REVERB WebSocket    │        │ │
│ │  - Request Handler   │    │   (puerto 8080)      │        │ │
│ │  - Web Routes        │    │ - Comunicación RT    │        │ │
│ │  - API Endpoints     │    │ - Broadcasting       │        │ │
│ │  - Controllers       │    │ - Presence Channels  │        │ │
│ └──────────┬───────────┘    └──────────┬───────────┘        │ │
│            │                           │                     │ │
└────────────┼───────────────────────────┼─────────────────────┘ │
             │                           │
        ┌────┴────────────────────────────┴──────┐
        │                                        │
        ▼                                        ▼
    ┌─────────────────┐              ┌──────────────────┐
    │  BASE DE DATOS  │              │  CACHE (Redis)   │
    │    (MySQL)      │              │  (Opcional)      │
    │                 │              │                  │
    │ - Datos App     │              │ - Sessions       │
    │ - Migrations    │              │ - Cache          │
    │ - Jobs Queue    │              │ - Broadcast      │
    └─────────────────┘              └──────────────────┘

```

## 🔄 Flujo de Procesos

```
┌────────────────────────────────────────────────────────────┐
│  PROCESOS QUE SE EJECUTAN PERMANENTEMENTE                 │
├────────────────────────────────────────────────────────────┤
│                                                            │
│  1. REVERB SERVER (systemd: reverb.service)               │
│     ├─ Escucha puerto 8080                                │
│     ├─ Acepta conexiones WebSocket                        │
│     ├─ Maneja channels públicos/privados                  │
│     ├─ Broadcasting de eventos                            │
│     └─ Reinicia automáticamente si falla                  │
│                                                            │
│  2. QUEUE WORKER (systemd: queue-worker.service)          │
│     ├─ Procesa jobs de la cola                            │
│     ├─ Envía emails                                       │
│     ├─ Ejecuta tareas en background                       │
│     ├─ Reintentos automáticos (3x)                        │
│     └─ Reinicia automáticamente si falla                  │
│                                                            │
│  3. SCHEDULER (systemd: scheduler.service)                │
│     ├─ Ejecuta cada minuto                                │
│     ├─ Correrá tareas programadas                         │
│     ├─ Limpieza automática                                │
│     └─ Reinicia automáticamente si falla                  │
│                                                            │
│  4. PHP-FPM (systemd: ya existente)                       │
│     ├─ Maneja requests HTTP/HTTPS                         │
│     ├─ Procesa rutas web                                  │
│     └─ Responde a clientes                                │
│                                                            │
│  5. APACHE (systemd: ya existente)                        │
│     ├─ Reverse proxy a PHP-FPM                            │
│     ├─ Certificados SSL/TLS                               │
│     └─ Manejo de puertos 80/443                           │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

## 🔗 Flujo de una Solicitud HTTP

```
1. Cliente Web
   │
   ├─ Request HTTP/HTTPS
   │  (ejemplo: GET /dashboard)
   │
   ▼
2. Apache (Reverse Proxy)
   │
   ├─ Recibe en puerto 80/443
   ├─ Valida certificado SSL
   ├─ Redirige a PHP-FPM
   │
   ▼
3. PHP-FPM Worker
   │
   ├─ Carga Laravel Framework
   ├─ Procesa middleware
   ├─ Enruta a controlador
   ├─ Ejecuta lógica
   ├─ Puede disparar eventos
   │
   ▼
4. Eventos (Broadcasting)
   │
   ├─ Si hay eventos, se envían a Reverb
   ├─ Reverb lo transmite a clientes WebSocket
   │
   ▼
5. Base de Datos
   │
   ├─ Recupera/guarda datos
   │
   ▼
6. Respuesta al Cliente
   │
   ├─ JSON/HTML
   │
   ▼
7. Cliente Recibe
   │
   └─ Frontend actualiza vista
```

## 🔗 Flujo de un Job en Cola

```
1. Controlador dispara Job
   │
   │ dispatch(SendEmailJob::class)
   │
   ▼
2. Se inserta en tabla `jobs`
   │
   │ (Base de datos)
   │
   ▼
3. Queue Worker detecta nuevo job
   │
   │ (proceso corriendo continuamente)
   │
   ▼
4. Ejecuta el job
   │
   │ - Lee parámetros
   │ - Ejecuta lógica
   │ - Envía email / realiza tarea
   │
   ▼
5. Marca como completado
   │
   │ Elimina de tabla `jobs`
   │
   ▼
6. Si falla
   │
   ├─ Reintenta (hasta 3 veces)
   ├─ Si sigue fallando
   │  └─ Se mueve a tabla `failed_jobs`
   │
   ▼
7. Fin
```

## ⏰ Flujo del Scheduler

```
Cada minuto:
│
├─ Servicios de sistema (systemd/cron) lanzan scheduler.service
│
├─ Script scheduler.sh se ejecuta
│
├─ Ejecuta: php artisan schedule:run
│
├─ Laravel verifica todas las tareas programadas
│
├─ Ejecuta las que correspondan al minuto actual
│  Ejemplos:
│  - Limpiar sesiones antiguas
│  - Enviar notificaciones programadas
│  - Backup de datos
│  - Sincronizar servicios externos
│
├─ Script espera 60 segundos
│
└─ Se repite...
```

## 📊 Flujo de WebSocket (Reverb)

```
1. Cliente conecta a WebSocket
   │
   │ var pusher = new Pusher(key, { ... })
   │
   ▼
2. Conexión a Reverb en puerto 8080
   │
   │ wss://manager.test:8080/app/local-key
   │
   ▼
3. Reversible valida credenciales
   │
   ├─ Verifica app_key
   ├─ Valida canal (public/private)
   │
   ▼
4. Cliente se suscribe a canales
   │
   │ pusher.subscribe('notifications')
   │ pusher.subscribe('user-123')
   │
   ▼
5. Servidor dispara eventos (Broadcasting)
   │
   │ event(new UserNotified($user))
   │
   ▼
6. Reverb recibe y transmite
   │
   │ ├─ A clientes suscritos al canal
   │ └─ En tiempo real
   │
   ▼
7. Cliente recibe actualización
   │
   │ pusher.subscribe('notifications')
   │   .bind('UserNotified', (data) => {
   │     // Actualizar UI
   │   })
   │
   └─ Interfaz se actualiza sin refresco
```

## 🛡️ Niveles de Ejecución

```
┌─────────────────────────────────────────────────────┐
│  NIVEL 1: Sistema Operativo (Ubuntu/Debian Linux)   │
│                                                     │
│  ├─ systemd (init system)                          │
│  │  ├─ Gestiona servicios                          │
│  │  ├─ Reinicia automáticamente si fallan          │
│  │  └─ Integrado en el SO                          │
│  │                                                 │
│  └─ Logs: journalctl                              │
│     └─ Persistent en /var/log/journal              │
│                                                     │
└─────────────────────────────────────────────────────┘
         ▲
         │
┌─────────┴──────────────────────────────────────────┐
│  NIVEL 2: Servicios Web & PHP                      │
│                                                    │
│  ├─ Apache2 (HTTP/HTTPS)                          │
│  ├─ PHP-FPM 8.4 (Laravel Processing)              │
│  ├─ MySQL/MariaDB (Database)                      │
│  └─ Redis (Cache - opcional)                      │
│                                                    │
│  Logs: /var/log/apache2/*                         │
│        /var/log/laravel/*                         │
│                                                    │
└─────────────────────────────────────────────────────┘
         ▲
         │
┌─────────┴──────────────────────────────────────────┐
│  NIVEL 3: Aplicación Laravel                       │
│                                                    │
│  ├─ Reverb (WebSocket)                            │
│  ├─ Queue Worker                                  │
│  ├─ Scheduler                                      │
│  └─ Controllers, Models, etc                      │
│                                                    │
│  Logs: storage/logs/laravel.log                   │
│                                                    │
└─────────────────────────────────────────────────────┘
```

## 💾 Flujo de Datos

```
Entrada HTTP Request
       │
       ├─ Validación → Middleware
       │
       ├─ Autorización → Gate/Policy
       │
       ├─ Procesamiento → Controller → Service
       │
       ├─ Persistencia → Database (MySQL)
       │
       ├─ Cache → Redis (opcional)
       │
       ├─ Eventos → Broadcasting → Reverb
       │
       ├─ Jobs en background → Queue → Worker
       │
       └─ Respuesta al cliente ← HTML/JSON
           │
           └─ WebSocket update (tiempo real)
```

## 🔄 Ciclo de Vida Completo de una Acción

```
Usuario hace clic en "Crear Notificación"
                │
                ▼
        Request POST a /notifications
                │
                ▼
        Apache recibe en puerto 443
                │
                ▼
        Apache redirige a PHP-FPM
                │
                ▼
        Laravel carga, middleware valida
                │
                ├─ Valida permisos (Gate)
                │
                ▼
        Controlador NotificationController@store
                │
                ├─ Valida datos (FormRequest)
                ├─ Crea notificación en BD
                ├─ Dispara evento BroadcastNotification
                │
                ├─ Job: SendNotificationEmail
                │     └─ Se inserta en queue (BD)
                │
                ├─ Event → Reverb
                │     └─ WebSocket a clientes suscritos
                │
                └─ Respuesta JSON al cliente
                        │
                        ▼
        Cliente recibe datos
        │
        ├─ WebSocket listener recibe en tiempo real
        │  └─ UI se actualiza sin refresco
        │
        └─ Mientras tanto...
                │
                ▼
        Queue Worker detecta SendNotificationEmail
                │
                ├─ Carga parámetros del job
                ├─ Envía email via SMTP
                ├─ Marca job como completado
                │
                └─ Si falla: reintenta
```

## 📈 Escalabilidad

```
Para sistemas con mucho tráfico:

┌─────────────────────────────────────────┐
│  Múltiples Workers de Queue            │
│                                        │
│  Queue Worker 1 (CPU: 5-10%)          │
│  Queue Worker 2 (CPU: 5-10%)          │
│  Queue Worker 3 (CPU: 5-10%)          │
│  Queue Worker 4 (CPU: 5-10%)          │
│                                        │
│  → Procesa 4x jobs en paralelo        │
└─────────────────────────────────────────┘
         │
         ├─ Configurar en supervisor
         │  numprocs=4
         │
         └─ O crear múltiples systemd units
```

---

**Última actualización**: 12 de Enero 2026
