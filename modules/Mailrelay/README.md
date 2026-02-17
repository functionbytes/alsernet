# Mailrelay Module

Módulo completo de email marketing y automatización con integración Mailrelay API para la plataforma Alsernet.

## Características Principales

### 🚀 **NUEVO: Sistema Multi-Provider con Integración Mailer** (v2.0)

#### Multi-Provider Support
- **Mailrelay** - Provider principal para envíos masivos en producción
- **Mailtrap** - Provider de testing/staging
- **Extensible** - Arquitectura preparada para agregar SendGrid, AWS SES, Postmark, etc.
- **Failover Automático** - Configuración de providers de respaldo con prioridades
- **Credenciales Encriptadas** - Almacenamiento seguro de API keys

#### Integración Total con Módulo Mailer
- ✅ **Reutiliza plantillas** existentes de Mailer (`MailerTemplate`)
- ✅ **Soporte completo de layouts** y componentes
- ✅ **Multi-idioma** integrado (`lang_id`)
- ✅ **Variables de plantilla** con reemplazo automático
- ✅ **Preview en tiempo real** antes de enviar
- ✅ **HTML personalizado** o plantillas Mailer (flexible)

#### Arquitectura Moderna
- **Strategy Pattern** - Providers intercambiables vía `MailProviderInterface`
- **Service Layer** - `CampaignService`, `CampaignRendererService`, `ProviderManager`
- **Queue Processing** - Envíos asíncronos con `SendCampaignJob`
- **Rate Limiting** - Respeto de límites por provider
- **Batch Processing** - Chunking automático de destinatarios

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
│   ├── Contracts/              # ✨ NUEVO v2.0
│   │   ├── MailProviderInterface.php
│   │   └── CampaignRendererInterface.php
│   ├── Providers/              # ✨ NUEVO v2.0
│   │   └── Mail/
│   │       ├── AbstractMailProvider.php
│   │       ├── MailrelayProvider.php
│   │       └── MailtrapProvider.php
│   ├── Console/Commands/      # Comandos Artisan (sync, send)
│   ├── Entities/               # 26 Modelos Eloquent (+ MailProvider)
│   ├── Enums/                  # Status enums (Campaign, Subscriber, Import, Validation)
│   ├── Events/                 # Eventos del módulo
│   ├── Exceptions/             # MailrelayException, EmailValidationException
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Managers/       # ✨ NUEVOS v2.0
│   │   │       ├── MailProviderController.php
│   │   │       └── CampaignManagerController.php
│   │   ├── Controllers/        # 19+ controladores (Web, API, Settings)
│   │   ├── Middleware/         # CSRF exceptions
│   │   ├── Requests/           # Form request validation
│   │   └── Resources/          # API resources
│   ├── Jobs/                   # 7 queued jobs (+ SendCampaignJob)
│   ├── Listeners/              # Event listeners
│   ├── Notifications/          # Notificaciones
│   ├── Policies/               # Authorization policies
│   ├── Services/               # 42+ servicios de negocio
│   │   ├── ProviderManager.php         # ✨ NUEVO v2.0
│   │   ├── CampaignRendererService.php # ✨ NUEVO v2.0
│   │   ├── CampaignService.php         # ✨ ACTUALIZADO v2.0
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
- `Campaign` - Campañas de email con analytics *(✨ ACTUALIZADO v2.0 - integración Mailer)*
- `MailProvider` - Configuración de providers *(✨ NUEVO v2.0)*
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

### ✨ Cambios en Base de Datos (v2.0)

**Nueva Tabla: `mail_providers`**
```sql
CREATE TABLE mail_providers (
    id BIGINT UNSIGNED PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    driver VARCHAR(255) NOT NULL,           -- 'mailrelay', 'mailtrap', 'sendgrid', etc.
    credentials TEXT NOT NULL,              -- JSON encriptado
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    priority INT DEFAULT 0,
    limits JSON NULL,                       -- {'emails_per_hour': 10000, ...}
    metadata JSON NULL,                     -- Información adicional
    last_tested_at TIMESTAMP NULL,
    connection_ok BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_default (is_default),
    INDEX idx_priority (priority)
);
```

**Tabla Actualizada: `campaigns`**
```sql
ALTER TABLE campaigns ADD COLUMN (
    mailer_template_id BIGINT UNSIGNED NULL,          -- FK a mailer_templates
    lang_id BIGINT UNSIGNED NULL,                     -- FK a langs
    mail_provider_id BIGINT UNSIGNED NOT NULL,        -- FK a mail_providers
    template_variables JSON NULL,                     -- Variables personalizadas
    track_opens BOOLEAN DEFAULT FALSE,
    track_clicks BOOLEAN DEFAULT FALSE,
    provider_campaign_id VARCHAR(255) NULL,           -- ID externo del provider
    scheduled_at TIMESTAMP NULL,                      -- Fecha programada

    FOREIGN KEY (mailer_template_id) REFERENCES mailer_templates(id),
    FOREIGN KEY (lang_id) REFERENCES langs(id),
    FOREIGN KEY (mail_provider_id) REFERENCES mail_providers(id)
);
```

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

---

## 🔄 Sistema Multi-Provider & Integración Mailer (v2.0)

### Arquitectura Multi-Provider

El nuevo sistema permite usar **múltiples proveedores de email** de forma intercambiable:

```
┌─────────────────────────────────────────────────────────────────┐
│                      CampaignService                             │
│  (Orquestador principal - Gestión de campaigns)                 │
└─────────────┬───────────────────────────────────────────────────┘
              │
              ├──> CampaignRendererService
              │    (Integración con MailerTemplate + variables)
              │
              └──> ProviderManager (Factory)
                   │
                   ├──> MailrelayProvider (prod)
                   ├──> MailtrapProvider (testing)
                   ├──> SendgridProvider (futuro)
                   └──> AwsSesProvider (futuro)
```

### Nuevos Componentes

#### **Contracts (Interfaces)**

**`MailProviderInterface`** - Contrato que todos los providers implementan:
```php
interface MailProviderInterface
{
    public function send(string $to, string $subject, string $htmlContent, array $options = []): array;
    public function sendBulk(array $recipients, string $subject, string $htmlContent, array $options = []): array;
    public function syncTemplate(string $templateHtml, array $metadata = []): array;
    public function getStats(string $campaignId): array;
    public function testConnection(): array;
    public function getName(): string;
    public function getRateLimits(): array;
}
```

**`CampaignRendererInterface`** - Renderizado de campaigns:
```php
interface CampaignRendererInterface
{
    public function render(Campaign $campaign, array $variables = []): string;
    public function renderPreview(Campaign $campaign, array $sampleData = []): string;
    public function getAvailableVariables(): array;
}
```

#### **Providers Implementados**

**`MailrelayProvider`**
- Implementación completa de Mailrelay API v2
- Soporte nativo de `sendBulk` (alto rendimiento)
- Sincronización de templates
- Estadísticas detalladas
- Rate limiting: 10,000/hora, 100,000/día

**`MailtrapProvider`**
- Ideal para testing y staging
- Envío individual con rate limiting
- No contamina emails reales
- Inbox de prueba visual

**`AbstractMailProvider`**
- Clase base con utilidades comunes
- `htmlToPlainText()`, `isValidEmail()`, `logError()`
- Cliente HTTP con retry logic

#### **Servicios Principales**

**`ProviderManager`** - Factory de providers
```php
// Obtener provider por nombre
$provider = $providerManager->driver('mailrelay');

// Obtener provider por defecto
$provider = $providerManager->getDefaultProvider();

// Obtener provider por ID de base de datos
$provider = $providerManager->byId($providerId);

// Test de conexión
$result = $providerManager->testProvider($providerId);
```

**`CampaignRendererService`** - Renderizado con Mailer
```php
// Renderizar campaign usando MailerTemplate
$html = $rendererService->render($campaign, [
    'SUBSCRIBER_FIRSTNAME' => 'Juan',
    'CUSTOM_VAR' => 'valor',
]);

// Preview con datos de prueba
$html = $rendererService->renderPreview($campaign, [
    'SUBSCRIBER_EMAIL' => 'test@example.com',
]);
```

**`CampaignService`** - Orquestador principal
```php
// Crear campaign
$campaign = $campaignService->create([
    'name' => 'Newsletter',
    'subject' => 'Novedades',
    'mailer_template_id' => $templateId,
    'mail_provider_id' => $providerId,
]);

// Enviar (síncrono)
$result = $campaignService->send($campaign, $recipients);

// Enviar (asíncrono - recomendado)
$campaignService->sendAsync($campaign, $recipients);

// Programar para después
$campaignService->schedule($campaign, $scheduledAt);

// Preview
$html = $campaignService->getPreview($campaign, $variables);

// Test
$result = $campaignService->sendTest($campaign, 'test@example.com');
```

#### **Modelos Actualizados**

**`MailProvider`** (Nuevo)
```php
Schema::create('mail_providers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('driver'); // mailrelay, mailtrap, sendgrid, etc.
    $table->text('credentials'); // encrypted JSON
    $table->boolean('is_active')->default(true);
    $table->boolean('is_default')->default(false);
    $table->integer('priority')->default(0);
    $table->json('limits')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('last_tested_at')->nullable();
    $table->boolean('connection_ok')->default(false);
    $table->timestamps();
});
```

**`Campaign`** (Actualizado)
```php
// Nuevas columnas agregadas:
$table->foreignId('mailer_template_id')->nullable()->constrained('mailer_templates');
$table->foreignId('lang_id')->nullable()->constrained('langs');
$table->foreignId('mail_provider_id')->constrained('mail_providers');
$table->json('template_variables')->nullable();
$table->boolean('track_opens')->default(false);
$table->boolean('track_clicks')->default(false);
$table->string('provider_campaign_id')->nullable();

// Nuevas relaciones:
public function mailerTemplate(): BelongsTo
public function language(): BelongsTo
public function mailProvider(): BelongsTo
```

### Workflow de Campaign con Mailer

```
1. CREAR CAMPAIGN
   ├─ Seleccionar plantilla de Mailer (MailerTemplate)
   │  └─ O usar HTML personalizado directo
   ├─ Configurar variables de plantilla (JSON)
   ├─ Elegir idioma (lang_id)
   └─ Seleccionar provider de envío (MailProvider)

2. PREVIEW & TEST
   ├─ Generar preview con variables de prueba
   │  └─ Usa CampaignRendererService + MailerTemplateRendererService
   ├─ Enviar email de prueba a inbox real
   └─ Revisar formato, links, imágenes

3. PREPARAR DESTINATARIOS
   ├─ Importar lista de suscriptores
   ├─ Aplicar filtros/segmentación
   └─ Validar emails (opcional)

4. ENVIAR
   ├─ Envío inmediato síncrono (< 100 destinatarios)
   ├─ Envío asíncrono via queue (recomendado)
   └─ Programar para fecha/hora futura

5. MONITOREAR
   ├─ Ver progreso en tiempo real
   ├─ Revisar estadísticas (opens, clicks)
   └─ Analizar bounces y unsubscribes
```

### Ejemplo Completo de Uso

```php
use Modules\Mailrelay\Entities\MailProvider;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Services\CampaignService;
use Modules\Mailer\Models\MailerTemplate;

// 1. Crear provider (una sola vez)
$provider = MailProvider::create([
    'name' => 'Mailrelay Producción',
    'driver' => 'mailrelay',
    'credentials' => [
        'api_url' => env('MAILRELAY_API_URL'),
        'api_key' => env('MAILRELAY_API_KEY'),
        'from_email' => 'noreply@alsernet.com',
        'from_name' => 'Alsernet',
    ],
    'is_active' => true,
    'is_default' => true,
    'priority' => 10,
]);

// 2. Seleccionar plantilla de Mailer
$template = MailerTemplate::where('slug', 'newsletter-monthly')
    ->where('is_enabled', true)
    ->first();

// 3. Crear campaign usando plantilla Mailer
$campaign = Campaign::create([
    'name' => 'Newsletter Marzo 2026',
    'subject' => '📰 Novedades de Marzo',
    'mailer_template_id' => $template->id, // ✅ Integración Mailer
    'lang_id' => 1,
    'mail_provider_id' => $provider->id,
    'template_variables' => [
        'CAMPAIGN_MONTH' => 'Marzo',
        'CAMPAIGN_YEAR' => '2026',
        'FEATURED_CONTENT' => '<h3>Nueva funcionalidad de IA</h3>',
        'CTA_URL' => 'https://alsernet.com/news/march',
    ],
    'track_opens' => true,
    'track_clicks' => true,
]);

// 4. Preview antes de enviar
$campaignService = app(CampaignService::class);
$html = $campaignService->getPreview($campaign, [
    'SUBSCRIBER_FIRSTNAME' => 'Juan',
    'SUBSCRIBER_EMAIL' => 'juan@example.com',
]);
// Revisar $html en navegador

// 5. Enviar email de prueba
$campaignService->sendTest($campaign, 'test@alsernet.com');

// 6. Preparar destinatarios
$recipients = [
    ['email' => 'user1@example.com', 'name' => 'Usuario 1'],
    ['email' => 'user2@example.com', 'name' => 'Usuario 2'],
    // ... miles de destinatarios
];

// 7. Enviar campaign (asíncrono - recomendado)
$campaignService->sendAsync($campaign, $recipients);
// La campaign se procesa en background via queue

// 8. O programar para después
$scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);
$campaignService->schedule($campaign, $scheduledAt);

// 9. Monitorear estadísticas
$stats = $campaignService->getStats($campaign);
echo "Enviados: {$stats['sent']}\n";
echo "Abiertos: {$stats['opened']} ({$stats['open_rate']}%)\n";
echo "Clicks: {$stats['clicked']} ({$stats['click_rate']}%)\n";
```

### Variables Disponibles en Plantillas Mailer

Las campaigns pueden usar todas las variables de Mailer más variables específicas:

#### Variables Globales (Automáticas)
```
{SITE_NAME}           → Nombre del sitio
{SITE_URL}            → URL del sitio
{CURRENT_YEAR}        → Año actual
{CURRENT_DATE}        → Fecha actual
{COMPANY_NAME}        → Nombre de la empresa
{COMPANY_ADDRESS}     → Dirección física
```

#### Variables de Campaign
```
{CAMPAIGN_NAME}       → Nombre de la campaign
{CAMPAIGN_SUBJECT}    → Asunto de la campaign
{CAMPAIGN_ID}         → ID de la campaign
```

#### Variables de Suscriptor
```
{SUBSCRIBER_EMAIL}    → Email del destinatario
{SUBSCRIBER_FIRSTNAME}→ Nombre del destinatario
{SUBSCRIBER_LASTNAME} → Apellido del destinatario
{SUBSCRIBER_ID}       → ID del suscriptor
```

#### Variables de URL (Tracking)
```
{UNSUBSCRIBE_URL}     → URL para cancelar suscripción
{WEB_VERSION_URL}     → URL para ver versión web
{TRACKING_PIXEL}      → Pixel de tracking (si track_opens=true)
```

#### Variables Personalizadas
Puedes agregar tus propias variables en `template_variables`:

```php
$campaign->update([
    'template_variables' => [
        'PROMO_CODE' => 'DESCUENTO50',
        'EXPIRATION_DATE' => '2026-03-31',
        'PRODUCT_NAME' => 'Plan Premium',
        'DISCOUNT_AMOUNT' => '50%',
        'CUSTOM_MESSAGE' => 'Oferta exclusiva para ti',
    ],
]);
```

Luego usarlas en tu plantilla Mailer:
```html
<h2>¡Oferta Especial!</h2>
<p>Usa el código <strong>{PROMO_CODE}</strong> para obtener
   <strong>{DISCOUNT_AMOUNT}</strong> de descuento en {PRODUCT_NAME}.</p>
<p>Válido hasta: {EXPIRATION_DATE}</p>
<p>{CUSTOM_MESSAGE}</p>
```

### Agregar Nuevos Providers

#### 1. Crear Clase del Provider

```php
// modules/Mailrelay/app/Providers/Mail/SendgridProvider.php

namespace Modules\Mailrelay\Providers\Mail;

use Modules\Mailrelay\Contracts\MailProviderInterface;

class SendgridProvider extends AbstractMailProvider implements MailProviderInterface
{
    public function send(string $to, string $subject, string $htmlContent, array $options = []): array
    {
        $response = $this->client->post('https://api.sendgrid.com/v3/mail/send', [
            'headers' => [
                'Authorization' => "Bearer {$this->getConfig('api_key')}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'personalizations' => [
                    ['to' => [['email' => $to]]],
                ],
                'from' => [
                    'email' => $this->getConfig('from_email'),
                    'name' => $this->getConfig('from_name'),
                ],
                'subject' => $subject,
                'content' => [
                    ['type' => 'text/html', 'value' => $htmlContent],
                ],
                'tracking_settings' => [
                    'click_tracking' => ['enable' => $options['track_clicks'] ?? false],
                    'open_tracking' => ['enable' => $options['track_opens'] ?? false],
                ],
            ],
        ]);

        return [
            'success' => $response->getStatusCode() === 202,
            'message_id' => $response->getHeader('X-Message-Id')[0] ?? null,
        ];
    }

    public function sendBulk(array $recipients, string $subject, string $htmlContent, array $options = []): array
    {
        // Implementar usando personalizations
        $personalizations = array_map(fn($r) => [
            'to' => [['email' => $r['email'], 'name' => $r['name'] ?? '']],
        ], $recipients);

        // ... resto de implementación
    }

    public function getName(): string
    {
        return 'SendGrid';
    }

    public function getRateLimits(): array
    {
        return [
            'per_second' => 600,
            'per_day' => 100000,
        ];
    }

    public function testConnection(): array
    {
        try {
            $response = $this->client->get('https://api.sendgrid.com/v3/user/profile', [
                'headers' => ['Authorization' => "Bearer {$this->getConfig('api_key')}"],
            ]);

            return [
                'success' => $response->getStatusCode() === 200,
                'message' => 'Conexión exitosa con SendGrid',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Error: {$e->getMessage()}",
            ];
        }
    }
}
```

#### 2. Registrar en ProviderManager

```php
// modules/Mailrelay/app/Services/ProviderManager.php

protected function createProvider(string $driver, array $credentials): MailProviderInterface
{
    return match ($driver) {
        'mailrelay' => new MailrelayProvider($credentials),
        'mailtrap' => new MailtrapProvider($credentials),
        'sendgrid' => new SendgridProvider($credentials), // ✅ Nuevo
        default => throw new \InvalidArgumentException("Unsupported driver: {$driver}"),
    };
}

public function availableDrivers(): array
{
    return ['mailrelay', 'mailtrap', 'sendgrid']; // ✅ Agregar
}

public function getDriverInfo(string $driver): array
{
    $info = [
        'mailrelay' => [
            'name' => 'Mailrelay',
            'description' => 'Provider principal para envíos masivos',
            'required_credentials' => ['api_url', 'api_key', 'from_email'],
        ],
        'mailtrap' => [
            'name' => 'Mailtrap',
            'description' => 'Provider de testing/staging',
            'required_credentials' => ['api_token', 'from_email'],
        ],
        'sendgrid' => [ // ✅ Nuevo
            'name' => 'SendGrid',
            'description' => 'Proveedor cloud escalable',
            'required_credentials' => ['api_key', 'from_email', 'from_name'],
        ],
    ];

    return $info[$driver] ?? throw new \InvalidArgumentException("Driver {$driver} no existe");
}
```

#### 3. Crear Provider en Base de Datos

```php
MailProvider::create([
    'name' => 'SendGrid Producción',
    'driver' => 'sendgrid',
    'credentials' => [
        'api_key' => env('SENDGRID_API_KEY'),
        'from_email' => env('MAIL_FROM_ADDRESS'),
        'from_name' => env('MAIL_FROM_NAME'),
    ],
    'is_active' => true,
    'is_default' => false,
    'priority' => 8,
    'limits' => [
        'emails_per_second' => 600,
        'emails_per_day' => 100000,
    ],
    'metadata' => [
        'description' => 'SendGrid para alta escalabilidad',
        'use_cases' => ['Marketing', 'Transactional'],
    ],
]);
```

### Controllers para Gestión

**`MailProviderController`** - CRUD de providers
```
GET    /managers/mailrelay/providers              → index
GET    /managers/mailrelay/providers/create       → create
POST   /managers/mailrelay/providers              → store
GET    /managers/mailrelay/providers/{id}/edit    → edit
PUT    /managers/mailrelay/providers/{id}         → update
DELETE /managers/mailrelay/providers/{id}         → destroy
POST   /managers/mailrelay/providers/{id}/test    → test (AJAX)
POST   /managers/mailrelay/providers/{id}/default → setDefault
POST   /managers/mailrelay/providers/{id}/toggle  → toggleActive
```

**`CampaignManagerController`** - CRUD de campaigns
```
GET    /managers/mailrelay/campaigns                    → index
GET    /managers/mailrelay/campaigns/create             → create
POST   /managers/mailrelay/campaigns                    → store
GET    /managers/mailrelay/campaigns/{id}               → show
GET    /managers/mailrelay/campaigns/{id}/edit          → edit
PUT    /managers/mailrelay/campaigns/{id}               → update
DELETE /managers/mailrelay/campaigns/{id}               → destroy
POST   /managers/mailrelay/campaigns/{id}/duplicate     → duplicate
GET    /managers/mailrelay/campaigns/{id}/preview       → preview
POST   /managers/mailrelay/campaigns/{id}/send-test     → sendTest (AJAX)
POST   /managers/mailrelay/campaigns/{id}/send          → send (AJAX)
POST   /managers/mailrelay/campaigns/{id}/schedule      → schedule (AJAX)
```

### Queue Job para Envíos Asíncronos

**`SendCampaignJob`**
```php
class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 1800; // 30 minutos
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function handle(
        ProviderManager $providerManager,
        CampaignRendererService $rendererService
    ): void {
        $provider = $providerManager->byId($this->campaign->mail_provider_id);
        $baseHtml = $rendererService->render($this->campaign);

        // Procesar en batches
        $batchSize = 1000;
        $batches = array_chunk($this->recipients, $batchSize);

        foreach ($batches as $batch) {
            $result = $provider->sendBulk($batch, $this->campaign->subject, $baseHtml, [
                'track_opens' => $this->campaign->track_opens,
                'track_clicks' => $this->campaign->track_clicks,
            ]);

            // Rate limiting entre batches
            sleep(1);
        }

        $this->campaign->markAsSent();
    }
}
```

---

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

# Ejecutar migraciones (incluye nuevas tablas multi-provider)
php artisan migrate --path=modules/Mailrelay/database/migrations

# Ejecutar seeders (incluye MailProviderSeeder y CampaignSeeder actualizados)
php artisan db:seed --class=Modules\\Mailrelay\\Database\\Seeders\\MailrelayDatabaseSeeder

# Compilar assets
npm install
npm run build
```

**Nuevas migraciones incluidas en v2.0:**
- `2026_01_25_100000_create_mail_providers_table.php` - Tabla de providers
- `2026_01_25_110000_add_mailer_integration_to_campaigns_table.php` - Integración Mailer

**Seeders actualizados:**
- `MailProviderSeeder` - Crea 3 providers de ejemplo (Mailrelay Prod, Mailtrap Testing, Mailrelay Backup)
- `CampaignSeeder` - Crea 4 campaigns de ejemplo usando plantillas Mailer

### 3. Configuración

Agregar al archivo `.env`:

```env
# ============================================
# MULTI-PROVIDER CONFIGURATION (v2.0)
# ============================================

# Mailrelay Production Provider
MAILRELAY_API_KEY=your_mailrelay_api_key_here
MAILRELAY_API_URL=https://app.mailrelay.com/api/v1

# Mailrelay Backup Provider (Failover)
MAILRELAY_BACKUP_API_KEY=your_backup_api_key_here
MAILRELAY_BACKUP_API_URL=https://app.mailrelay.com/api/v1

# Mailtrap Testing Provider
MAILTRAP_API_TOKEN=your_mailtrap_token_here

# Email Defaults (usado por todos los providers)
MAIL_FROM_ADDRESS=noreply@alsernet.com
MAIL_FROM_NAME="Alsernet"
MAIL_REPLY_TO=soporte@alsernet.com

# ============================================
# ORIGINAL EMAIL VALIDATION
# ============================================

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

# ============================================
# QUEUE CONFIGURATION (Para envíos asíncronos)
# ============================================
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
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

# Listar providers configurados
php artisan mailrelay:list-providers
php artisan mailrelay:list-providers --inactive  # Incluir inactivos
php artisan mailrelay:list-providers --json      # Output JSON

# Probar conexión de providers
php artisan mailrelay:test-provider 1           # Por ID
php artisan mailrelay:test-provider sendgrid    # Por driver
php artisan mailrelay:test-provider --all       # Todos los activos
php artisan mailrelay:test-provider --all --inactive  # Todos
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

### ✅ Completado (v2.0 - Enero 2026)
- [x] **Sistema Multi-Provider** - Soporte de múltiples proveedores intercambiables
- [x] **Integración Mailer** - Reutilización completa de plantillas, layouts, componentes
- [x] **Multi-idioma** - Integración con sistema de idiomas de Mailer
- [x] **Queue Processing** - Envíos asíncronos con jobs y retry logic
- [x] **Credentials Encryption** - Almacenamiento seguro de API keys
- [x] **Rate Limiting** - Respeto de límites por provider
- [x] **Batch Processing** - Chunking automático de destinatarios

### 🚧 En Desarrollo
- [ ] Vistas Blade para gestión de providers y campaigns
- [ ] API REST completa para providers y campaigns
- [ ] Tests unitarios y de integración
- [ ] Políticas de autorización (CampaignPolicy, MailProviderPolicy)

### 📋 Planificado (v2.1+)
- [ ] **Más Providers**: SendGrid, AWS SES, Mailgun, Postmark
- [ ] **Editor visual** de plantillas drag & drop
- [ ] **Segmentación avanzada** con machine learning
- [ ] **Webhooks personalizables** para eventos de campaign
- [ ] **Gestión de suscriptores** mejorada con importación masiva
- [ ] **Dashboard analytics** con gráficos en tiempo real
- [ ] **App móvil** para gestión de campaigns

## Licencia

MIT License - Ver LICENSE.md

## Soporte

Para soporte técnico, contactar: dev@alsernet.com
