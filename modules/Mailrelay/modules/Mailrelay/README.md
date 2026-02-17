# Mailrelay Module - Multi-Provider Email Marketing System

Sistema completo de email marketing con soporte para múltiples proveedores de envío (Mailrelay, Mailtrap, SendGrid, AWS SES, Postmark).

## 📋 Características Principales

### ✅ Multi-Provider Support
- **5 Proveedores Integrados**: Mailrelay, Mailtrap, SendGrid, AWS SES, Postmark
- **Arquitectura Extensible**: Strategy Pattern para agregar nuevos providers fácilmente
- **Failover Automático**: Sistema de prioridades para backup automático
- **Configuración Encriptada**: Credenciales seguras en base de datos

### ✅ Campaign Management
- **Integración con Mailer**: Reutiliza templates, layouts y componentes
- **Multi-idioma**: Soporte completo vía `lang_id`
- **Variables Dinámicas**: Personalización de contenido por destinatario
- **Tracking Avanzado**: Opens, clicks, bounces, deliverability
- **Programación**: Schedule campaigns para envío futuro
- **Preview & Testing**: Vista previa y emails de prueba

### ✅ REST API
- **Versioned API**: `/api/v1/` con autenticación Sanctum
- **Full CRUD**: Providers y Campaigns
- **API Resources**: Transformación limpia de datos JSON
- **Rate Limiting**: Respeto de límites por provider
- **Async Processing**: Queue jobs para envíos masivos

### ✅ Authorization
- **Policy-Based**: Laravel Policies con lógica de negocio
- **Granular Permissions**: Permisos específicos por módulo
- **Role Support**: Marketing Manager, Subscriber Manager, Super Admin

## 🏗️ Arquitectura

```
modules/Mailrelay/
├── app/
│   ├── Contracts/
│   │   ├── CampaignRendererInterface.php    # Contrato para renderizado
│   │   └── MailProviderInterface.php         # Contrato para providers
│   ├── Providers/Mail/
│   │   ├── AbstractMailProvider.php          # Clase base común
│   │   ├── MailrelayProvider.php             # Provider de Mailrelay
│   │   ├── MailtrapProvider.php              # Provider de Mailtrap
│   │   ├── SendGridProvider.php              # Provider de SendGrid
│   │   ├── AwsSesProvider.php                # Provider de AWS SES
│   │   └── PostmarkProvider.php              # Provider de Postmark
│   ├── Services/
│   │   ├── ProviderManager.php               # Factory para providers
│   │   ├── CampaignService.php               # Lógica de campañas
│   │   └── CampaignRendererService.php       # Renderizado de templates
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/V1/
│   │   │   │   ├── MailProviderApiController.php
│   │   │   │   └── CampaignApiController.php
│   │   │   └── Managers/
│   │   │       ├── MailProviderController.php
│   │   │       └── CampaignManagerController.php
│   │   └── Resources/V1/
│   │       ├── MailProviderResource.php
│   │       └── CampaignResource.php
│   ├── Policies/
│   │   ├── MailProviderPolicy.php
│   │   └── CampaignPolicy.php
│   ├── Entities/
│   │   ├── MailProvider.php
│   │   └── Campaign.php
│   └── Jobs/
│       └── SendCampaignJob.php
├── database/
│   ├── migrations/
│   ├── seeders/
│   │   ├── MailrelayPermissionsSeeder.php
│   │   └── MailProviderSeeder.php
│   └── factories/
│       ├── MailProviderFactory.php
│       └── CampaignFactory.php
└── tests/
    └── Feature/
        ├── MailProviderApiTest.php
        └── CampaignApiTest.php
```

## 📦 Instalación

### 1. Ejecutar Migraciones

```bash
php artisan migrate
```

### 2. Ejecutar Seeders

```bash
# Crear permisos
php artisan db:seed --class=Modules\\Mailrelay\\Database\\Seeders\\MailrelayPermissionsSeeder

# Crear providers de ejemplo (con credenciales ficticias)
php artisan db:seed --class=Modules\\Mailrelay\\Database\\Seeders\\MailProviderSeeder
```

### 3. Configurar Credenciales

Accede al panel de administración en `/managers/mailrelay/providers` y configura las credenciales reales de tus providers.

## 🔧 Configuración de Providers

### Mailrelay

```php
[
    'api_url' => 'https://api.mailrelay.com/v1',
    'api_key' => 'your_api_key',
    'from_email' => 'noreply@example.com',
    'from_name' => 'Your Company',
    'reply_to' => 'support@example.com',
]
```

### SendGrid

```php
[
    'api_key' => 'SG.xxx',
    'from_email' => 'noreply@example.com',
    'from_name' => 'Your Company',
    'reply_to' => 'support@example.com',
]
```

### AWS SES

```php
[
    'access_key' => 'AKIAXXXXX',
    'secret_key' => 'your_secret_key',
    'region' => 'us-east-1',
    'from_email' => 'noreply@example.com',
    'from_name' => 'Your Company',
    'configuration_set' => 'production-emails', // opcional
]
```

### Postmark

```php
[
    'server_token' => 'your_server_token',
    'from_email' => 'noreply@example.com',
    'from_name' => 'Your Company',
    'message_stream' => 'outbound', // outbound o broadcast
]
```

### Mailtrap

```php
[
    'api_token' => 'your_api_token',
    'from_email' => 'dev@example.com',
    'from_name' => 'Development Team',
]
```

## 🚀 Uso

### Via API

#### 1. Listar Providers

```http
GET /api/v1/providers
Authorization: Bearer {token}
```

#### 2. Crear Provider

```http
POST /api/v1/providers
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "SendGrid Production",
    "driver": "sendgrid",
    "credentials": {
        "api_key": "SG.xxx",
        "from_email": "noreply@example.com",
        "from_name": "Example Team"
    },
    "is_active": true,
    "priority": 90
}
```

#### 3. Crear Campaña

```http
POST /api/v1/campaigns
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Newsletter Enero 2026",
    "subject": "Novedades del mes",
    "mailer_template_id": 5,
    "lang_id": 1,
    "mail_provider_id": 2,
    "template_variables": {
        "month": "Enero",
        "year": "2026"
    },
    "track_opens": true,
    "track_clicks": true
}
```

#### 4. Enviar Campaña

```http
POST /api/v1/campaigns/{id}/send
Authorization: Bearer {token}
Content-Type: application/json

{
    "recipients": [
        {"email": "user1@example.com", "name": "John Doe"},
        {"email": "user2@example.com", "name": "Jane Smith"}
    ],
    "send_async": true
}
```

### Via PHP

#### Crear y Enviar Campaña

```php
use Modules\Mailrelay\Services\CampaignService;
use Modules\Mailrelay\Entities\Campaign;

$campaignService = app(CampaignService::class);

// Crear campaña
$campaign = $campaignService->create([
    'name' => 'Newsletter',
    'subject' => 'New Features',
    'mailer_template_id' => 5,
    'lang_id' => 1,
    'mail_provider_id' => 2,
]);

// Enviar
$result = $campaignService->send($campaign, [
    ['email' => 'user@example.com', 'name' => 'User'],
]);
```

#### Obtener Provider

```php
use Modules\Mailrelay\Services\ProviderManager;

$providerManager = app(ProviderManager::class);

// Por defecto
$provider = $providerManager->default();

// Por ID
$provider = $providerManager->byId(2);

// Por driver
$provider = $providerManager->driver('sendgrid');
```

## 🧪 Testing

### Ejecutar Tests

```bash
# Todos los tests del módulo
php artisan test modules/Mailrelay/tests

# Tests de API
php artisan test modules/Mailrelay/tests/Feature/MailProviderApiTest.php
php artisan test modules/Mailrelay/tests/Feature/CampaignApiTest.php
```

### Factories

```php
// Crear provider de prueba
$provider = MailProvider::factory()->create([
    'driver' => 'sendgrid',
]);

// Provider activo por defecto
$provider = MailProvider::factory()->default()->create();

// Campaña en draft
$campaign = Campaign::factory()->draft()->create();

// Campaña enviada
$campaign = Campaign::factory()->sent()->create();
```

## 📊 Rate Limits por Provider

| Provider | Por Segundo | Por Día | Batch Size |
|----------|-------------|---------|------------|
| Mailrelay | Variable | Variable | 1000 |
| SendGrid | 100 | 100,000 | 1000 |
| AWS SES | 14* | 200* | 50 |
| Postmark | 10* | 10,000* | 500 |
| Mailtrap | Variable | 1,000 | 100 |

*Límites por defecto - verificar con tu cuenta

## 🔐 Permisos

### Providers

- `mailrelay.providers.view` - Ver proveedores
- `mailrelay.providers.create` - Crear proveedores
- `mailrelay.providers.edit` - Editar proveedores
- `mailrelay.providers.delete` - Eliminar proveedores

### Campaigns

- `mailrelay.campaigns.view` - Ver campañas
- `mailrelay.campaigns.create` - Crear campañas
- `mailrelay.campaigns.edit` - Editar campañas
- `mailrelay.campaigns.delete` - Eliminar campañas
- `mailrelay.campaigns.send` - Enviar campañas

## 📝 Políticas de Negocio

### MailProvider

- ✅ No se puede eliminar el provider por defecto
- ✅ No se puede eliminar un provider con campañas activas
- ✅ No se puede desactivar el provider por defecto
- ✅ Un provider debe estar activo para ser marcado como default
- ✅ Solo un provider puede ser default a la vez

### Campaign

- ✅ No se pueden editar campañas enviadas
- ✅ No se pueden eliminar campañas enviadas o en envío
- ✅ Solo campañas en draft pueden ser programadas
- ✅ Cualquier usuario con permisos puede duplicar campañas
- ✅ Las campañas usan el provider configurado (o el default)

## 🔄 Añadir Nuevo Provider

### 1. Crear Provider Class

```php
<?php

namespace Modules\Mailrelay\Providers\Mail;

class CustomProvider extends AbstractMailProvider
{
    public function getName(): string
    {
        return 'Custom Provider';
    }

    public function send(string $to, string $subject, string $htmlContent, array $options = []): array
    {
        // Implementar envío
    }

    public function sendBulk(array $recipients, string $subject, string $htmlContent, array $options = []): array
    {
        // Implementar envío masivo
    }

    public function testConnection(): array
    {
        // Implementar test de conexión
    }

    public function getRateLimits(): array
    {
        return [
            'max_per_second' => 10,
            'max_per_day' => 1000,
            'batch_size' => 100,
        ];
    }
}
```

### 2. Registrar en ProviderManager

```php
// modules/Mailrelay/app/Services/ProviderManager.php

private array $providers = [
    'mailrelay' => MailrelayProvider::class,
    'mailtrap' => MailtrapProvider::class,
    'sendgrid' => SendGridProvider::class,
    'aws_ses' => AwsSesProvider::class,
    'postmark' => PostmarkProvider::class,
    'custom' => CustomProvider::class, // ← Nuevo
];
```

### 3. Añadir Información del Driver

```php
public function getDriverInfo(string $driver): array
{
    $info = [
        // ... otros drivers
        'custom' => [
            'name' => 'Custom Provider',
            'description' => 'Mi provider personalizado',
            'features' => ['Feature 1', 'Feature 2'],
            'required_credentials' => ['api_key', 'from_email'],
        ],
    ];

    return $info[$driver] ?? [...]
}
```

## 📚 Referencias

- **Laravel Policies**: https://laravel.com/docs/12.x/authorization
- **Laravel Sanctum**: https://laravel.com/docs/12.x/sanctum
- **Laravel Queues**: https://laravel.com/docs/12.x/queues
- **SendGrid API**: https://docs.sendgrid.com/api-reference
- **AWS SES API**: https://docs.aws.amazon.com/ses/
- **Postmark API**: https://postmarkapp.com/developer
- **Mailrelay API**: https://mailrelay.com/api

## 🤝 Contribuir

1. Los providers deben implementar `MailProviderInterface`
2. Usar `AbstractMailProvider` como clase base
3. Incluir tests para nuevas funcionalidades
4. Documentar credenciales requeridas
5. Actualizar este README

## 📄 Licencia

Propiedad de Alsernet - Uso interno

---

**Versión**: 2.0.0
**Última Actualización**: Enero 2026
**Mantenedor**: Development Team
