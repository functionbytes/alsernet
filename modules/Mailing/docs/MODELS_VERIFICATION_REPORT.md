# Reporte de Verificación: Modelos Acelle → Módulo Mailing

**Fecha de Generación:** 29 de enero de 2026
**Ejecutado por:** Agente de Verificación de Modelos
**Versión:** 1.0

---

## 📊 Resumen Ejecutivo

Este reporte proporciona un análisis exhaustivo del estado de migración de los modelos de Acelle Mail al módulo Mailing del proyecto Alsernet. Se han identificado **107 modelos** en Acelle Mail y se ha verificado cuáles han sido migrados, cuáles faltan, y cuáles no deben migrarse por diseño.

### Estadísticas Generales

| Categoría | Cantidad | Porcentaje |
|-----------|----------|------------|
| **Total de modelos en Acelle** | 107 | 100% |
| ✅ **Modelos migrados** | 23 | 21.5% |
| ❌ **Modelos faltantes** | 59 | 55.1% |
| ⚠️ **NO migrar (usar Alsernet)** | 5 | 4.7% |
| 🤔 **A evaluar según necesidad** | 20 | 18.7% |

### Progreso de Migración

**Modelos críticos migrados:** 28.0% (23 de 82 modelos aplicables)

> **Nota:** El cálculo excluye los 5 modelos que NO deben migrarse y los 20 modelos a evaluar.

---

## ✅ Modelos Migrados (23)

Los siguientes modelos de Acelle han sido migrados exitosamente al módulo Mailing:

| # | Modelo Acelle | Modelo Mailing | Estado |
|---|---------------|----------------|--------|
| 1 | `Automation2` | `Automation` | Renombrado |
| 2 | `BounceHandler` | `BounceHandler` | Match exacto |
| 3 | `BounceLog` | `BounceLog` | Match exacto |
| 4 | `Campaign` | `Campaign` | Match exacto |
| 5 | `CampaignLink` | `CampaignLink` | Match exacto |
| 6 | `CampaignWebhook` | `CampaignWebhook` | Match exacto |
| 7 | `Email` | `EmailTemplate` | Renombrado |
| 8 | `EmailVerificationServer` | `EmailVerificationServer` | Match exacto |
| 9 | `FeedbackLog` | `FeedbackLog` | Match exacto |
| 10 | `FeedbackLoopHandler` | `FeedbackLoopHandler` | Match exacto |
| 11 | `Field` | `Field` | Match exacto |
| 12 | `FieldOption` | `FieldOption` | Match exacto |
| 13 | `Language` | `Language` | Match exacto |
| 14 | `Layout` | `Layout` | Match exacto |
| 15 | `MailList` | `Lists` | Renombrado |
| 16 | `Segment` | `Segment` | Match exacto |
| 17 | `SegmentCondition` | `SegmentCondition` | Match exacto |
| 18 | `SendingServer` | `SendingServer` | Match exacto |
| 19 | `Setting` | `Setting` | Match exacto |
| 20 | `SubAccount` | `SubAccount` | Match exacto |
| 21 | `Subscriber` | `Subscriber` | Match exacto |
| 22 | `SubscriberField` | `SubscriberField` | Match exacto |
| 23 | `Template` | `Template` | Match exacto |

### Modelos Adicionales en Mailing (No en Acelle)

Los siguientes modelos existen en el módulo Mailing pero NO están en Acelle original:

1. `ActivityLog` - Sistema de auditoría
2. `ApiBatch` - Procesamiento batch de API
3. `Bounce` - Registro de rebotes (¿diferente a BounceLog?)
4. `BulkEmailSending` - Envío masivo de emails
5. `CampaignAnalytics` - Analíticas de campaña
6. `CampaignFolder` - Organización de campañas
7. `CampaignStatus` - Estados de campaña
8. `CronjobSetting` - Configuración de cron jobs
9. `CustomField` - Campos personalizados (¿diferente a Field?)
10. `EmailValidation` - Validación de emails
11. `EmailVerificationResult` - Resultados de verificación
12. `GeneralSetting` - Configuraciones generales
13. `Group` - Grupos de usuarios
14. `ImportJob` - Jobs de importación
15. `MailerSetting` - Configuración de mailer
16. `MailingGroup` - Grupos de mailing
17. `MediaFile` - Archivos multimedia
18. `MediaFolder` - Carpetas multimedia
19. `ResponseLog` - Logs de respuesta
20. `SmsSentMessage` - Mensajes SMS enviados
21. `SmsTransactional` - SMS transaccionales
22. `TemplateForm` - Formularios de template
23. `UnsubscribeEvent` - Eventos de desuscripción
24. `UrlSetting` - Configuración de URLs
25. `Webhook` - Webhooks genéricos

---

## ❌ Modelos Faltantes - DEBEN Migrarse (59)

Los siguientes modelos de Acelle **NO** han sido migrados y son necesarios para la funcionalidad completa del sistema:

### Tier 1: CRÍTICOS (Migrar Urgente)

| # | Modelo | Descripción | Prioridad |
|---|--------|-------------|-----------|
| 1 | `AutoTrigger` | Disparadores de automatización | 🔴 CRÍTICA |
| 2 | `Blacklist` | Lista negra de emails | 🔴 CRÍTICA |
| 3 | `CampaignsListsSegment` | Relación campaigns-lists-segments | 🔴 CRÍTICA |
| 4 | `ClickLog` | Registro de clicks en emails | 🔴 CRÍTICA |
| 5 | `Contact` | Información de contacto de listas | 🔴 CRÍTICA |
| 6 | `EmailLink` | Links en emails de automatización | 🔴 CRÍTICA |
| 7 | `EmailWebhook` | Webhooks para automation emails | 🔴 CRÍTICA |
| 8 | `IpLocation` | Geolocalización de IPs | 🔴 CRÍTICA |
| 9 | `JobMonitor` | Monitoreo de jobs en background | 🔴 CRÍTICA |
| 10 | `MailListsSendingServer` | Relación lists-sending servers | 🔴 CRÍTICA |
| 11 | `OpenLog` | Registro de aperturas de emails | 🔴 CRÍTICA |
| 12 | `Sender` | Identidades de remitente verificadas | 🔴 CRÍTICA |
| 13 | `SendingDomain` | Dominios de envío verificados | 🔴 CRÍTICA |
| 14 | `TrackingDomain` | Dominios personalizados de tracking | 🔴 CRÍTICA |
| 15 | `TrackingLog` | Log principal de tracking de envíos | 🔴 CRÍTICA |
| 16 | `UnsubscribeLog` | Registro de desuscripciones | 🔴 CRÍTICA |

### Tier 2: ALTA PRIORIDAD (Sending Servers)

| # | Modelo | Descripción | Prioridad |
|---|--------|-------------|-----------|
| 17 | `SendingServerAmazon` | Base para Amazon SES | 🟠 ALTA |
| 18 | `SendingServerAmazonApi` | Amazon SES API | 🟠 ALTA |
| 19 | `SendingServerAmazonSmtp` | Amazon SES SMTP | 🟠 ALTA |
| 20 | `SendingServerMailgun` | Base para Mailgun | 🟠 ALTA |
| 21 | `SendingServerMailgunApi` | Mailgun API | 🟠 ALTA |
| 22 | `SendingServerMailgunSmtp` | Mailgun SMTP | 🟠 ALTA |
| 23 | `SendingServerSendGrid` | Base para SendGrid | 🟠 ALTA |
| 24 | `SendingServerSendGridApi` | SendGrid API | 🟠 ALTA |
| 25 | `SendingServerSendGridSmtp` | SendGrid SMTP | 🟠 ALTA |
| 26 | `SendingServerSparkPost` | Base para SparkPost | 🟠 ALTA |
| 27 | `SendingServerSparkPostApi` | SparkPost API | 🟠 ALTA |
| 28 | `SendingServerSparkPostSmtp` | SparkPost SMTP | 🟠 ALTA |
| 29 | `SendingServerElasticEmail` | Base para ElasticEmail | 🟠 ALTA |
| 30 | `SendingServerElasticEmailApi` | ElasticEmail API | 🟠 ALTA |
| 31 | `SendingServerElasticEmailSmtp` | ElasticEmail SMTP | 🟠 ALTA |
| 32 | `SendingServerBlastengine` | Base para Blastengine | 🟠 ALTA |
| 33 | `SendingServerBlastengineApi` | Blastengine API | 🟠 ALTA |
| 34 | `SendingServerBlastengineSmtp` | Blastengine SMTP | 🟠 ALTA |
| 35 | `SendingServerSendmail` | Sendmail transport | 🟠 ALTA |
| 36 | `SendingServerSmtp` | SMTP genérico | 🟠 ALTA |
| 37 | `SubAccountSendGrid` | Sub-cuentas de SendGrid | 🟠 ALTA |

### Tier 3: MEDIA PRIORIDAD (Infraestructura)

| # | Modelo | Descripción | Prioridad |
|---|--------|-------------|-----------|
| 38 | `Attachment` | Adjuntos de emails | 🟡 MEDIA |
| 39 | `Attribute` | Atributos de productos/contactos | 🟡 MEDIA |
| 40 | `Category` | Categorías de templates/contenido | 🟡 MEDIA |
| 41 | `Country` | Países (localization) | 🟡 MEDIA |
| 42 | `Currency` | Monedas | 🟡 MEDIA |
| 43 | `CustomerGroupSendingServer` | Relación grupos-servers | 🟡 MEDIA |
| 44 | `DeliveryHandler` | Manejador de entregas | 🟡 MEDIA |
| 45 | `FailedJob` | Jobs fallidos | 🟡 MEDIA |
| 46 | `Form` | Formularios de suscripción | 🟡 MEDIA |
| 47 | `Job` | Jobs genéricos | 🟡 MEDIA |
| 48 | `Log` | Sistema de logging | 🟡 MEDIA |
| 49 | `Notification` | Notificaciones del sistema | 🟡 MEDIA |
| 50 | `Page` | Páginas de landing | 🟡 MEDIA |
| 51 | `PlansEmailVerificationServer` | Relación planes-verification | 🟡 MEDIA |
| 52 | `PlansSendingServer` | Relación planes-sending servers | 🟡 MEDIA |
| 53 | `Plugin` | Sistema de plugins | 🟡 MEDIA |
| 54 | `Reply` | Respuestas a emails | 🟡 MEDIA |
| 55 | `Source` | Fuente de verificación de emails | 🟡 MEDIA |
| 56 | `TemplateCategory` | Categorías de templates | 🟡 MEDIA |
| 57 | `TemplatesCategory` | Tabla pivot templates-categories | 🟡 MEDIA |
| 58 | `Timeline` | Línea de tiempo de eventos | 🟡 MEDIA |
| 59 | `UserActivation` | Activación de usuarios | 🟡 MEDIA |

---

## ⚠️ Modelos NO Migrar - Usar Sistema Alsernet (5)

Los siguientes modelos **NO** deben migrarse porque Alsernet ya tiene sistemas equivalentes o mejores:

| # | Modelo Acelle | Sistema Alsernet Equivalente | Razón |
|---|---------------|------------------------------|-------|
| 1 | `User` | `App\Models\User` | Sistema de usuarios unificado |
| 2 | `Admin` | Sistema de roles (Spatie Permission) | RBAC con `admin` role |
| 3 | `AdminGroup` | Spatie Permission (roles/permissions) | Sistema de permisos moderno |
| 4 | `Customer` | `App\Models\User` con role `customer` | Multi-tenant via roles |
| 5 | `CustomerGroup` | Grupos/Roles de Spatie | Grouping via roles/permissions |

### Implicaciones de NO Migrar Estos Modelos

**Cambios necesarios en otros modelos:**

```php
// ANTES (Acelle)
public function customer() {
    return $this->belongsTo('Acelle\Model\Customer');
}

public function admin() {
    return $this->belongsTo('Acelle\Model\Admin');
}

// DESPUÉS (Mailing Module)
public function user() {
    return $this->belongsTo('App\Models\User');
}

// Verificar roles en controladores:
if ($user->hasRole('mailing_admin')) { ... }
if ($user->hasRole('mailing_customer')) { ... }
```

**Modelos afectados que requieren actualización:**
- `Campaign` - Relación a customer/admin
- `MailList` (Lists) - Relación a customer
- `Automation` - Relación a customer
- `Template` - Relación a customer/admin
- `SendingServer` - Relación a customer/admin
- `TrackingLog` - Relación a customer
- Todos los logs de tracking
- Y otros...

---

## 🤔 Modelos a Evaluar - Según Necesidad (20)

Los siguientes modelos deben ser evaluados según los requisitos del proyecto. Migrar solo si la funcionalidad es necesaria:

### Billing & Subscriptions (6 modelos)

| # | Modelo | Descripción | ¿Migrar? |
|---|--------|-------------|----------|
| 1 | `Plan` | Planes de suscripción | Evaluar si necesario |
| 2 | `PlanGeneral` | Planes generales | Evaluar si necesario |
| 3 | `Subscription` | Suscripciones de clientes | Evaluar si necesario |
| 4 | `SubscriptionLog` | Historial de suscripciones | Evaluar si necesario |
| 5 | `SubscriptionsEmailVerificationServer` | Relación subs-verification | Evaluar si necesario |
| 6 | `Transaction` | Transacciones de pago | Evaluar si necesario |

**Pregunta clave:** ¿El módulo Mailing ofrecerá planes de pago/subscripciones, o será parte del sistema general de Alsernet?

### Facturación (7 modelos)

| # | Modelo | Descripción | ¿Migrar? |
|---|--------|-------------|----------|
| 7 | `Invoice` | Facturas | Evaluar si necesario |
| 8 | `InvoiceChangePlan` | Facturas por cambio de plan | Evaluar si necesario |
| 9 | `InvoiceItem` | Items de factura | Evaluar si necesario |
| 10 | `InvoiceNewSubscription` | Facturas nueva suscripción | Evaluar si necesario |
| 11 | `InvoiceRenewSubscription` | Facturas renovación | Evaluar si necesario |
| 12 | `BillingAddress` | Direcciones de facturación | Evaluar si necesario |
| 13 | `Transaction` | (listado arriba) | Evaluar si necesario |

**Pregunta clave:** ¿Se facturará desde el módulo Mailing o desde sistema central?

### E-commerce (3 modelos)

| # | Modelo | Descripción | ¿Migrar? |
|---|--------|-------------|----------|
| 14 | `Product` | Productos para vender | Evaluar si necesario |
| 15 | `ProductAttribute` | Atributos de productos | Evaluar si necesario |
| 16 | `Order` | Órdenes de compra | Evaluar si necesario |

**Pregunta clave:** ¿El módulo Mailing venderá productos directamente, o solo enviará emails de campañas sobre productos externos?

### Integraciones Externas (4 modelos)

| # | Modelo | Descripción | ¿Migrar? |
|---|--------|-------------|----------|
| 17 | `Funnel` | Funnels de conversión | Evaluar si necesario |
| 18 | `Website` | Websites vinculados | Evaluar si necesario |
| 19 | `WooCommerce` | Integración WooCommerce | Evaluar si necesario |
| 20 | `Lazada` | Integración Lazada | Evaluar si necesario |
| - | `WpPost` | Posts de WordPress | Evaluar si necesario |

**Pregunta clave:** ¿Qué integraciones externas son necesarias? (WooCommerce, Lazada, WordPress, etc.)

---

## 🎯 Plan de Acción Recomendado

### Fase 1: Modelos Críticos (Prioridad 🔴)

**Duración estimada:** 2-3 semanas

Migrar los siguientes 16 modelos CRÍTICOS:

1. `TrackingLog` ⚠️ **CRÍTICO** - Base del sistema de tracking
2. `OpenLog` - Tracking de aperturas
3. `ClickLog` - Tracking de clicks
4. `UnsubscribeLog` - Tracking de desuscripciones
5. `CampaignsListsSegment` - Relación campaigns-lists-segments
6. `MailListsSendingServer` - Relación lists-sending servers
7. `AutoTrigger` - Disparadores de automatización
8. `EmailLink` - Links en automation emails
9. `EmailWebhook` - Webhooks de automation
10. `Blacklist` - Lista negra de emails
11. `Contact` - Contactos de listas
12. `Sender` - Identidades verificadas
13. `SendingDomain` - Dominios verificados
14. `TrackingDomain` - Dominios de tracking
15. `IpLocation` - Geolocalización
16. `JobMonitor` - Monitoreo de jobs

### Fase 2: Sending Servers (Prioridad 🟠)

**Duración estimada:** 2 semanas

Migrar todos los **21 modelos** de Sending Servers:

- Amazon (3): `SendingServerAmazon`, `SendingServerAmazonApi`, `SendingServerAmazonSmtp`
- Mailgun (3): `SendingServerMailgun`, `SendingServerMailgunApi`, `SendingServerMailgunSmtp`
- SendGrid (4): `SendingServerSendGrid`, `SendingServerSendGridApi`, `SendingServerSendGridSmtp`, `SubAccountSendGrid`
- SparkPost (3): `SendingServerSparkPost`, `SendingServerSparkPostApi`, `SendingServerSparkPostSmtp`
- ElasticEmail (3): `SendingServerElasticEmail`, `SendingServerElasticEmailApi`, `SendingServerElasticEmailSmtp`
- Blastengine (3): `SendingServerBlastengine`, `SendingServerBlastengineApi`, `SendingServerBlastengineSmtp`
- Otros (2): `SendingServerSendmail`, `SendingServerSmtp`

### Fase 3: Infraestructura (Prioridad 🟡)

**Duración estimada:** 2-3 semanas

Migrar los **22 modelos** de infraestructura restantes:

- Templates: `Category`, `TemplateCategory`, `TemplatesCategory`
- Forms & Pages: `Form`, `Page`
- Localization: `Country`, `Currency`
- System: `Notification`, `Timeline`, `Log`, `FailedJob`, `Job`, `Plugin`
- Attachments: `Attachment`
- Relations: `CustomerGroupSendingServer`, `PlansSendingServer`, `PlansEmailVerificationServer`
- Otros: `Attribute`, `DeliveryHandler`, `Reply`, `Source`, `UserActivation`

### Fase 4: Decisión sobre Modelos "Evaluate"

**Duración estimada:** 1 semana (análisis) + desarrollo según decisión

1. **Reunión de stakeholders** - Decidir qué funcionalidad se necesita:
   - ¿Billing/Subscriptions interno o externo?
   - ¿E-commerce integrado o solo campañas?
   - ¿Qué integraciones externas son prioritarias?

2. **Migrar según decisión** - Solo los modelos necesarios

---

## 📋 Checklist de Verificación Post-Migración

Una vez migrados todos los modelos necesarios:

### Verificación de Modelos

- [ ] Todos los modelos críticos (Tier 1) están migrados
- [ ] Todos los Sending Servers necesarios están migrados
- [ ] Namespaces actualizados: `Modules\Mailing\Models\`
- [ ] Relaciones verificadas entre modelos
- [ ] Traits aplicados correctamente (`HasUid`, `HasCache`, `TrackJobs`)

### Verificación de Relaciones

- [ ] Relaciones `belongsTo(User::class)` en lugar de `Customer`/`Admin`
- [ ] Foreign keys correctas en migraciones
- [ ] Pivot tables creadas (campaigns_lists_segments, mail_lists_sending_servers, etc.)
- [ ] Relaciones polimórficas verificadas

### Verificación de Base de Datos

- [ ] Migraciones ejecutadas sin errores
- [ ] Índices creados correctamente
- [ ] Foreign keys aplicadas
- [ ] Seeders ejecutados correctamente

### Verificación de Funcionalidad

- [ ] Tests unitarios para cada modelo
- [ ] Tests de relaciones
- [ ] Tests de scopes
- [ ] Tests de métodos críticos

---

## 📝 Notas Técnicas

### Mapeo de Nombres

Algunos modelos han sido renombrados en la migración:

| Acelle | Mailing | Razón del Cambio |
|--------|---------|------------------|
| `Automation2` | `Automation` | Simplificación de nombre |
| `Email` | `EmailTemplate` | Mayor claridad semántica |
| `MailList` | `Lists` | Nombre más corto |

### Convenciones de Namespace

```php
// Antes (Acelle)
namespace Acelle\Model;
use Acelle\Model\Campaign;

// Después (Mailing Module)
namespace Modules\Mailing\Models;
use Modules\Mailing\Models\Campaign;
```

### Prefijo de Tablas

Todas las tablas del módulo Mailing usan el prefijo `mailing_`:

```php
// Ejemplo
protected $table = 'mailing_campaigns';
protected $table = 'mailing_subscribers';
protected $table = 'mailing_tracking_logs';
```

---

## 🔗 Referencias

- **Documentación de Análisis Original:** `ACELLE_MODELS_ANALYSIS.md`
- **Plan de Migración General:** `MIGRATION_PLAN.md`
- **Status de Migración:** `MIGRACION_ACELLE_STATUS.md`
- **Código fuente Acelle:** `/Users/functionbytes/Function/Coding/acelle/app/Model/`
- **Modelos Mailing:** `/Users/functionbytes/Function/Coding/system/modules/Mailing/app/Models/`

---

## 📊 Gráfico de Progreso

```
Progreso de Migración de Modelos:

✅ Migrados (23/107):     [####.................] 21.5%
❌ Faltantes (59/107):    [###########..........] 55.1%
⚠️  No Migrar (5/107):    [#....................] 4.7%
🤔 Evaluar (20/107):      [####.................] 18.7%

Total Aplicable: 82 modelos (107 - 5 no_migrar - 20 evaluar)
Migrados de Aplicables: 23/82 = 28.0%
```

---

**Fin del Reporte**
**Generado:** 2026-01-29
**Próxima Verificación:** Después de completar Fase 1
