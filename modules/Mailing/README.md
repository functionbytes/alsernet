# Mailrelay Module

Módulo completo de email marketing y automatización con integración Mailrelay API para la plataforma Alsernet.

## Características Principales

### 📧 Gestión de Email Marketing
- **Campañas de Email**: Creación, programación y envío de campañas masivas
- **Editor Rich Text**: Editor Quill.js con plantillas personalizables
- **A/B Testing**: Creación y gestión de pruebas A/B
- **Campañas RSS**: Generación automática de campañas desde feeds RSS
- **Análisis de Campañas**: Métricas detalladas (aperturas, clics, bounces, unsubscribes)

### 👥 Gestión de Suscriptores
- **CRUD Completo**: Gestión total de suscriptores
- **Listas y Grupos**: Organización por listas y segmentos
- **Campos Personalizados**: Soporte para campos custom
- **Sincronización Mailrelay**: Sync bidireccional automático
- **Estados**: Active, Pending, Unsubscribed, Bounced, Banned

### ✅ Validación de Emails Multi-Nivel
- **Nivel 1 - Sintaxis**: Validación RFC 5322 (gratis, instantáneo)
- **Nivel 2 - Email Utilities**: Detección de emails desechables y de rol (gratis)
- **Nivel 3 - DNS**: Verificación de registros MX/A (gratis, cacheado 24h)
- **Nivel 4 - SMTP**: Verificación de buzón real (gratis, más lento)
- **Nivel 5 - APIs Externas**: ZeroBounce, NeverBounce, Hunter.io (pago, cacheado)
- **Sistema de Scoring**: Puntuación 0-100 con umbrales configurables
- **Validación por Lotes**: Procesamiento asíncrono de miles de emails

### 📥 Importación Masiva
- **Formatos Soportados**: Excel (.xlsx, .xls) y CSV
- **Procesamiento Asíncrono**: Jobs en cola con seguimiento de progreso
- **Auto-detección**: Detección automática de columnas email/nombre
- **Validación Opcional**: Validación durante importación
- **Reportes Detallados**: Estadísticas completas de importación

### 📨 Newsletter & Subscripciones
- **API Pública**: Endpoints para suscripción/desuscripción
- **Formularios Web**: Integración con widget de suscripción
- **Doble Opt-in**: Confirmación por email (configurable)
- **Prevención de Spam**: Bloqueo de emails desechables

### 📱 SMS Marketing
- **Campañas SMS**: Envío masivo de SMS
- **SMS Transaccionales**: Envío individual programable
- **Seguimiento**: Tracking de mensajes enviados

### 🔄 Automatización
- **Workflows**: Creación de flujos automatizados
- **Webhooks**: Integración con eventos externos
- **Triggers**: Acciones basadas en comportamiento

### 📊 Analytics & Reporting
- **Dashboard**: Métricas en tiempo real
- **Gráficos**: Chart.js para visualización de datos
- **Exportación**: Reportes en Excel/PDF
- **Histórico**: Tracking completo de actividad

## Arquitectura Técnica

### Estructura del Módulo
```
modules/Mailrelay/
├── app/
│   ├── Console/Commands/      # Comandos Artisan (sync, send)
│   ├── Entities/               # 25 Modelos Eloquent
│   ├── Enums/                  # Status enums (Campaign, Subscriber, Import, Validation)
│   ├── Events/                 # Eventos del módulo
│   ├── Exceptions/             # MailrelayException, EmailValidationException
│   ├── Http/
│   │   ├── Controllers/        # 19 controladores (Web, API, Settings)
│   │   ├── Middleware/         # CSRF exceptions
│   │   ├── Requests/           # Form request validation
│   │   └── Resources/          # API resources
│   ├── Jobs/                   # 6 queued jobs (import, sync, validation)
│   ├── Listeners/              # Event listeners
│   ├── Notifications/          # Notificaciones
│   ├── Policies/               # Authorization policies
│   ├── Services/               # 39 servicios de negocio
│   │   └── EmailValidation/    # 10 validadores
│   └── Traits/                 # Traits reutilizables
├── config/
│   ├── mailrelay.php          # Configuración Mailrelay API
│   ├── email-validator.php    # Configuración validación
│   └── email-utilities.php    # Configuración utilities
├── database/
│   ├── migrations/             # 36+ migraciones
│   ├── seeders/                # Seeders de datos
│   └── factories/              # Model factories
├── resources/
│   ├── views/                  # 27 vistas Blade
│   │   ├── campaigns/          # Gestión de campañas
│   │   ├── subscribers/        # Gestión de suscriptores
│   │   ├── imports/            # Importación
│   │   ├── newsletter/         # Suscripción pública
│   │   ├── validation/         # Testing de validación
│   │   └── layouts/            # Layouts (app, public)
│   ├── css/                    # Estilos personalizados
│   └── js/                     # Scripts JavaScript
├── routes/
│   ├── web.php                # Rutas web
│   └── api.php                # Rutas API
├── tests/
│   ├── Feature/               # Tests de integración
│   └── Unit/                  # Tests unitarios
└── supervisor/                # Configuración de colas
    ├── linux/
    └── mac/
```

### Modelos Principales

**Core Models:**
- `Campaign` - Campañas de email con analytics
- `Subscriber` - Suscriptores con estados y metadata
- `MailrelayGroup` - Grupos/segmentos sincronizados
- `EmailValidation` - Resultados de validación cacheados
- `ImportJob` - Jobs de importación con tracking

**Configuration Models:**
- `Lists` - Listas de suscriptores
- `Group` - Grupos locales
- `CustomField` - Campos personalizados
- `EmailTemplate` - Plantillas de email

**Analytics Models:**
- `CampaignAnalytics` - Métricas de campaña
- `ResponseLog` - Tracking de interacciones
- `Bounce` - Emails rebotados
- `UnsubscribeEvent` - Eventos de baja

### Servicios Principales

**MailRelayService** (18.6 KB)
- Cliente HTTP principal para Mailrelay API
- Retry logic con exponential backoff
- Sincronización bidireccional
- Gestión de webhooks
- Caching con TTL configurable

**EmailValidatorService** (19.9 KB)
- Orquestador de validación multi-nivel
- 10 validadores especializados
- Early-exit optimization
- Cost tracking
- Result aggregation

**CampaignService**
- CRUD de campañas
- Envío y programación
- A/B testing
- Analytics sync

**ImportService**
- Procesamiento de Excel/CSV
- Auto-detección de columnas
- Validación en lote
- Reporting detallado

### Jobs & Queue System

**ProcessEmailImportJob**
- Importación asíncrona de archivos
- Validación opcional de emails
- Creación masiva de suscriptores
- Generación de reportes

**SyncSubscriberJob**
- Sincronización con Mailrelay API
- Retry con exponential backoff
- Manejo de conflictos 409
- Actualización de metadata

**ValidateEmailJob**
- Validación asíncrona
- Multi-provider support
- Caching de resultados
- Cost tracking

## Instalación

### 1. Requisitos Previos
- PHP 8.4+
- Laravel 12+
- PostgreSQL 15+ / MySQL 8+
- Redis 7+ (para colas y cache)
- Supervisor (para queue workers)

### 2. Instalación del Módulo

```bash
# El módulo ya está en modules/Mailrelay/
# Instalar dependencias
composer install

# Ejecutar migraciones
php artisan migrate --path=modules/Mailrelay/database/migrations

# Ejecutar seeders (opcional)
php artisan db:seed --class=Modules\\Mailrelay\\Database\\Seeders\\DatabaseSeeder

# Compilar assets
npm install
npm run build
```

### 3. Configuración

Agregar al archivo `.env`:

```env
# Mailrelay API
MAILRELAY_API_KEY=your_api_key_here
MAILRELAY_API_URL=https://api.mailrelay.com/v2

# Email Validation
EMAIL_VALIDATOR_PRIMARY_PROVIDER=zerobounce
EMAIL_VALIDATOR_ZEROBOUNCE_API_KEY=your_key
EMAIL_VALIDATOR_NEVERBOUNCE_API_KEY=your_key
EMAIL_VALIDATOR_HUNTER_API_KEY=your_key

# SMTP Validation
EMAIL_VALIDATOR_SMTP_ENABLED=true
EMAIL_VALIDATOR_SMTP_TIMEOUT=10

# Cache
MAILRELAY_CACHE_ENABLED=true
MAILRELAY_CACHE_TTL=3600
```

### 4. Configurar Queue Workers

```bash
# Linux
sudo cp modules/Mailrelay/supervisor/linux/mailrelay-queue.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start mailrelay-queue:*

# macOS
cp modules/Mailrelay/supervisor/mac/mailrelay-queue.plist ~/Library/LaunchAgents/
launchctl load ~/Library/LaunchAgents/mailrelay-queue.plist
```

### 5. Publicar Configuración (Opcional)

```bash
php artisan vendor:publish --tag=mailrelay-config
```

## Uso

### Rutas Web (Admin)

```
/web/dashboard                  # Dashboard con métricas
/web/subscribers                # Gestión de suscriptores (CRUD)
/web/campaigns                  # Gestión de campañas (CRUD)
/web/imports                    # Historial de importaciones
/web/validation/test            # Testing de validación
```

### API Endpoints

**Email Validation:**
```bash
POST /api/validation/validate
POST /api/validation/validate-bulk
```

**Newsletter:**
```bash
POST /api/newsletter/subscribe
POST /api/newsletter/unsubscribe
GET  /api/newsletter/status?email=user@example.com
GET  /api/newsletter/subscribers
```

**Campaigns:**
```bash
GET    /api/campaigns
POST   /api/campaigns
GET    /api/campaigns/{id}
PATCH  /api/campaigns/{id}
DELETE /api/campaigns/{id}
POST   /api/campaigns/{id}/send
GET    /api/campaigns/{id}/analytics
```

**Imports:**
```bash
POST /api/imports/upload
GET  /api/imports/{id}/status
GET  /api/imports/{id}/report
```

### Comandos Artisan

```bash
# Sincronizar con Mailrelay
php artisan mailrelay:sync
php artisan mailrelay:sync --force
php artisan mailrelay:sync --dry-run

# Enviar campañas programadas
php artisan mailrelay:send-campaigns
```

### Uso Programático

```php
use Modules\Mailrelay\Services\MailRelayService;
use Modules\Mailrelay\Services\EmailValidatorService;
use Modules\Mailrelay\Entities\Campaign;

// Validar email
$validator = app(EmailValidatorService::class);
$result = $validator->validate('user@example.com', [
    'syntax', 'dns', 'smtp', 'external'
]);

// Crear campaña
$campaign = Campaign::create([
    'name' => 'Newsletter Enero 2026',
    'subject' => 'Novedades del mes',
    'html_content' => '<html>...</html>',
    'status' => CampaignStatus::DRAFT
]);

// Sincronizar suscriptor
$mailrelay = app(MailRelayService::class);
$mailrelay->syncSubscriber($subscriber);

// Enviar campaña
$campaign->markAsSending();
$mailrelay->sendCampaign($campaign->id);
```

## Testing

```bash
# Ejecutar todos los tests
php artisan test modules/Mailrelay/tests

# Tests específicos
php artisan test modules/Mailrelay/tests/Feature/CampaignTest.php
php artisan test modules/Mailrelay/tests/Unit/EmailValidationTest.php
```

## Roadmap

- [ ] Integración con otros proveedores (SendGrid, Mailgun)
- [ ] Editor de plantillas drag & drop
- [ ] Segmentación avanzada con machine learning
- [ ] Webhooks personalizables
- [ ] Multi-idioma completo
- [ ] App móvil para gestión

## Licencia

MIT License - Ver LICENSE.md

## Soporte

Para soporte técnico, contactar: dev@alsernet.com
