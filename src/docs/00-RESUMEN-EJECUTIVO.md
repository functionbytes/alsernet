# INOQUALAB - Resumen Ejecutivo del Proyecto Enterprise

**Fecha de Análisis:** 8 de febrero de 2026
**Versión:** Laravel 12 + React 19 + TypeScript
**Arquitectura:** Modular Enterprise (22+ módulos independientes)

---

## 🎯 Descripción General

**INOQUALAB** es una **aplicación enterprise modular** que implementa un sistema completo de gestión de atenciones (PQRSF - Peticiones, Quejas, Reclamos, Sugerencias, Felicitaciones) con capacidades avanzadas de:

- ✅ **Arquitectura Modular** con 22+ módulos independientes
- ✅ **Frontend Moderno** React 19 + TypeScript + Vite 7
- ✅ **Backend Robusto** Laravel 12 con PHP 8.4
- ✅ **Real-time** con Laravel Reverb (WebSockets nativos)
- ✅ **Monitoreo Enterprise** Pulse, Telescope, Health Checks
- ✅ **Multi-Database** MySQL + Prestashop + SQLite
- ✅ **API REST** completa con 100+ endpoints
- ✅ **Sistema de Roles** granular con Spatie Permission
- ✅ **Media Management** con Spatie MediaLibrary
- ✅ **Activity Logging** completo con auditoría
- ✅ **Backups Automáticos** programables
- ✅ **Analytics** integrado con Google Analytics

---

## 🏗️ Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────┐
│           FRONTEND (React 19 + TypeScript)              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │   Zustand    │  │ React Query  │  │React Router 7│ │
│  │ (State Mgmt) │  │(Data Caching)│  │  (Routing)   │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
│  ┌────────────────────────────────────────────────────┐ │
│  │        Laravel Echo + Reverb (WebSockets)         │ │
│  └────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
                         ↕️ Axios HTTP
┌─────────────────────────────────────────────────────────┐
│         BACKEND (Laravel 12 - Arquitectura Modular)     │
│  ┌─────────────┐  ┌─────────────┐  ┌──────────────┐   │
│  │  Attention  │  │    Role     │  │     Auth     │   │
│  │  (PQRSF)    │  │  (Permisos) │  │ (Sanctum)    │   │
│  └─────────────┘  └─────────────┘  └──────────────┘   │
│  ┌─────────────┐  ┌─────────────┐  ┌──────────────┐   │
│  │   Mailer    │  │ Notification│  │    Media     │   │
│  │  (Emails)   │  │  (Alerts)   │  │   (Files)    │   │
│  └─────────────┘  └─────────────┘  └──────────────┘   │
│  ┌─────────────┐  ┌─────────────┐  ┌──────────────┐   │
│  │    Pulse    │  │   Health    │  │   Backup     │   │
│  │ (Monitoring)│  │  (Checks)   │  │  (Auto BKP)  │   │
│  └─────────────┘  └─────────────┘  └──────────────┘   │
│                 ... 15+ módulos más                     │
└─────────────────────────────────────────────────────────┘
                         ↕️
┌─────────────────────────────────────────────────────────┐
│              BASES DE DATOS (Multi-DB)                  │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────┐  │
│  │    MySQL     │  │  Prestashop  │  │   SQLite    │  │
│  │  (Principal) │  │ (Ecommerce)  │  │   (Cache)   │  │
│  └──────────────┘  └──────────────┘  └─────────────┘  │
└─────────────────────────────────────────────────────────┘
```

---

## 📊 Estadísticas del Proyecto

### Backend (Laravel 12)
```
Módulos:          22 activos + 7 deshabilitados
Modelos:          52 Eloquent models
Controladores:    108 controllers
Rutas API:        100+ endpoints REST
Middleware:       15+ personalizados
Jobs:             14 asíncronos
Eventos:          2 broadcasted
Servicios:        30+ especializados
Traits:           22 reutilizables
Helpers:          12 globales
```

### Frontend (React 19)
```
Stack:            React 19 + TypeScript 5 + Vite 7
State:            Zustand 5
Data:             React Query 5
Routing:          React Router 7
Styling:          Tailwind CSS 4
Animations:       Framer Motion 12
Forms:            React Hook Form 7 + Zod 3
```

### Base de Datos
```
Tablas:           80+ tablas
Migraciones:      100+ migrations
Seeders:          10+ seeders
Factories:        5+ factories
Conexiones:       3 bases de datos
```

### Código
```
PHP:              ~25,000 líneas
TypeScript:       ~3,000 líneas (preparado)
JavaScript:       ~2,000 líneas
Vistas Blade:     50+ vistas
Módulos:          22 independientes
```

---

## 🎯 Módulos Principales (Top 10)

### 1. **Attention** (PQRSF + Documentos) - 40% del proyecto
**Propósito:** Sistema completo de gestión de atenciones al cliente

**Características:**
- 34 modelos Eloquent
- Flujo de validación multi-etapa
- Sistema SLA con escalada automática
- Validación dual (administrativa + técnica)
- Gestión de documentos con Spatie Media
- 11 tipos de emails automatizados
- API REST con 100+ endpoints
- Broadcasting en tiempo real
- Trazabilidad completa

**Casos de uso:**
- Recepción de PQRSF de clientes
- Validación en múltiples etapas
- Aprobación/rechazo con notificaciones
- Gestión de documentos adjuntos
- Monitoreo de tiempos SLA

---

### 2. **Role** (Control de Acceso) - 10% del proyecto
**Propósito:** Sistema RBAC completo con Spatie Permission

**Características:**
- Roles personalizables
- Permisos granulares por módulo y acción
- Middleware de autorización automático
- Matrix de permisos por rol
- Protección de roles del sistema
- API REST para gestión

**Roles predefinidos:**
- super-admin (acceso total)
- manager (gestión de validaciones)
- administrative (personal administrativo)
- weapons (área técnica)
- accounting (contabilidad)
- customer (clientes)

---

### 3. **Mailer** (Sistema de Emails) - 8% del proyecto
**Propósito:** Gestión avanzada de plantillas y envío de emails

**Características:**
- Templates con variables dinámicas
- Editor Twig integrado
- Endpoints SMTP configurables
- Variables personalizadas por contexto
- Layouts reutilizables
- Caché de templates
- Tracking de envíos

---

### 4. **Media** (Gestión de Archivos) - 8% del proyecto
**Propósito:** File manager con Spatie MediaLibrary

**Características:**
- Upload de archivos (100MB max)
- Gestión de carpetas jerárquica
- Conversiones automáticas de imágenes
- Optimización de imágenes
- Soft delete y papelera
- Favoritos
- Búsqueda y filtrado
- Múltiples discos de almacenamiento

---

### 5. **Pulse** (Monitoreo APM) - 5% del proyecto
**Propósito:** Monitoreo de rendimiento en tiempo real

**Características:**
- Métricas en tiempo real
- Detección de queries lentas
- Tracking de excepciones
- Monitoreo de colas
- CPU y memoria del servidor
- Dashboard integrado
- Almacenamiento en Redis/DB

---

### 6. **Notification** (Notificaciones) - 5% del proyecto
**Propósito:** Sistema de notificaciones multi-canal

**Características:**
- Canales: Database, Email, SMS, Push
- Preferencias por usuario
- Tokens para push notifications
- Notificaciones en tiempo real
- Templates personalizables

---

### 7. **Auth** (Autenticación) - 5% del proyecto
**Propósito:** Sistema de autenticación completo

**Características:**
- Login/logout
- Registro de usuarios
- Recuperación de contraseña
- Verificación de email
- Sesiones con Laravel Sanctum
- Middleware de autenticación

---

### 8. **Backup** (Backups Automáticos) - 3% del proyecto
**Propósito:** Copias de seguridad programables

**Características:**
- Backup selectivo de archivos
- Dump de base de datos
- Programación flexible (daily, weekly, monthly)
- Retención automática
- Notificaciones de estado
- Encriptación AES-256

---

### 9. **Health** (Health Checks) - 3% del proyecto
**Propósito:** Monitoreo de salud del sistema

**Características:**
- Checks customizables
- Integración con Oh Dear
- Notificaciones de fallos
- Historial de checks
- Dashboard de estado

---

### 10. **Mailrelay** (Email Marketing) - 10% del proyecto
**Propósito:** Campañas de email masivo con API Mailrelay

**Características:**
- Integración completa con Mailrelay
- Gestión de campañas
- Listas de suscriptores
- Sincronización automática
- Estadísticas de campañas
- Import/export de contactos
- Templates de email
- A/B testing

---

## 💻 Stack Tecnológico Completo

### Backend
```yaml
Framework:        Laravel 12.0
PHP:              8.4+
ORM:              Eloquent
Auth:             Laravel Sanctum 4.0
Queue:            Database (Redis opcional)
Cache:            Redis (File opcional)
Sessions:         Database
Broadcasting:     Laravel Reverb (WebSockets nativos)
Search:           SQL manual (Scout preparado)
```

### Frontend
```yaml
Framework:        React 19.0
Language:         TypeScript 5.0
Build:            Vite 7.0.7
State:            Zustand 5.0
Data:             React Query 5.0 (@tanstack)
Routing:          React Router 7.0
Forms:            React Hook Form 7.0 + Zod 3.0
Styling:          Tailwind CSS 4.0
Animations:       Framer Motion 12.23
HTTP:             Axios 1.11
WebSockets:       Laravel Echo 2.2 + Pusher.js 8.4
```

### Base de Datos
```yaml
Primary:          MySQL 8.0+
Secondary:        PostgreSQL (opcional)
Integration:      Prestashop DB
Cache:            SQLite (file-based)
Connections:      3 configuradas
```

### Servicios
```yaml
Monitoring:       Laravel Pulse 1.x
Debugging:        Laravel Telescope (ready)
Health:           Spatie Laravel Health
Media:            Spatie MediaLibrary v10/v11
Activity:         Spatie ActivityLog
Backup:           Spatie Laravel Backup
Permission:       Spatie Laravel Permission 6.24
```

### DevOps
```yaml
Server:           PHP-FPM + Nginx
Queue Worker:     Supervisor
Scheduler:        Laravel Cron
Logs:             Laravel Pail (real-time)
Deployment:       Composer + NPM scripts
Monitoring:       Cloudflare Tunnel (aerni/cloudflared)
```

---

## 🔑 Características Enterprise

### 1. Arquitectura Modular (nwidart/laravel-modules)
- **22 módulos independientes** con sus propios:
  - Models, Controllers, Routes
  - Migrations, Seeders, Factories
  - Views, Assets, Config
  - Service Providers
  - Tests

### 2. Sistema de Permisos Granular
- **RBAC** completo con Spatie Permission
- Permisos por módulo y acción
- Middleware de autorización automático
- Matrix de permisos visual
- Roles protegidos del sistema

### 3. Real-time con WebSockets
- **Laravel Reverb** (WebSockets nativos)
- Fallback a Pusher
- Broadcasting de eventos
- Canales públicos y privados
- Echo client en React

### 4. Multi-Database
- Conexión principal MySQL
- Integración con Prestashop
- SQLite para cache
- Soporte PostgreSQL
- Queries cross-database

### 5. API REST Completa
- **100+ endpoints** documentados
- Autenticación por sesión
- Rate limiting (60 req/min)
- CORS configurado
- Respuestas JSON estandarizadas

### 6. Sistema de Colas
- Jobs asíncronos (14 implementados)
- Queue workers con Supervisor
- Failed jobs handling
- Rate limiting por job
- Multiple queue connections

### 7. Activity Logging
- **Spatie ActivityLog** integrado
- Auditoría completa de cambios
- Registro por usuario
- Propiedades antes/después
- Retención de 365 días

### 8. Media Management
- **Spatie MediaLibrary**
- File manager completo
- Conversiones automáticas
- Optimización de imágenes
- Múltiples discos

### 9. Monitoreo APM
- **Laravel Pulse** en tiempo real
- Detección de queries lentas
- Tracking de excepciones
- Monitoreo de servidor
- Dashboard integrado

### 10. Backups Automáticos
- **Spatie Laravel Backup**
- Programación flexible
- Retención automática
- Notificaciones
- Encriptación AES-256

---

## 📂 Estructura del Proyecto

```
inoqualab/
├── app/                           # Core de la aplicación
│   ├── Models/                    # Modelos base
│   ├── Http/                      # HTTP layer
│   ├── Providers/                 # Service Providers
│   ├── Helpers/                   # Helpers globales
│   └── Traits/                    # Traits reutilizables
│
├── modules/                       # 22 módulos independientes
│   ├── Attention/                 # PQRSF (34 modelos, 15+ controllers)
│   ├── Role/                      # RBAC con Spatie
│   ├── Auth/                      # Autenticación
│   ├── User/                      # Gestión de usuarios
│   ├── Notification/              # Notificaciones
│   ├── Mailer/                    # Sistema de emails
│   ├── Mailrelay/                 # Email marketing
│   ├── Media/                     # File manager
│   ├── Pulse/                     # Monitoreo APM
│   ├── Health/                    # Health checks
│   ├── Backup/                    # Backups automáticos
│   ├── Analytics/                 # Google Analytics
│   ├── Core/                      # Servicios centrales
│   ├── Database/                  # Gestión de BD
│   ├── Storage/                   # Almacenamiento
│   ├── Theme/                     # Temas visuales
│   ├── System/                    # Info del sistema
│   ├── Queue/                     # Gestión de colas
│   ├── MailsSettings/             # Config de emails
│   └── Modules/                   # Gestor de módulos
│
├── config/                        # Configuración
│   ├── modules.php                # Config de módulos
│   ├── auth.php                   # Autenticación
│   ├── broadcasting.php           # WebSockets
│   ├── database.php               # Multi-DB
│   ├── permission.php             # Spatie Permission
│   ├── media-library.php          # Spatie Media
│   ├── activitylog.php            # Spatie Activity
│   ├── backup.php                 # Spatie Backup
│   ├── health.php                 # Spatie Health
│   └── pulse.php                  # Laravel Pulse
│
├── database/
│   ├── migrations/                # Migraciones base
│   ├── seeders/                   # Seeders
│   └── factories/                 # Factories
│
├── resources/
│   ├── js/                        # React + TypeScript
│   │   ├── app.js                 # Entry point
│   │   ├── bootstrap.js           # Echo + Axios config
│   │   └── helpdesk/              # React app (preparado)
│   ├── css/
│   │   └── app.css                # Tailwind CSS 4
│   ├── views/                     # Blade templates
│   └── lang/                      # Traducciones
│
├── routes/
│   ├── web.php                    # Rutas web
│   ├── api.php                    # API base
│   ├── channels.php               # Broadcasting
│   └── console.php                # Comandos Artisan
│
├── public/
│   └── build/                     # Assets compilados (Vite)
│
├── storage/
│   ├── app/                       # Archivos de la app
│   ├── framework/                 # Cache, sessions, views
│   └── logs/                      # Logs de aplicación
│
├── tests/                         # Tests (PHPUnit)
│   ├── Feature/
│   └── Unit/
│
├── modules_statuses.json          # Estado de módulos
├── composer.json                  # Dependencias PHP
├── package.json                   # Dependencias JS
├── vite.config.js                 # Config de Vite
├── tsconfig.json                  # Config de TypeScript
└── .env                           # Variables de entorno
```

---

## 🚀 Comandos de Desarrollo

```bash
# Setup inicial
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Desarrollo (4 procesos en paralelo)
composer dev
# Ejecuta:
#   - php artisan serve (servidor)
#   - php artisan queue:listen (colas)
#   - php artisan pail (logs real-time)
#   - npm run dev (Vite hot reload)

# Comandos individuales
php artisan serve              # Servidor local
php artisan queue:listen       # Worker de colas
php artisan pail               # Logs en tiempo real
npm run dev                    # Vite dev server
npm run build                  # Build de producción

# Testing
php artisan test               # Ejecutar tests
composer test                  # Alias

# Módulos
php artisan module:list        # Listar módulos
php artisan module:enable      # Habilitar módulo
php artisan module:disable     # Deshabilitar módulo

# Backups
php artisan backup:run         # Crear backup manual
php artisan backup:clean       # Limpiar backups antiguos
php artisan backup:list        # Listar backups

# Cache
php artisan optimize           # Optimizar aplicación
php artisan optimize:clear     # Limpiar caches
php artisan config:cache       # Cache de config
php artisan route:cache        # Cache de rutas
php artisan view:cache         # Cache de vistas
```

---

## 📊 Flujos Principales

### Flujo de PQRSF (Atención al Cliente)

```
1. CLIENTE CREA PQRSF
   ├─ POST /api/pqrsf/submit
   ├─ Genera UID único
   ├─ Estado inicial: "Pendiente"
   ├─ Dispara evento AttentionCreated (WebSocket)
   ├─ Envía email de confirmación
   └─ Retorna número de radicado

2. CLIENTE CARGA DOCUMENTOS
   ├─ POST /api/pqrsf/{radicado}/files
   ├─ Almacena en Media (Spatie)
   ├─ Marca como "con documentos"
   ├─ Envía email de confirmación de carga
   └─ Notifica a validadores

3. VALIDACIÓN ADMINISTRATIVA (Etapa 1)
   ├─ Admin accede a /admin/documents/{uid}
   ├─ Revisa documentos
   ├─ Aprueba → POST /api/{uid}/approve-stage
   │  ├─ Transición a siguiente etapa
   │  ├─ Envía email de aprobación
   │  └─ Registra en historial
   └─ O rechaza → POST /api/{uid}/reject-stage
      ├─ Vuelve a etapa anterior
      ├─ Envía email de rechazo con razón
      └─ Registra en historial

4. VALIDACIÓN TÉCNICA (Etapa 2)
   ├─ Similar a etapa 1
   ├─ Validadores del grupo técnico
   └─ Transición a aprobado o rechazo

5. FINALIZACIÓN
   ├─ Estado final: "Aprobado" o "Rechazado"
   ├─ Email de conclusión al cliente
   ├─ Registro completo en Activity Log
   └─ Métricas de tiempo en Pulse

MONITOREO SLA (Job periódico)
   ├─ CheckSlaBreachesJob cada 5 minutos
   ├─ Detecta tiempo excedido
   ├─ Crea registro SlaBreach
   ├─ Si enable_escalation: notifica a supervisores
   └─ Registra para auditoría
```

---

## 🔐 Seguridad

### Autenticación y Autorización
- ✅ Laravel Sanctum para API
- ✅ Sesiones seguras con encriptación
- ✅ CSRF protection en formularios
- ✅ Password hashing con Bcrypt
- ✅ Rate limiting (60 req/min)
- ✅ RBAC con Spatie Permission
- ✅ Middleware de autorización automático

### Protecciones Adicionales
- ✅ CORS configurado
- ✅ Validación de inputs
- ✅ Sanitización de HTML
- ✅ Encriptación de cookies
- ✅ HTTPS enforcement (producción)
- ✅ Activity logging completo
- ✅ Failed login attempts tracking

---

## 📈 Métricas y Monitoreo

### Laravel Pulse (APM)
- Queries lentas (threshold: configurable)
- Excepciones capturadas
- Jobs lentos (>1000ms)
- Requests lentas
- CPU y memoria del servidor
- Dashboard en tiempo real

### Health Checks
- Estado de base de datos
- Estado de cache
- Estado de queue workers
- Estado de storage
- Integración con Oh Dear
- Notificaciones automáticas

### Activity Log
- Todos los cambios registrados
- Usuario y timestamp
- Propiedades antes/después
- Retención: 365 días
- Exportable

---

## 🌐 Integraciones Externas

| Servicio | Módulo | Propósito |
|----------|--------|-----------|
| **Google Analytics** | Analytics | Métricas de tráfico |
| **Mailrelay** | Mailrelay | Email marketing masivo |
| **Prestashop** | Database | Integración ecommerce |
| **Oh Dear** | Health | Uptime monitoring |
| **Cloudflare Tunnel** | System | Exposición segura |
| **SMTP** | Mailer | Envío de emails |
| **Pusher** | Broadcasting | WebSockets fallback |

---

## 📚 Documentación Adicional

1. **[01-ARQUITECTURA-MODULAR.md](01-ARQUITECTURA-MODULAR.md)** - Detalle de los 22 módulos
2. **[02-FRONTEND-REACT.md](02-FRONTEND-REACT.md)** - Stack React + TypeScript
3. **[03-BACKEND-API.md](03-BACKEND-API.md)** - API REST y modelos
4. **[04-SERVICIOS-ENTERPRISE.md](04-SERVICIOS-ENTERPRISE.md)** - Pulse, Health, Backup
5. **[05-SISTEMA-PQRSF.md](05-SISTEMA-PQRSF.md)** - Módulo Attention detallado
6. **[10-GUIA-DESARROLLO.md](10-GUIA-DESARROLLO.md)** - Guía para desarrolladores

---

## 👥 Roles del Sistema

| Rol | Acceso | Funcionalidades |
|-----|--------|-----------------|
| **super-admin** | Total | CRUD completo, gestión de módulos, configuración |
| **manager** | Gestión | Validación de documentos, reportes, usuarios |
| **administrative** | Validación | Validación administrativa, revisión de documentos |
| **weapons** | Validación | Validación técnica (área de armas) |
| **accounting** | Finanzas | Validación contable |
| **customer** | Limitado | Solo frontend, crear PQRSF |

---

## 🎓 Conceptos Clave

### Arquitectura Modular
- Cada módulo es independiente
- Service Providers por módulo
- Autoload PSR-4 por módulo
- Activación/desactivación dinámica
- Merge de composer.json automático

### Trait Mediable (Spatie)
- Sistema polimórfico de archivos
- 13+ modelos usan este trait
- Conversiones automáticas
- Optimización de imágenes

### Broadcasting Real-time
- Laravel Reverb (WebSockets nativos)
- Canales públicos y privados
- Eventos broadcasteados
- Echo client en React
- Pusher como fallback

### Sistema de Permisos
- Formato: `{module}.{action}`
- Ejemplos: `documents.view`, `users.update`
- Middleware automático
- Matrix de permisos visual

---

**Proyecto analizado por:** Claude Sonnet 4.5
**Herramienta:** Claude Code (Análisis en Paralelo con 4 Agentes)
**Tiempo de análisis:** ~10 minutos
**Total de archivos analizados:** 500+
**Líneas de código analizadas:** ~30,000+

---

*Para más detalles, consulte los documentos individuales en la carpeta `/docs`.*
