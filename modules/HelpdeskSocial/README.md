# HelpdeskSocial

Módulo de gestión automatizada de comentarios y mensajes de redes sociales (Meta: Facebook, Instagram, WhatsApp).

---

## Índice

- [Requisitos](#requisitos)
- [Activación](#activación)
- [Variables de entorno](#variables-de-entorno)
- [Migraciones y permisos](#migraciones-y-permisos)
- [Colas (queues)](#colas-queues)
- [Schedule (tareas programadas)](#schedule)
- [Permisos Spatie](#permisos-spatie)
- [Ejecutar tests](#ejecutar-tests)
- [Arquitectura del módulo](#arquitectura-del-módulo)
- [Documentación adicional](#documentación-adicional)

---

## Requisitos

- Módulo `Helpdesk` habilitado y migrado
- Redis configurado (colas y cache de analytics)
- Laravel Reverb corriendo (broadcasting en tiempo real)
- Laravel Horizon corriendo (workers de colas)
- Variables de entorno Meta configuradas (ver abajo)

---

## Activación

El módulo viene deshabilitado por defecto. Para activarlo:

```bash
php artisan module:enable HelpdeskSocial
php artisan optimize:clear
```

Para deshabilitar:

```bash
php artisan module:disable HelpdeskSocial
php artisan optimize:clear
```

---

## Variables de entorno

Añade al `.env`:

```env
# Meta / Facebook / Instagram
HELPDESK_SOCIAL_META_ENABLED=true
HELPDESK_SOCIAL_META_APP_ID=tu_app_id
HELPDESK_SOCIAL_META_APP_SECRET=tu_app_secret
HELPDESK_SOCIAL_META_API_VERSION=v25.0
HELPDESK_SOCIAL_META_VERIFY_TOKEN=token_secreto_aleatorio

# WhatsApp (opcional)
HELPDESK_SOCIAL_WHATSAPP_ENABLED=false
HELPDESK_SOCIAL_WHATSAPP_VERIFY_TOKEN=token_secreto_whatsapp

# Auto-reply
HELPDESK_SOCIAL_AUTO_REPLY_ENABLED=true

# Clasificación de intenciones: rules | openai | hybrid
HELPDESK_SOCIAL_INTENT_PROVIDER=rules
HELPDESK_SOCIAL_OPENAI_MODEL=gpt-4o-mini   # solo si provider=openai|hybrid
```

> **Seguridad crítica:** Si `HELPDESK_SOCIAL_META_APP_SECRET` o `HELPDESK_SOCIAL_META_VERIFY_TOKEN` están vacíos, los webhooks son rechazados con 503. Nunca dejes estas variables en blanco en producción.

---

## Migraciones y permisos

```bash
# 1. Ejecutar migraciones del módulo
php artisan module:migrate HelpdeskSocial

# 2. Registrar permisos Spatie
php artisan module:seed HelpdeskSocial --class=HelpdeskSocialPermissionsSeeder

# 3. Limpiar cache de permisos
php artisan cache:clear
```

### Migración de permisos legacy

Si el sistema tenía instalada una versión anterior del módulo con los permisos viejos
(`helpdesksocial.manage-accounts`, `manage-rules`, `manage-templates`, `view-analytics`),
la migración `2026_05_21_085720_rename_legacy_helpdesksocial_permissions` los renombra
automáticamente al nuevo esquema granular. No requiere acción manual.

---

## Colas (queues)

El módulo usa tres queues dedicadas. Horizon debe tenerlas configuradas en `config/horizon.php`:

| Queue | Uso |
|-------|-----|
| `helpdesk-social-webhooks` | Recepción y parsing de webhooks entrantes |
| `helpdesk-social-processing` | Procesamiento de comentarios, notificaciones, SLA, asignación |
| `helpdesk-social-analytics` | Análisis de sentimiento, cálculo de métricas |

Iniciar worker manual (desarrollo):

```bash
php artisan queue:work --queue=helpdesk-social-webhooks,helpdesk-social-processing,helpdesk-social-analytics
```

---

## Schedule

Las siguientes tareas están registradas en `routes/console.php`:

| Comando | Frecuencia | Descripción |
|---------|-----------|-------------|
| `helpdesk-social:sync-comments` | Cada 15 min | Sincroniza comentarios desde Meta API |
| `helpdesk-social:check-sla-breaches --notify` | Cada 5 min | Detecta y notifica incumplimientos de SLA |
| `helpdesk-social:sync-mentions` | Cada 30 min | Sincroniza menciones |
| `helpdesk-social:sync-competitor-metrics` | Diario 04:00 | Sincroniza métricas de competidores |
| `helpdesk-social:health-check --notify` | Diario 08:00 | Chequeo de salud de cuentas conectadas |
| `CalculateSocialMetricsJob` | Diario 06:00 | Agrega métricas del día anterior |

El scheduler de Laravel debe estar activo:

```bash
# En producción (crontab)
* * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

---

## Permisos Spatie

El módulo usa permisos granulares con la convención `helpdesksocial.{entidad}.{acción}`:

| Permiso | Descripción |
|---------|-------------|
| `helpdesksocial.view` | Acceso general al módulo |
| `helpdesksocial.accounts.view/create/update/delete/manage` | Gestión de cuentas sociales |
| `helpdesksocial.comments.view/reply/assign/escalate/spam/bulk/manage` | Bandeja de comentarios |
| `helpdesksocial.conversations.view/update/delete` | Conversaciones |
| `helpdesksocial.rules.view/create/update/delete/manage` | Reglas automáticas |
| `helpdesksocial.templates.view/create/update/delete/manage` | Plantillas de respuesta |
| `helpdesksocial.tags.view/create/update/delete` | Etiquetas |
| `helpdesksocial.sla.view/create/update/delete` | Políticas SLA |
| `helpdesksocial.mentions.view/update/delete` | Menciones |
| `helpdesksocial.assignment-rules.view/create/update/delete` | Reglas de asignación |
| `helpdesksocial.approvals.view/create/approve/reject` | Aprobaciones |
| `helpdesksocial.competitors.view/create/update/delete` | Seguimiento de competidores |
| `helpdesksocial.notes.view/create/delete` | Notas internas |
| `helpdesksocial.analytics.view` | Analítica y reportes |
| `helpdesksocial.settings.view/update` | Configuración del módulo |

---

## Ejecutar tests

### Prerrequisitos del entorno de tests

El módulo necesita que la base de datos de tests tenga el esquema completo y el módulo habilitado:

```bash
# 1. Habilitar el módulo
php artisan module:enable HelpdeskSocial

# 2. Crear/migrar la base de datos de tests
php artisan migrate --env=testing --force

# 3. (Opcional) Verificar que las tablas de Spatie existen
php artisan db:show --env=testing 2>/dev/null | grep permission
```

### Correr los tests

```bash
# Suite completa del módulo
php artisan test --compact modules/HelpdeskSocial/tests/

# Solo tests de API
php artisan test --compact modules/HelpdeskSocial/tests/Feature/Api/

# Solo tests web (panel admin)
php artisan test --compact modules/HelpdeskSocial/tests/Feature/Web/

# Tests unitarios de servicios
php artisan test --compact modules/HelpdeskSocial/tests/Unit/

# Un test específico
php artisan test --compact --filter=test_nombre_del_test modules/HelpdeskSocial/tests/
```

### Estructura de tests

```
tests/
├── TestCase.php                    # Base: crea permisos en setUp()
├── Unit/
│   └── Services/
│       ├── IntentClassifierTest.php
│       ├── RuleBasedAutoReplyEngineTest.php
│       └── BroadcastEventsTest.php
└── Feature/
    ├── Api/
    │   ├── SocialAnalyticsTest.php
    │   ├── SocialApprovalRequestsControllerTest.php
    │   ├── SocialAssignmentRulesControllerTest.php
    │   ├── SocialInboxTest.php
    │   ├── SocialMentionsControllerTest.php
    │   ├── SocialSlaPoliciesControllerTest.php
    │   └── SocialTemplatesTest.php
    └── Web/
        ├── SocialAccountsWebTest.php
        ├── SocialInboxWebTest.php
        ├── SocialRulesWebTest.php
        └── SocialTemplatesWebTest.php
```

---

## Arquitectura del módulo

```
app/
├── Console/Commands/       # 8 comandos Artisan
├── Contracts/              # Interfaces: SocialApiClient, WebhookParser, AutoReplyEngine...
├── Events/                 # SocialCommentReceived, SocialCommentReplied, IntentClassified...
├── Exports/                # SocialCommentsExport (FromQuery, streaming)
├── Http/
│   ├── Controllers/
│   │   ├── Api/            # 13 controllers REST (Sanctum)
│   │   ├── Managers/       # SocialSettingsController (panel admin)
│   │   ├── Settings/       # SocialModuleSettingsController
│   │   └── Webhooks/       # MetaWebhookController (público, rate-limited)
│   ├── Requests/           # 23 Form Requests con authorize() Spatie
│   └── Resources/          # 11 API Resources (camelCase, ISO8601)
├── Jobs/                   # 9 jobs encolados (tries=3, timeout, failed())
├── Listeners/              # 9 listeners (ShouldQueue, queue dedicada)
├── Middleware/             # LogSocialApiCalls
├── Models/                 # 16 modelos Eloquent
├── Notifications/          # 4 notificaciones (ShouldQueue)
├── Policies/               # 4 policies (Spatie + ownership check)
├── Providers/              # HelpdeskSocialServiceProvider, EventServiceProvider
├── Repositories/           # SocialCommentRepository, SocialAccountRepository, SocialRuleRepository
├── Services/
│   ├── Channels/           # MetaApiClient, MetaWebhookParser, MetaWebhookVerifier, SocialChannelRegistry
│   ├── Classifiers/        # RulesIntentClassifier, OpenAiIntentClassifier, HybridIntentClassifier
│   ├── Engines/            # RuleBasedAutoReplyEngine
│   └── *.php               # AuditLog, CrisisMode, Sentiment, SLA, SmartAssignment...
└── Widgets/                # SocialInboxWidget
```

### Flujo de un comentario entrante

```
Meta → POST /webhooks/helpdesk/social/meta
         │
         ├─ Verifica firma HMAC SHA-256 (hash_equals)
         ├─ Verifica idempotencia (cache 24h por payload hash)
         │
         └─ ProcessSocialCommentJob (queue: helpdesk-social-processing)
                │
                ├─ Persiste SocialComment (status=pending)
                ├─ Dispara SocialCommentReceived
                │     ├─ BroadcastSocialComment → Reverb (tiempo real)
                │     ├─ AutoAssignCommentListener → SmartAssignmentService
                │     ├─ ApplySlaPolicyListener → SlaTrackingService
                │     └─ SendNewSocialCommentNotification → agentes
                │
                ├─ ClassifyIntentJob → IntentClassificationService
                │     └─ AutoTagOnIntentClassified
                │
                └─ RuleBasedAutoReplyEngine
                      └─ Si hay match → responde en Meta API → SocialCommentReplied
```

---

## Documentación adicional

- [`docs/WEBHOOK_SETUP.md`](docs/WEBHOOK_SETUP.md) — Guía paso a paso para configurar webhooks en Meta Developers
