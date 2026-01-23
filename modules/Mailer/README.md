# Mailer Module

Email template management and endpoint configuration module for Alsernet. Provides comprehensive email template management, variable definitions, layout components, and HTTP API endpoints for sending emails.

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
│       ├── MailerServiceProvider.php
│       └── RouteServiceProvider.php
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
