# Mailer Module

Email template management and endpoint configuration module for Alsernet. Provides comprehensive email template management, variable definitions, layout components, and HTTP API endpoints for sending emails.

## Responsabilidad

Este modulo gestiona **emails transaccionales**: notificaciones, confirmaciones, password reset, alertas, emails 1-a-1 disparados por eventos del sistema.

### Casos de uso

- Confirmacion de creacion de cuenta
- Reset de contrasena
- Notificacion de tickets/conversations
- Alertas de seguridad
- Emails de cambio de email
- Magic link emails
- Cualquier email triggered por un evento de aplicacion

### NO usar para

- Newsletters masivos
- Email marketing campaigns
- Subscriber lists / segmentacion
- Multi-provider routing (SendGrid, AWS SES, etc.)

Para esos casos, usar el modulo `Mailrelay`.

## Diferencia con Mailrelay

| Caracteristica | Mailer | Mailrelay |
|---|---|---|
| Tipo de email | Transaccional | Marketing/masivo |
| Volumen tipico | 1-10/usuario | 1000-100000/campana |
| Audience | Individual users | Subscriber lists |
| Provider | SMTP unico (config Laravel) | Multi-provider (Mailrelay, Mailtrap, SendGrid, AWS SES, Postmark) |
| Templates | i18n por user locale | Marketing en idioma de la lista |
| Trigger | Application events | Manual schedule / automation |
| Tracking | Bounce/unsubscribe via webhook | Open/click tracking nativo |

## Features

- **Email Templates** - Multi-language email template management with translations
- **Template Variables** - System and custom variable definitions with categories
- **Layout Components** - Reusable header, footer, and layout components
- **Email Endpoints** - HTTP API endpoints for external email sending services
- **Endpoint Logging** - Track and monitor all endpoint requests and delivery status
- **Template Rendering** - Dynamic variable substitution and HTML rendering
- **Variable Service** - Manage available variables by module and category

## Routes

**Manager Routes** (`/manager/settings/mailers/`):
- Templates: `manager.mailers.templates.*` - CRUD operations for email templates
- Components: `manager.mailers.components.*` - CRUD for layout components
- Variables: `manager.mailers.variables.*` - Manage email variables
- Endpoints: `manager.mailers.endpoints.*` - Configure API endpoints

**API Routes** (`/api/endpoints/`):
- `POST /{slug}/send` - Send email via endpoint
- `GET /{slug}/info` - Get endpoint information
- `GET /{slug}/status` - Get endpoint status and statistics

## Architecture

```
modules/Mailer/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Settings/
│   │   │   │   ├── MailerTemplateController.php
│   │   │   │   ├── MailerComponentController.php
│   │   │   │   ├── MailerVariableController.php
│   │   │   │   └── MailerEndpointController.php
│   │   │   └── Api/
│   │   │       └── EmailEndpointController.php
│   ├── Models/
│   │   ├── MailerTemplate.php
│   │   ├── MailerTemplateLang.php
│   │   ├── MailerLayout.php
│   │   ├── MailerLayoutLang.php
│   │   ├── MailerVariable.php
│   │   ├── MailerVariableLang.php
│   │   ├── MailerEndpoint.php
│   │   └── MailerEndpointLog.php
│   ├── Services/
│   │   ├── MailerTemplateRendererService.php
│   │   ├── MailerVariableService.php
│   │   └── MailerVariableValueService.php
│   ├── Jobs/
│   │   └── SendEndpointEmailJob.php
│   └── Providers/
│       └── MailerServiceProvider.php
├── database/
│   ├── migrations/ - Database schema for all mailer tables
│   └── seeders/ - Initial data and example seeders
├── config/
│   └── mailer.php
├── routes/
│   ├── managers.php - Web UI routes
│   └── api/
│       └── endpoints.php - API routes
└── resources/views/
    └── mailers/ - Blade templates for UI
```

## License

Proprietary - Alsernet
