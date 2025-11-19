                     # 📋 DOCUMENTACIÓN DEL PROYECTO WEBADMIN

**Proyecto:** A-Álvarez Web Admin
**Framework:** Laravel 11.42
**Versión:** Production Ready
**Última Actualización:** 2025-11-17

---

## 📑 TABLA DE CONTENIDOS

1. [Introducción](#introducción)
2. [Tipo de Proyecto](#tipo-de-proyecto)
3. [Estructura de Directorios](#estructura-de-directorios)
4. [Archivos de Configuración](#archivos-de-configuración)
5. [Dependencias Principales](#dependencias-principales)
6. [Puntos de Entrada](#puntos-de-entrada)
7. [Modelos de Datos](#modelos-de-datos)
8. [Funcionalidades Principales](#funcionalidades-principales)
9. [Módulos del Sistema](#módulos-del-sistema)
10. [Base de Datos](#base-de-datos)
11. [Vistas y Frontend](#vistas-y-frontend)
12. [Integraciones Externas](#integraciones-externas)
13. [Broadcasting y Eventos](#broadcasting-y-eventos)
14. [Comandos Artisan](#comandos-artisan)
15. [Resumen Técnico](#resumen-técnico)

---

## 🎯 Introducción

**WebAdmin** es una aplicación empresarial completa construida con **Laravel 11.42** que funciona como plataforma integral para:
- Gestión de campañas de email marketing
- Sistema de retorno y devoluciones de productos
- Centro de contacto (call center)
- Gestión de inventarios
- Administración de tiendas e-commerce
- Chat en vivo y automaciones

La arquitectura está diseñada para ser **modular, escalable y multi-tenant**, con soporte para múltiples idiomas y localizaciones.

---

## 🔧 Tipo de Proyecto

### Especificaciones Técnicas

| Aspecto | Valor |
|---------|-------|
| **Framework** | Laravel 11.42 |
| **Lenguaje** | PHP 8.2+ |
| **Base de Datos** | MySQL + Oracle/PrestaShop |
| **Frontend Stack** | Blade + Tailwind CSS + Vite |
| **Broadcasting** | Pusher + Laravel Reverb |
| **Sistema de Colas** | Database driver |
| **Autenticación** | Sanctum + Sessions |
| **ORM** | Eloquent |
| **API** | REST API (Sanctum) |
| **Ambiente** | Local (APP_DEBUG=true) |
| **URL Principal** | https://webadmin.test |
| **Multi-tenant** | Sí (APP_SAAS=true) |

### Características Principales

- ✅ Aplicación web SaaS multi-tenant
- ✅ Sistema de autenticación robusto
- ✅ Chat en vivo con Pusher
- ✅ Email marketing con seguimiento
- ✅ Sistema completo de devoluciones
- ✅ Generación de códigos QR y códigos de barras
- ✅ Soporte para 6 idiomas
- ✅ API REST completa
- ✅ Broadcasting en tiempo real
- ✅ Exportación a Excel/PDF
- ✅ Auditoría de actividades
- ✅ Sistema de roles y permisos

---

## 📁 Estructura de Directorios

```
C:\Users\functionbytes\Herd\webadmin\
│
├── app/                              # Código PHP principal
│   ├── Console/
│   │   └── Commands/                 # Comandos CLI personalizados
│   ├── Conversations/                # Chatbot conversations (BotMan)
│   ├── Events/                       # Event broadcasting
│   ├── Exceptions/                   # Manejo de excepciones
│   ├── Exports/                      # Exportadores (Excel/CSV)
│   ├── Facades/                      # Facades personalizados
│   ├── Helpers/                      # Funciones utilitarias
│   ├── Http/
│   │   ├── Controllers/              # Controladores del sistema
│   │   ├── Middleware/               # Middleware personalizado
│   │   └── Requests/                 # Form requests validados
│   ├── Jobs/                         # Tareas en background
│   ├── Library/                      # Librerías personalizadas
│   ├── Mail/                         # Email templates
│   ├── Models/                       # Modelos Eloquent
│   ├── Providers/                    # Service providers
│   └── Services/                     # Lógica de negocio
│
├── bootstrap/                        # Bootstrap de la aplicación
│   └── cache/                        # Caché de autoload
│
├── config/                           # Archivos de configuración
│   ├── app.php                       # Config general
│   ├── auth.php                      # Autenticación
│   ├── database.php                  # Conexiones BD
│   ├── mail.php                      # Email
│   ├── queue.php                     # Colas
│   └── [35+ archivos más]
│
├── database/                         # BD Schema
│   ├── migrations/                   # Migraciones
│   ├── factories/                    # Model factories
│   └── seeders/                      # Data seeders
│
├── public/                           # Directorio web público
│   ├── index.php                     # Entry point
│   └── builder/                      # Assets del builder
│
├── resources/                        # Recursos frontend
│   ├── css/                          # Stylesheets (Tailwind)
│   ├── js/                           # JavaScript (Vite)
│   ├── views/                        # Blade templates
│   └── lang/                         # Traducciones
│
├── routes/                           # Definición de rutas
│   ├── web.php                       # Rutas web
│   ├── api/api.php                   # Rutas API
│   ├── administratives.php
│   ├── managers.php
│   ├── callcenters.php
│   ├── inventaries.php
│   ├── returns.php
│   ├── shops.php
│   └── channels.php
│
├── storage/                          # Almacenamiento
│   ├── app/                          # Archivos de app
│   ├── framework/                    # Cache, sessions
│   └── logs/                         # Log files
│
├── tests/                            # Tests unitarios
│
├── vendor/                           # Dependencias Composer
│
└── [Archivos de configuración]
    ├── composer.json
    ├── package.json
    ├── vite.config.js
    ├── tailwind.config.js
    ├── postcss.config.js
    ├── .env
    ├── phpunit.xml
    └── artisan

```

---

## ⚙️ Archivos de Configuración

### Configuración Principal

| Archivo | Propósito | Estado |
|---------|-----------|--------|
| **composer.json** | Dependencias PHP, scripts | ✅ |
| **package.json** | Dependencias Node.js (Vite, Axios) | ✅ |
| **.env** | Variables de entorno (BD, Mail, APIs) | ✅ |
| **vite.config.js** | Bundler frontend con HMR | ✅ |
| **tailwind.config.js** | Framework CSS | ✅ |
| **postcss.config.js** | Procesamiento de CSS | ✅ |
| **phpunit.xml** | Configuración de tests | ✅ |
| **.gitignore** | Archivos ignorados en Git | ✅ |

### Configuración Laravel (config/)

- **app.php**: Nombre app, timezone, locale, providers
- **auth.php**: Guards (web, api), providers (users, agents)
- **database.php**: 2 conexiones MySQL principales
- **mail.php**: Mailer Sendmail, from: mail@a-alvarez.com
- **queue.php**: Database queue driver
- **cache.php**: File cache driver
- **session.php**: Database session store
- **broadcasting.php**: Pusher broadcaster

---

## 📦 Dependencias Principales

### Dependencias PHP (Composer)

#### Framework & Core
```json
{
  "laravel/framework": "11.42",
  "laravel/sanctum": "4.0",
  "laravel/tinker": "2.8",
  "laravel/ui": "4.6"
}
```

#### Broadcasting & Real-time
```json
{
  "laravel/reverb": "1.4",
  "laravel/pulse": "1.4",
  "pusher/pusher-php-server": "^7.0",
  "laravel-echo": "2.1.4",
  "pusher-js": "8.4.0"
}
```

#### Funcionalidades Especializadas
```json
{
  "botman/botman": "2.8",                            // Chatbot
  "spatie/laravel-permission": "6.18",               // Roles/Permisos
  "spatie/laravel-medialibrary": "11.12",            // Gestión de medios
  "spatie/laravel-activitylog": "4.9",               // Auditoría
  "maatwebsite/excel": "3.1",                        // Excel export
  "barryvdh/laravel-dompdf": "3.1",                  // PDF generation
  "webklex/laravel-imap": "5.5"                      // IMAP
}
```

#### Códigos & Identificadores
```json
{
  "bacon/bacon-qr-code": "2.0",                      // QR codes
  "simplesoftwareio/simple-qrcode": "4.2",           // QR alt
  "picqer/php-barcode-generator": "3.2",             // Códigos de barras
  "milon/barcode": "11.0"                            // Códigos de barras alt
}
```

#### Integraciones
```json
{
  "guzzlehttp/guzzle": "7.0",                        // HTTP requests
  "deeplcom/deepl-php": "1.11",                      // Traducción DeepL
  "torann/geoip": "3.0"                              // Geolocalización
}
```

#### Otros
```json
{
  "spatie/laravel-cookie-consent": "3.2",            // GDPR
  "league/csv": "9.21",                              // CSV
  "symfony/mime": "7.2"                              // MIME types
}
```

### Dependencias Node.js (NPM)

```json
{
  "dependencies": {
    "axios": "1.6.4",
    "laravel-echo": "2.1.4",
    "pusher-js": "8.4.0"
  },
  "devDependencies": {
    "laravel-vite-plugin": "1.0.0",
    "vite": "5.0.0"
  }
}
```

---

## 🚀 Puntos de Entrada

### Entry Point Público

```
public/index.php
```

### Bootstrap de Aplicación

```
artisan (CLI command)
├── Carga: bootstrap/app.php
├── Carga: app/Providers/
└── Carga: config/
```

### Rutas Web Principales

**routes/web.php**
```php
GET     /                  → LoginController        (formulario login)
POST    /login             → AuthController         (login)
GET     /logout            → LogoutController       (logout)
GET     /home              → PagesController        (dashboard)
GET     /chatbot           → ChatbotController      (chatbot)
GET/POST /reset            → PasswordResetController (reset password)
GET     /files/{path}      → FileController         (servir archivos)
```

### Módulos de Rutas

| Módulo | Archivo | Propósito |
|--------|---------|-----------|
| **Administratives** | routes/administratives.php | Panel administrativo |
| **Managers** | routes/managers.php | Gestión centralizada |
| **Callcenters** | routes/callcenters.php | Centro de contacto |
| **Inventaries** | routes/inventaries.php | Gestión de inventarios |
| **Returns** | routes/returns.php | Sistema de devoluciones |
| **Shops** | routes/shops.php | Tiendas e-commerce |
| **API** | routes/api/api.php | REST API Sanctum |
| **Channels** | routes/channels.php | Broadcasting channels |

---

## 📊 Modelos de Datos

### Usuarios & Autenticación

```
User              → Usuario principal del sistema
├── roles()       → Roles asignados (spatie)
├── permissions() → Permisos directos
└── agents()      → Agentes de chat

Agent             → Agentes de atención
├── user()
├── conversations()
└── messages()

Role              → Roles del sistema
└── permissions() → Permisos asociados
```

### Campañas & Marketing

```
Campaign               → Campañas de email
├── maillists()       → Listas de correo
├── segments()        → Segmentación
├── webhooks()        → Webhooks integrados
└── templates()       → Templates de email

CampaignMaillist      → Asociación campañas-listas
CampaignSegment       → Segmentación de campañas
CampaignWebhook       → Webhooks de campañas

MailList              → Listas de suscriptores
├── subscribers()     → Suscriptores
└── segments()        → Segmentación

Subscriber            → Suscriptores
├── conversations()   → Chats
└── maillist()        → Listas
```

### Sistema de Devoluciones

```
ReturnRequest          → Solicitud de devolución
├── products()         → ReturnProduct (productos)
├── payments()         → ReturnPayment (pagos)
├── statuses_history   → Historial de estados
├── labels()           → ReturnLabel (etiquetas)
└── notes()            → Notas/comentarios

ReturnProduct          → Producto devuelto
├── returnRequest()    → Solicitud padre
└── barcode()          → Código de barras

ReturnPayment          → Pago de devolución
├── returnRequest()    → Solicitud padre
└── carrier()          → Transportista

ReturnStatus           → Estados disponibles
└── returnRequests()   → Solicitudes con estado

ReturnLabel            → Etiqueta de envío
└── returnRequest()    → Solicitud asociada

Carrier                → Transportistas/Mensajeros
├── pickupRequests()   → Solicitudes de recogida
└── returnPayments()   → Pagos procesados
```

### Chat & Soporte

```
Chat                   → Conversaciones
├── comments()         → ChatComment (comentarios)
├── canned_responses() → ChatCanned (respuestas)
├── subscriber()       → Suscriptor
└── agent()            → Agente

ChatComment            → Comentario en chat
├── chat()             → Chat padre
└── agent()            → Agente que comenta

ChatCanned             → Respuesta predefinida
└── chat()             → Chat asociado

Ticket                 → Ticket de soporte
├── user()             → Usuario
├── callcenter()       → Call center
└── notes()            → Notas
```

### Inventario

```
Product                → Producto
├── locations()        → Ubicaciones
├── barcodes()         → Códigos de barras
└── images()           → Imágenes

Location               → Ubicación del inventario
└── products()         → Productos en ubicación

Barcode                → Código de barras
└── product()          → Producto asociado
```

### Integración

```
CarrierPickupRequest   → Solicitud de recogida
├── carrier()          → Transportista
└── returnPayment()    → Pago asociado
```

---

## 🎯 Funcionalidades Principales

### 1. Sistema de Autenticación Robusto
- Login/Logout seguro
- Recovery de contraseña
- Sessions basadas en BD
- Sanctum para API REST

### 2. Email Marketing Completo
- Gestión de listas de suscriptores
- Campañas de email personalizadas
- Segmentación avanzada
- Webhooks para tracking
- Plantillas de email reutilizables

### 3. Centro de Contacto (Call Center)
- Chat en vivo con Pusher
- Tickets de soporte
- FAQs internas
- Sistema de devoluciones integrado
- Usuarios/roles del call center

### 4. Sistema de Devoluciones Completo
- Solicitudes de devolución
- Estados y seguimiento
- Procesamiento de pagos
- Generación de etiquetas de envío
- Integración con transportistas
- Auditoría de cambios

### 5. Gestión de Inventarios
- Control de productos
- Múltiples ubicaciones
- Códigos de barras
- Movimientos de stock

### 6. Tiendas E-commerce
- Gestión de suscriptores
- Configuración por tienda
- Checkout integrado

### 7. Chatbot Inteligente
- Conversaciones con BotMan
- Respuestas automáticas
- Escalation a agentes humanos

### 8. Administración & Auditoría
- Panel administrativo completo
- Logs de actividad
- Reportes
- Gestión de usuarios/roles

### 9. Integraciones Externas
- Oracle ERP
- PrestaShop
- Twilio (SMS)
- Firebase FCM (Push notifications)
- DeepL (Traducción automática)
- Pusher (Broadcasting)

### 10. Generación de Documentos
- Códigos QR personalizados
- Códigos de barras
- PDFs (DomPDF)
- Exportación a Excel
- Lectura de IMAP

---

## 🏗️ Módulos del Sistema

### 1. MANAGERS - Gestión Centralizada

**Ubicación:** `app/Http/Controllers/Managers/`

**Funcionalidades:**
- Gestión de campañas de email
- Automatizaciones
- Listas de suscriptores
- Segmentación avanzada
- Templates de email
- Roles y permisos
- Webhooks de campañas

**Tablas principales:**
- campaigns
- maillists
- subscribers
- campaign_segments
- campaign_webhooks

---

### 2. CALLCENTERS - Centro de Contacto

**Ubicación:** `app/Http/Controllers/Callcenters/`

**Subcategorías:**
- **Contacts** - Gestión de contactos
- **Faqs** - Preguntas frecuentes
- **Returns** - Sistema de devoluciones
- **Settings** - Configuración
- **Tickets** - Tickets de soporte
- **Users** - Usuarios del call center

**Funcionalidades:**
- Chat en vivo con agentes
- Tickets de soporte
- FAQs internas
- Devoluciones integradas
- Gestión de usuarios

**Tablas principales:**
- chats
- chat_comments
- tickets
- return_requests
- callcenter_users

---

### 3. ADMINISTRATIVES - Panel Administrativo

**Ubicación:** `app/Http/Controllers/Administratives/`

**Funcionalidades:**
- Dashboard principal
- Gestión de órdenes
- Documentos
- Reportes
- Auditoría
- Configuración general

**Tablas principales:**
- activity_log
- orders
- documents
- configurations

---

### 4. INVENTARIES - Gestión de Inventarios

**Ubicación:** `app/Http/Controllers/Inventaries/`

**Funcionalidades:**
- Gestión de productos
- Múltiples ubicaciones
- Códigos de barras
- Movimientos de stock
- Control de inventario

**Tablas principales:**
- products
- locations
- barcodes
- inventory_movements

---

### 5. SHOPS - Tiendas E-commerce

**Ubicación:** `app/Http/Controllers/Shops/`

**Funcionalidades:**
- Gestión de suscriptores
- Configuración por tienda
- Integración con checkout
- Productos y catálogos

**Tablas principales:**
- shops
- subscribers
- shop_configurations

---

## 🗄️ Base de Datos

### Conexiones Principales

#### Conexión 1 - WebAdmin (Principal)
```
Host:     localhost:3306
Database: webadmins
Usuario:  root
Tipo:     MySQL
```

#### Conexión 2 - PrestaShop (Externa)
```
Host:     192.168.1.120:3306
Database: alvarez_cristia
Usuario:  alvarez_cristia
Tipo:     MySQL
```

### Configuración de Sesiones & Almacenamiento

| Componente | Driver | Detalles |
|-----------|--------|----------|
| **Sessions** | database | Lifetime: 120 min |
| **Cache** | file | Store: database |
| **Queue** | database | Conexión: default |
| **Broadcasting** | pusher | Pusher real-time |

### Tablas Principales

#### Autenticación
- users
- agents
- roles
- permissions
- role_permission
- model_has_roles
- model_has_permissions

#### Campañas
- campaigns
- maillists
- subscribers
- campaign_segments
- campaign_webhooks
- mail_list_segments

#### Devoluciones
- return_requests
- return_products
- return_payments
- return_statuses
- return_history
- return_labels
- return_audit

#### Chat & Soporte
- chats
- chat_comments
- chat_canned
- tickets
- faqs

#### Inventario
- products
- locations
- barcodes

#### Sistema
- activity_log
- migrations
- jobs
- job_batches
- cache
- sessions

---

## 🎨 Vistas y Frontend

### Estructura de Vistas

```
resources/views/
├── administratives/        # Panel administrativo
│   ├── dashboard/
│   ├── orders/
│   └── documents/
│
├── callcenters/           # Centro de contacto
│   ├── chats/
│   ├── tickets/
│   ├── faqs/
│   ├── returns/
│   └── users/
│
├── managers/              # Gestión centralizada
│   ├── campaigns/
│   ├── maillists/
│   ├── subscribers/
│   ├── segments/
│   └── templates/
│
├── inventaries/           # Inventario
│   ├── products/
│   ├── locations/
│   └── movements/
│
├── shops/                 # Tiendas
│   └── subscribers/
│
├── mailers/               # Templates de email
│   ├── welcome/
│   ├── notifications/
│   └── returns/
│
├── layouts/               # Layouts base
│   ├── core/              # Layout principal
│   ├── automation/        # Layout automatizaciones
│   └── popup/             # Pop-up layout
│
├── auth/                  # Autenticación
│   ├── login/
│   ├── register/
│   └── reset-password/
│
├── builder/               # Builder visual
│   └── components/
│
├── elements/              # Componentes reutilizables
│   ├── forms/
│   ├── tables/
│   ├── modals/
│   └── widgets/
│
└── vendor/                # Templates third-party
    ├── pagination/
    ├── notifications/
    └── alerts/
```

### Stack Frontend

| Tecnología | Versión | Propósito |
|-----------|---------|-----------|
| **Blade** | - | Motor de templates |
| **Tailwind CSS** | 3.x | Framework CSS |
| **Vite** | 5.0 | Bundler assets |
| **Axios** | 1.6.4 | HTTP client |
| **Laravel Echo** | 2.1.4 | Real-time broadcasting |
| **Pusher JS** | 8.4.0 | WebSocket client |
| **Alpine.js** | - | Interactividad (probablemente) |

### Características del Frontend

- Responsive design con Tailwind
- Hot Module Replacement (HMR) con Vite
- Real-time updates con Echo/Pusher
- AJAX requests con Axios
- Componentes reutilizables
- Formularios validados
- Tablas dinámicas
- Modales interactivos

---

## 🔗 Integraciones Externas

### ERP & E-commerce

#### Oracle/PrestaShop
```
URL:      http://223.1.1.8:8080
Sistema:  Oracle + PrestaShop
BD:       192.168.1.120:3306
```

### Email & Comunicaciones

#### Sendmail
```
Mailer:  sendmail
From:    mail@a-alvarez.com
Timeout: 30 segundos
```

#### IMAP
```
Propósito: Lectura de emails entrantes
Driver:    laravel-imap
```

### Broadcasting Real-time

#### Pusher
```
Driver:    pusher
Uso:       Chat en vivo, notificaciones
Channels:  private.chat.*, private.user.*
```

### SMS & Push Notifications

#### Twilio
```
Propósito: Envío de SMS
Status:    Configurado
```

#### Firebase FCM
```
Propósito: Push notifications
Status:    Configurado
```

### Almacenamiento de Imágenes

#### FTP
```
Host:     imagenes.a-alvarez.com
Usuario:  imagenes_alvarez
Directorio: /mailers
Propósito: Imágenes de campañas y templates
```

### Servicios de Terceros

#### DeepL
```
Propósito: Traducción automática
Idiomas:  en, es, pt, it, de, ja
```

#### GeoIP
```
Propósito: Geolocalización por IP
```

---

## 📡 Broadcasting y Eventos

### Eventos Generados

```php
App\Events\AdminLoggedIn          // Admin inicia sesión
App\Events\AgentMessageEvent      // Agente envía mensaje
App\Events\ChatMessageEvent       // Mensaje en chat
App\Events\CampaignUpdated        // Campaña actualizada
App\Events\MailListImported       // Lista importada
App\Events\MailListImportComplete // Import completado
App\Events\ReturnCreated          // Devolución creada
App\Events\ReturnStatusChanged    // Estado de devolución cambió
App\Events\ReturnCompleted        // Devolución completada
App\Events\SubscriberCheckoutEvent // Suscriptor en checkout
App\Events\CronJobExecuted        // Cron job completado
```

### Channels (Broadcasting)

```php
// Canales privados para usuarios
private.user.{id}                 // Notificaciones de usuario
private.agent.{id}                // Notificaciones de agente
private.chat.{id}                 // Conversación de chat

// Canales de presencia (opcionales)
presence.callcenter.{id}          // Agentes en call center
presence.managers.{id}            // Managers activos
```

### Listeners (Event Handlers)

```
app/Listeners/
├── AdminLoggedInListener.php
├── AgentMessageHandler.php
├── ChatMessageHandler.php
├── CampaignUpdateHandler.php
├── MailListImportHandler.php
├── ReturnEventHandler.php
└── SubscriberCheckoutHandler.php
```

---

## 🛠️ Comandos Artisan

### Comandos Personalizados

```
php artisan
  AuditReturnRules              # Auditar reglas de devolución
  CleanOldNotifications         # Limpiar notificaciones antiguas
  CleanupOldCommunications      # Limpiar comunicaciones antiguas
  GeoIpCheck                    # Chequear IPs con GeoIP
  MergeTranslationFiles         # Fusionar archivos de traducción
  ProcessComponents             # Procesar componentes
  ProcessWarranties             # Procesar garantías
  RunHandler                    # Handler general
  SendReturnReminders           # Enviar recordatorios de devolución
  SystemCleanup                 # Limpieza general del sistema
  TestCampaign                  # Testear campaña
  UpdateTrackingStatuses        # Actualizar estados de tracking
  UpgradeTranslation            # Actualizar traducciones
  VerifySender                  # Verificar remitentes
```

### Comandos Nativos Importantes

```
# Migraciones
php artisan migrate              # Ejecutar migraciones
php artisan migrate:rollback     # Revertir migraciones

# Caché
php artisan cache:clear         # Limpiar caché
php artisan view:clear          # Limpiar vistas compiladas

# Base de datos
php artisan tinker              # REPL interactivo
php artisan db:seed             # Ejecutar seeders

# Colas
php artisan queue:work          # Procesar colas
php artisan queue:failed        # Ver trabajos fallidos

# Assets
npm run dev                      # Desarrollo con HMR
npm run build                    # Compilar para producción

# Testing
php artisan test                # Ejecutar tests
```

---

## 📈 Migraciones de Base de Datos

### Estructura de Migraciones

```
database/migrations/
├── [date]_create_users_table.php
├── [date]_create_roles_permissions_tables.php
├── [date]_create_campaigns_table.php
├── [date]_create_maillists_table.php
├── [date]_create_subscribers_table.php
├── [date]_create_return_requests_table.php
├── [date]_create_return_products_table.php
├── [date]_create_return_payments_table.php
├── [date]_create_return_statuses_table.php
├── [date]_create_return_labels_table.php
├── [date]_create_chats_table.php
├── [date]_create_tickets_table.php
├── [date]_create_products_table.php
├── [date]_create_carriers_table.php
└── [Más migraciones...]
```

### Migraciones Clave

1. **Usuarios & Autenticación**
   - users, agents, roles, permissions

2. **Sistema de Devoluciones** (Completo)
   - return_requests, return_products, return_payments
   - return_statuses, return_labels, return_audit
   - return_history

3. **Email Marketing**
   - campaigns, maillists, subscribers
   - campaign_segments, campaign_webhooks

4. **Chat & Soporte**
   - chats, chat_comments, chat_canned
   - tickets, faqs

5. **Inventario**
   - products, locations, barcodes

6. **Sistema**
   - activity_log, jobs, sessions, cache

---

## 📋 Service Providers

### Providers Principales

```
app/Providers/
├── AppServiceProvider.php        # Provider principal
├── AuthServiceProvider.php       # Autenticación
├── RouteServiceProvider.php      # Rutas
├── EventServiceProvider.php      # Eventos
├── BroadcastServiceProvider.php  # Broadcasting
└── [Providers personalizados]
```

---

## 🔐 Seguridad

### Features de Seguridad

- Autenticación con Sanctum
- CSRF protection
- Password hashing (bcrypt)
- Encriptación de datos sensibles
- Rate limiting
- CORS configurado
- Roles y permisos (Spatie)
- GDPR compliance (cookies)
- SSL certificate validation

### Autenticación

```php
// Métodos disponibles
auth()->user()          // Usuario actual
auth('api')->user()     // Usuario API
auth()->check()         // ¿Autenticado?
auth()->guest()         // ¿Invitado?
auth()->logout()        // Cerrar sesión
auth()->attempt($creds) // Intentar login
```

### Autorización

```php
// Spatie Permission
auth()->user()->can('permiso')
auth()->user()->hasRole('role')
auth()->user()->hasAnyRole(['role1', 'role2'])
$user->assignRole('role')
$user->givePermissionTo('permiso')
```

---

## 📚 Recursos Adicionales

### Idiomas Soportados

- 🇬🇧 Inglés (en)
- 🇪🇸 Español (es)
- 🇵🇹 Portugués (pt)
- 🇮🇹 Italiano (it)
- 🇩🇪 Alemán (de)
- 🇯🇵 Japonés (ja)

### Archivos de Traducción

```
resources/lang/
├── en/                  # English
├── es/                  # Español
├── pt/                  # Português
├── it/                  # Italiano
├── de/                  # Deutsch
└── ja/                  # 日本語
```

---

## 🚦 Estado Actual del Proyecto

### Cambios Pendientes

```
M bootstrap/cache/packages.php
M bootstrap/cache/services.php
?? GEMINI.md
?? public/media/6/Revisión-Analítica-a-alvarez.pdf
```

### Rama Actual

```
Branch: main
Status: Production Ready
```

### Últimos Commits

```
cbbccac  25/06/2025  Última actualización
92c81f5  03-06-2025
ddfef0e  02-05-2025
44ae9a4  30-05-2025
570297a  27-05-2025
```

---

## 🎓 Guía Rápida para Desarrolladores

### Instalación & Setup

```bash
# Clonar el repositorio
git clone <repo>

# Instalar dependencias PHP
composer install

# Instalar dependencias Node.js
npm install

# Copiar archivo de entorno
cp .env.example .env

# Generar app key
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Compilar assets (desarrollo)
npm run dev

# Iniciar servidor (si no está en Valet)
php artisan serve
```

### Comandos de Desarrollo Comunes

```bash
# Ejecutar tests
php artisan test

# Generar migraciones
php artisan make:migration nombre

# Generar modelos
php artisan make:model NombreModel -m

# Generar controladores
php artisan make:controller NombreController --resource

# Compilar assets para desarrollo
npm run dev

# Compilar assets para producción
npm run build

# Verificar integridad de la aplicación
php artisan tinker
```

### Estructura de Controladores

```php
// app/Http/Controllers/NombreController.php

namespace App\Http\Controllers;

use App\Models\NombreModel;
use Illuminate\Http\Request;

class NombreController extends Controller
{
    public function index() { }
    public function create() { }
    public function store(Request $request) { }
    public function show(NombreModel $model) { }
    public function edit(NombreModel $model) { }
    public function update(Request $request, NombreModel $model) { }
    public function destroy(NombreModel $model) { }
}
```

---

## 📞 Contacto y Soporte

**Proyecto:** A-Álvarez Web Admin
**Ambiente:** https://webadmin.test
**Desarrollador:** functionbytes
**Última Documentación:** 2025-11-17

---

## 📝 Notas Finales

Este proyecto es una **aplicación empresarial completa y profesional** con arquitectura modular, bien documentada y lista para producción. Incluye:

✅ **Sistema de devoluciones** completo y robusto
✅ **Email marketing** integrado con seguimiento
✅ **Centro de contacto** con chat en vivo
✅ **Gestión de inventarios** avanzada
✅ **API REST** completa con autenticación
✅ **Broadcasting** en tiempo real
✅ **Auditoría** de actividades
✅ **Múltiples idiomas** soportados
✅ **Integraciones** con sistemas externos
✅ **Tests** y configuración profesional

---

**Documento generado automáticamente por análisis de codebase**
**Framework:** Laravel 11.42 | **Fecha:** 2025-11-17
