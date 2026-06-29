# Changelog

All notable changes to the Mailrelay module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-01-25

### 🎉 Major Release - Multi-Provider System

Esta versión mayor introduce un sistema completo de múltiples proveedores de email con integración total al módulo Mailer.

### Added

#### Multi-Provider Architecture
- ✨ **5 Email Providers**: Soporte nativo para Mailrelay, Mailtrap, SendGrid, AWS SES y Postmark
- 🏗️ **Strategy Pattern**: Arquitectura extensible con `MailProviderInterface`
- 🔧 **ProviderManager**: Factory service para gestión centralizada de providers
- 🔐 **Encrypted Credentials**: Almacenamiento seguro con Laravel encrypted casting
- ⚡ **Rate Limiting**: Respeto automático de límites por provider
- 📦 **Batch Processing**: Chunking inteligente de destinatarios
- 🔄 **Failover Support**: Sistema de prioridades para backup automático

#### Provider Implementations
- `MailrelayProvider` - Integración completa con Mailrelay API
- `MailtrapProvider` - Provider de testing/staging
- `SendGridProvider` - Twilio SendGrid con soporte de templates
- `AwsSesProvider` - Amazon SES con CloudWatch integration
- `PostmarkProvider` - Transactional email specialist

#### Database
- 📊 **mail_providers table**: Configuración de providers con credenciales encriptadas
- 🔗 **mail_provider_id**: Foreign key en campaigns para selección de provider
- 🗄️ **Migrations**: Schema completo con índices optimizados

#### REST API
- 🌐 **API v1**: Endpoints versionados con `/api/v1/` prefix
- 🔒 **Sanctum Auth**: Autenticación segura para todas las rutas
- 📝 **API Resources**: `MailProviderResource`, `CampaignResource`
- 📋 **API Controllers**: `MailProviderApiController`, `CampaignApiController`
- ✅ **Full CRUD**: Operaciones completas para providers y campaigns
- 🎯 **Custom Actions**: test, setDefault, toggleActive, duplicate, preview, sendTest, send, schedule, stats

#### Authorization
- 🛡️ **MailProviderPolicy**: Lógica de negocio para providers
  - No eliminar provider por defecto
  - No eliminar provider con campañas activas
  - No desactivar provider por defecto
- 🛡️ **CampaignPolicy**: Lógica de negocio para campaigns
  - No editar campañas enviadas
  - No eliminar campañas en envío o enviadas
  - Solo draft campaigns pueden ser programadas
- 🔐 **Permissions**: 4 nuevos permisos para gestión de providers

#### Services
- 🔄 **CampaignService**: Orquestación completa de campañas
  - `create()`, `update()`, `send()`, `sendAsync()`
  - `schedule()`, `sendTest()`, `getPreview()`, `duplicate()`
- 🎨 **CampaignRendererService**: Renderizado de templates con integración Mailer
- 🏭 **ProviderManager**: Factory pattern para instancias de providers
  - `driver()`, `byId()`, `default()`
  - `testProvider()`, `validateCredentials()`

#### Commands
- 🖥️ **mailrelay:list-providers**: Listar todos los providers configurados
  - Opciones: `--inactive`, `--json`
  - Tabla formateada con estadísticas
  - Emojis de estado visual
- 🧪 **mailrelay:test-provider**: Probar conexiones de providers
  - Test individual por ID o driver
  - Test masivo con `--all`
  - Incluir inactivos con `--inactive`
  - Resultados detallados con tabla de información

#### Testing
- ✅ **MailProviderApiTest**: 15+ tests para API de providers
  - Authorization tests
  - CRUD operations
  - Business logic validation
- ✅ **CampaignApiTest**: 15+ tests para API de campaigns
  - Status-based tests
  - Filter and search tests
  - Policy enforcement tests

#### Factories
- 🏭 **MailProviderFactory**: Factory completo con estados
  - `default()`, `active()`, `inactive()`
  - `connected()`, `disconnected()`
  - Credenciales realistas por driver
- 🏭 **CampaignFactory**: Factory completo con estados
  - `draft()`, `scheduled()`, `sending()`, `sent()`
  - `withTemplate()`, `withTracking()`, `withoutTracking()`

#### Seeders
- 🌱 **MailProviderSeeder**: 5 providers de ejemplo
  - Credenciales ficticias (requieren configuración)
  - Configuración de prioridades
  - Metadata descriptiva
- 🔐 **MailrelayPermissionsSeeder**: Permisos actualizados
  - 4 nuevos permisos de providers
  - Roles Marketing Manager y Subscriber Manager

#### Documentation
- 📚 **README.md**: Documentación completa actualizada
  - Arquitectura multi-provider
  - Ejemplos de uso API y PHP
  - Guía de configuración por provider
  - Rate limits por provider
  - Comandos Artisan
- 📝 **CHANGELOG.md**: Este archivo

### Changed

#### Campaign Model
- 🔄 **mail_provider_id**: Nueva relación con MailProvider
- 🔄 **mailProvider()**: Nuevo método de relación BelongsTo
- 🎨 **mailerTemplate()**: Integración con Mailer module
- 🌐 **language()**: Soporte multi-idioma integrado

#### Mailer Integration
- 🔗 **Template Reuse**: Reutilización completa de MailerTemplate
- 🎨 **Layout Support**: Soporte de layouts y componentes
- 🔤 **Variables**: Sistema de variables dinámicas
- 🌍 **Multi-language**: Integración con lang_id

#### Web UI
- 🎨 **Provider Management**: Vistas CRUD para providers
- 📊 **Campaign Management**: Vistas mejoradas con selector de provider
- 🔍 **Provider Selector**: Dropdown dinámico en campaign form

### Fixed
- 🐛 **Connection OK Field**: Validación correcta de conexiones
- 🔒 **Credential Security**: Encriptación automática en base de datos
- ⚡ **Rate Limit Respect**: Delays correctos entre batches
- 🔄 **Default Provider Logic**: Solo un provider default a la vez

### Security
- 🔐 **Encrypted Credentials**: Cast automático para API keys
- 🛡️ **Policy Enforcement**: Authorization en todos los endpoints
- 🔒 **Sanctum Auth**: API protegida con tokens
- ✅ **Permission Checks**: Validación granular de permisos

### Performance
- ⚡ **Provider Caching**: Cache de instancias en ProviderManager
- 📦 **Batch Processing**: Chunking eficiente de destinatarios
- 🔄 **Async Jobs**: Queue processing para envíos masivos
- 💾 **Eager Loading**: Optimización de queries con relaciones

### Dependencies
- No new external dependencies
- Uses existing Laravel stack (Sanctum, Policies, Queues)

### Migration Path

```bash
# 1. Ejecutar migraciones
php artisan migrate

# 2. Ejecutar seeders
php artisan db:seed --class=Modules\\Mailrelay\\Database\\Seeders\\MailrelayPermissionsSeeder
php artisan db:seed --class=Modules\\Mailrelay\\Database\\Seeders\\MailProviderSeeder

# 3. Configurar credenciales reales en panel de administración
# Visita: /managers/mailrelay/providers

# 4. Probar conexiones
php artisan mailrelay:test-provider --all
```

### Breaking Changes
- ⚠️ **Campaign Model**: Nuevo campo `mail_provider_id` (nullable para compatibilidad)
- ⚠️ **Routes**: Nuevas rutas API en `/api/v1/`
- ⚠️ **Permissions**: 4 nuevos permisos requeridos para gestión de providers

### Notes
- 📌 Providers de ejemplo incluyen credenciales FICTICIAS
- 📌 Configurar credenciales reales antes de usar en producción
- 📌 Ejecutar tests después de configurar providers
- 📌 AWS SES requiere verificación de dominio
- 📌 SendGrid y Postmark requieren cuentas verificadas

---

## [1.x.x] - Previous Versions

Ver historial de git para versiones anteriores:
```bash
git log --oneline modules/Mailrelay/
```

---

## Unreleased

### Planned Features
- [ ] Template builder visual drag & drop
- [ ] Advanced A/B testing analytics
- [ ] Webhook handlers para todos los providers
- [ ] Provider auto-failover inteligente
- [ ] Campaign analytics dashboard
- [ ] SMS provider support (Twilio, Vonage)
- [ ] Push notification support (FCM, APNS)

---

**Mantenido por**: Development Team
**Última actualización**: 2026-01-25
