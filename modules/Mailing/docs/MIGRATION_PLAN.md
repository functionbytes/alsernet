# Plan de Migración: Acelle Mail → Módulo Mailing (Alsernet)

**Fecha de Creación:** 2026-01-29
**Versión:** 1.0
**Proyecto Origen:** Acelle Mail (Laravel 8)
**Proyecto Destino:** Alsernet/Mailing Module (Laravel 12)

---

## 📋 Resumen Ejecutivo

Este documento detalla el plan completo de migración del sistema Acelle Mail hacia el módulo Mailing del proyecto Alsernet. La migración implica adaptar aproximadamente:

- **108 Modelos Eloquent**
- **108 Controladores**
- **297 Migraciones de Base de Datos**
- **930 Vistas Blade**
- **697 Assets Frontend (JS/CSS)**
- **20+ Jobs en Queue**
- **13+ Service Providers**

**Complejidad Estimada:** Alta
**Tiempo Estimado Total:** 12-16 semanas (480-640 horas)
**Riesgo General:** Medio-Alto

---

## 🎯 Objetivos de la Migración

### Objetivos Principales
1. ✅ Migrar funcionalidad completa de email marketing a módulo independiente
2. ✅ Adaptar código de Laravel 8 a Laravel 12
3. ✅ Mantener compatibilidad con arquitectura modular de Alsernet
4. ✅ Integrar con sistemas existentes (Auth, Permissions, Media)
5. ✅ Preservar toda la lógica de negocio de campañas

### Objetivos Secundarios
1. Modernizar frontend a Bootstrap 5.3 + Modernize template
2. Implementar tests unitarios y de integración
3. Optimizar queries y rendimiento
4. Documentar toda la funcionalidad migrada

---

## 📦 Dependencias Externas Requeridas

### Paquetes a Agregar al Composer Principal

```json
{
  "require": {
    // Email & SMTP
    "aws/aws-sdk-php": "^3.19",
    "aws/aws-php-sns-message-validator": "^1.1",
    "swiftmailer/swiftmailer": "~6.0",
    "mailgun/mailgun-php": "^3.5",
    "sendgrid/sendgrid": "~7",
    "sendgrid/smtpapi": "^0.5.0",
    "sparkpost/sparkpost": "^2.1",
    "rdok/elasticemail-php": "^2.2",

    // Email Parsing & Validation
    "php-mime-mail-parser/php-mime-mail-parser": "^5.0",
    "zytzagoo/smtp-validate-email": "^1.1",

    // HTML Processing
    "kub-at/php-simple-html-dom-parser": "^1.9",
    "soundasleep/html2text": "^2.1",
    "mews/purifier": "^3.2",

    // CSV & Data Import
    "league/csv": "9.7.4",
    "league/pipeline": "^1.0",

    // Template Engine
    "twig/twig": "^3.0",

    // Spintax (contenido variable)
    "bjoernffm/spintax": "^1.0",

    // GeoIP
    "geoip2/geoip2": "~2.0",

    // AI Integration (OpenAI)
    "orhanerday/open-ai": "^4.7",

    // External APIs
    "facebook/php-ads-sdk": "^11.0",
    "twilio/sdk": "^7.4",
    "getbrevo/brevo-php": "1.x.x",

    // Utilities
    "galbar/jsonpath": "^1.2"
  }
}
```

### Paquetes Ya Instalados en Alsernet
- ✅ `guzzlehttp/guzzle`
- ✅ `barryvdh/laravel-dompdf`
- ✅ `maatwebsite/excel`
- ✅ `intervention/image`

---

## 🗂️ Orden de Migración Recomendado

### Fase 1: Fundamentos (Semanas 1-2)

#### 1.1 Configuración Base
- **Archivos:**
  - `config/` → Adaptar configuraciones a módulo
  - `.env.example` → Documentar variables necesarias
  - Service Providers básicos

- **Esfuerzo:** 16 horas
- **Riesgo:** Bajo
- **Prioridad:** CRÍTICA

#### 1.2 Traits y Contratos
- **Archivos:**
  - `app/Library/Traits/` → `app/Traits/`
    - `HasTemplate.php`
    - `HasCache.php`
    - `HasUid.php`
    - `Trackable.php`
    - `TrackJobs.php`
  - `app/Library/Contracts/` → `app/Contracts/`

- **Esfuerzo:** 12 horas
- **Riesgo:** Bajo
- **Prioridad:** CRÍTICA

#### 1.3 Helpers y Utilidades
- **Archivos:**
  - `app/Helpers/helpers.php` → `app/Helpers/MailingHelpers.php`
  - `app/Helpers/namespaced_helpers.php`
  - `app/Library/Tool.php`
  - `app/Library/File.php`
  - `app/Library/StringHelper.php`

- **Adaptaciones:**
  - Renombrar funciones globales para evitar colisiones
  - Namespace todas las funciones helper
  - Convertir helpers estáticos a clases invokables

- **Esfuerzo:** 20 horas
- **Riesgo:** Medio
- **Prioridad:** ALTA

---

### Fase 2: Base de Datos (Semanas 3-4)

#### 2.1 Migraciones Core
**Orden de ejecución (respetando foreign keys):**

1. **Tablas base sin dependencias:**
   - `mailing_countries`
   - `mailing_currencies`
   - `mailing_languages`
   - `mailing_timezones`
   - `mailing_settings`

2. **Usuarios y autenticación (ADAPTAR, no migrar):**
   - ⚠️ **NO MIGRAR:** `mailing_users`, `mailing_admins`
   - ✅ **USAR:** Sistema Auth existente de Alsernet
   - ✅ **ADAPTAR:** Relaciones a `users` tabla principal

3. **Sending Servers y dominios:**
   - `mailing_sending_servers`
   - `mailing_sending_domains`
   - `mailing_tracking_domains`
   - `mailing_bounce_handlers`
   - `mailing_feedback_loop_handlers`

4. **Mail Lists y Subscribers:**
   - `mailing_mail_lists`
   - `mailing_fields`
   - `mailing_field_options`
   - `mailing_segments`
   - `mailing_segment_conditions`
   - `mailing_subscribers`
   - `mailing_subscriber_fields`

5. **Campaigns:**
   - `mailing_templates`
   - `mailing_layouts`
   - `mailing_campaigns`
   - `mailing_campaigns_lists_segments`
   - `mailing_campaign_links`
   - `mailing_campaign_webhooks`

6. **Tracking y Logs:**
   - `mailing_tracking_logs`
   - `mailing_open_logs`
   - `mailing_click_logs`
   - `mailing_bounce_logs`
   - `mailing_unsubscribe_logs`
   - `mailing_feedback_logs`

7. **Automatización:**
   - `mailing_automation2s`
   - `mailing_auto_triggers`
   - `mailing_emails` (automation emails)

8. **E-commerce & Billing (EVALUAR SI NECESARIO):**
   - `mailing_products`
   - `mailing_orders`
   - `mailing_invoices`
   - `mailing_subscriptions`

- **Total Migraciones:** ~85 (de 297 originales)
- **Esfuerzo:** 60 horas
- **Riesgo:** Alto
- **Prioridad:** CRÍTICA

#### 2.2 Seeders
- Adaptar seeders de países, idiomas, configuraciones
- **Esfuerzo:** 8 horas
- **Riesgo:** Bajo

---

### Fase 3: Modelos Eloquent (Semanas 5-6)

#### 3.1 Modelos Core (Orden de dependencias)

**Grupo A - Sin dependencias externas:**
1. `Country.php`
2. `Currency.php`
3. `Language.php`
4. `Layout.php`
5. `Setting.php`

**Grupo B - Sending Infrastructure:**
6. `SendingServer.php` + todos los subtipos:
   - `SendingServerAmazon.php`
   - `SendingServerMailgun.php`
   - `SendingServerSendGrid.php`
   - `SendingServerSparkPost.php`
   - `SendingServerElasticEmail.php`
   - `SendingServerSmtp.php`
   - `SendingServerSendmail.php`
7. `SendingDomain.php`
8. `TrackingDomain.php`
9. `BounceHandler.php`
10. `FeedbackLoopHandler.php`

**Grupo C - Mail Lists & Fields:**
11. `MailList.php` ⚠️ **CRÍTICO**
12. `Field.php`
13. `FieldOption.php`
14. `Segment.php`
15. `SegmentCondition.php`
16. `Subscriber.php` ⚠️ **CRÍTICO**
17. `SubscriberField.php`

**Grupo D - Templates & Campaigns:**
18. `Template.php`
19. `TemplateCategory.php`
20. `Campaign.php` ⚠️ **CRÍTICO**
21. `CampaignLink.php`
22. `CampaignWebhook.php`
23. `CampaignsListsSegment.php`

**Grupo E - Tracking & Logs:**
24. `TrackingLog.php`
25. `OpenLog.php`
26. `ClickLog.php`
27. `BounceLog.php`
28. `UnsubscribeLog.php`
29. `FeedbackLog.php`

**Grupo F - Automation:**
30. `Automation2.php` ⚠️ **COMPLEJO**
31. `AutomationElement.php`
32. `AutoTrigger.php`
33. `Email.php` (automation emails)
34. `EmailLink.php`
35. `EmailWebhook.php`

**Grupo G - Auxiliares:**
36. `Blacklist.php`
37. `Contact.php`
38. `Attachment.php`
39. `Timeline.php`
40. `Notification.php`

**Grupo H - Email Verification:**
41. `EmailVerificationServer.php`
42. `Source.php` (email verification results)

**Modelos a NO MIGRAR (usar sistema existente):**
- ❌ `User.php` → Usar `App\Models\User`
- ❌ `Admin.php` → Usar sistema de roles de Alsernet
- ❌ `AdminGroup.php` → Usar Spatie Permissions
- ❌ `Customer.php` → Usar `App\Models\User` con roles
- ❌ `CustomerGroup.php` → Usar grupos/roles existentes

**Modelos a EVALUAR (según necesidades):**
- ⚠️ `Plan.php` (planes de suscripción)
- ⚠️ `Subscription.php`
- ⚠️ `Invoice.php`
- ⚠️ `Product.php`
- ⚠️ `Order.php`
- ⚠️ `Funnel.php`
- ⚠️ `WooCommerce.php`
- ⚠️ `Lazada.php`

**Cambios necesarios en TODOS los modelos:**
```php
// ANTES (Acelle)
namespace Acelle\Model;
class Campaign extends Model

// DESPUÉS (Mailing)
namespace Modules\Mailing\Models;
class Campaign extends Model
```

- **Total Modelos a Migrar:** ~45 (de 108 originales)
- **Esfuerzo:** 90 horas
- **Riesgo:** Alto
- **Prioridad:** CRÍTICA

---

### Fase 4: Library Classes (Semanas 7-8)

#### 4.1 Core Library Classes
**Directorio:** `app/Library/` → `app/Library/`

**Prioridad CRÍTICA:**
1. `BaseCampaign.php` - Base de campañas
2. `RateTracker.php` - Control de tasa de envío
3. `RouletteWheel.php` - Distribución de sending servers
4. `InlineStyleWrapper.php` - CSS inline para emails
5. `IdentityStore.php` - Gestión de identidades DKIM/SPF

**Prioridad ALTA:**
6. `HtmlHandler/` - Procesamiento HTML
   - `InjectTrackingPixel.php`
   - `TransformUrl.php`
7. `Automation/` - Motor de automatización
   - `Action.php`
   - `Evaluate.php`
   - `Operate.php`
   - `Send.php`
   - `Trigger.php`
   - `Wait.php`

**Prioridad MEDIA:**
8. `Storage/` - Gestión de archivos
   - `S3.php`
   - Adaptar a `Spatie MediaLibrary` existente
9. `BillingManager.php` - Solo si se migra billing
10. `HookManager.php` - Webhooks

**NO MIGRAR (usar alternativas Laravel):**
- ❌ `AmazonSmtpTransport.php` → Usar drivers nativos de Laravel
- ❌ Clases de Payment Gateways → Evaluar si necesario

- **Esfuerzo:** 50 horas
- **Riesgo:** Alto
- **Prioridad:** CRÍTICA

---

### Fase 5: Jobs & Queue (Semana 9)

#### 5.1 Jobs Core
**Migrar con alta prioridad:**

1. **Campaign Jobs:**
   - `RunCampaign.php` ⚠️ **CRÍTICO**
   - `LoadCampaign.php`
   - `UpdateCampaignJob.php`
   - `SendMessage.php` ⚠️ **CRÍTICO**
   - `ExecuteCampaignCallback.php`

2. **Subscriber Jobs:**
   - `ImportSubscribersJob.php` ⚠️ **CRÍTICO**
   - `ImportSubscribers2.php`
   - `ExportSubscribersJob.php`
   - `VerifySubscriber.php`
   - `VerifyAndCreateSubscriber.php`
   - `VerifyMailListJob.php`
   - `UpdateSegmentJob.php`

3. **Automation Jobs:**
   - `ForceTriggerAutomation.php`
   - `UpdateAutomation.php`

4. **Auxiliary Jobs:**
   - `ImportBlacklistJob.php`
   - `SendConfirmationEmailJob.php`
   - `UpdateMailListJob.php`
   - `SyncProducts.php` (si e-commerce)

**Cambios necesarios en Jobs:**
```php
// ANTES
namespace Acelle\Jobs;
use Acelle\Model\Campaign;

// DESPUÉS
namespace Modules\Mailing\Jobs;
use Modules\Mailing\Models\Campaign;
```

**Configuración Queue:**
- Usar Redis existente de Alsernet
- Integrar con Laravel Horizon existente
- Configurar supervisord para workers dedicados

- **Total Jobs:** ~20
- **Esfuerzo:** 40 horas
- **Riesgo:** Medio-Alto
- **Prioridad:** CRÍTICA

---

### Fase 6: Controladores (Semanas 10-11)

#### 6.1 Controladores Core (Migración por Módulos)

**Módulo: Campaigns**
1. `CampaignController.php` ⚠️ **CRÍTICO - 2000+ líneas**
   - CRUD de campañas
   - Builder de templates
   - Preview & Test sends
   - Scheduling
   - Analytics

**Módulo: Lists & Subscribers**
2. `MailListController.php` ⚠️ **CRÍTICO**
3. `AudienceController.php`
4. `FieldController.php`
5. `SubscriberController.php` (dentro de MailListController)

**Módulo: Templates**
6. `TemplateController.php`
7. `LayoutController.php`

**Módulo: Automation**
8. `Automation2Controller.php` ⚠️ **COMPLEJO**
9. `AutoTriggerController.php`

**Módulo: Delivery & Tracking**
10. `DeliveryController.php`
11. `TrackingController.php` (implícito en logs)

**Módulo: Settings**
12. `SendingServerController.php`
13. `SendingDomainController.php`
14. `EmailVerificationServerController.php`
15. `BlacklistController.php`

**Módulo: Forms & Pages**
16. `FormController.php`
17. `PageController.php`

**Controladores API:**
- `Api/CampaignController.php`
- `Api/ListController.php`
- `Api/SubscriberController.php`

**NO MIGRAR (usar Alsernet):**
- ❌ `AdminController.php`
- ❌ `CustomerController.php`
- ❌ `AuthController.php`
- ❌ `AccountController.php`
- ❌ `PlanController.php` (evaluar)
- ❌ `InvoiceController.php` (evaluar)

**Cambios necesarios:**
```php
// ANTES
namespace Acelle\Http\Controllers;
Route::group(['prefix' => 'campaigns'], ...);

// DESPUÉS
namespace Modules\Mailing\Http\Controllers;
Route::group(['prefix' => 'mailing/campaigns'], ...);
```

- **Total Controladores:** ~25 (de 108)
- **Esfuerzo:** 100 horas
- **Riesgo:** Alto
- **Prioridad:** CRÍTICA

#### 6.2 Middlewares
**Adaptar:**
- Rate limiting → Usar Laravel 12 native
- Permission checks → Integrar con Spatie Permission existente

---

### Fase 7: Vistas Blade (Semanas 12-13)

#### 7.1 Layouts
**Archivos:**
- `resources/views/layouts/core/` → Adaptar a Modernize template
- Usar layout principal de Alsernet: `layouts.app`

- **Esfuerzo:** 16 horas
- **Riesgo:** Medio

#### 7.2 Vistas por Módulo

**Campaigns (~150 vistas):**
- `campaigns/index.blade.php`
- `campaigns/create.blade.php`
- `campaigns/edit.blade.php`
- `campaigns/show.blade.php`
- `campaigns/template/` (builder)
- `campaigns/setup/` (wizard)

**Lists & Subscribers (~120 vistas):**
- `lists/index.blade.php`
- `lists/show.blade.php`
- `subscribers/index.blade.php`
- `subscribers/import.blade.php`
- `fields/manage.blade.php`
- `segments/create.blade.php`

**Templates (~80 vistas):**
- `templates/index.blade.php`
- `templates/builder.blade.php`
- `templates/gallery.blade.php`

**Automation (~100 vistas):**
- `automation2/index.blade.php`
- `automation2/builder.blade.php` ⚠️ **COMPLEJO - Visual workflow**
- `automation2/trigger.blade.php`

**Settings (~60 vistas):**
- `sending_servers/`
- `sending_domains/`
- `email_verification/`

**Forms & Pages (~40 vistas):**
- `forms/builder.blade.php`
- `pages/editor.blade.php`

**Helpers & Components (~100 vistas):**
- Componentes reutilizables
- Modales
- Dropdowns
- Tables

**Reports & Analytics (~80 vistas):**
- Dashboards
- Charts
- Export views

**NO MIGRAR (redundante):**
- ❌ Vistas de Auth → Usar Alsernet
- ❌ Vistas de Admin → Usar Alsernet
- ❌ Vistas de Billing → Evaluar necesidad

**Adaptaciones necesarias:**

1. **Bootstrap 4 → Bootstrap 5.3:**
```html
<!-- ANTES -->
<div class="card-header bg-primary text-white">
<button class="btn btn-primary" data-toggle="modal">

<!-- DESPUÉS -->
<div class="card-header bg-primary text-white-50">
<button class="btn btn-primary" data-bs-toggle="modal">
```

2. **jQuery → Alpine.js/Vue 3 (opcional):**
- Evaluar migración progresiva
- Mantener jQuery para DevExpress widgets

3. **Icons: Font Awesome 6 OBLIGATORIO:**
```html
<!-- ❌ NUNCA usar Tabler -->
<i class="ti ti-upload"></i>

<!-- ✅ SIEMPRE usar Font Awesome -->
<i class="fas fa-upload"></i>
```

4. **Capitalización títulos:**
```html
<!-- ✅ Correcto -->
<h6><i class="fa fa-list me-2"></i>Lista de campañas</h6>

<!-- ❌ Incorrecto -->
<h6><i class="fa fa-list me-2"></i>Lista De Campañas</h6>
```

- **Total Vistas a Migrar:** ~730 (de 930)
- **Esfuerzo:** 150 horas
- **Riesgo:** Medio-Alto
- **Prioridad:** ALTA

---

### Fase 8: Frontend Assets (Semana 14)

#### 8.1 JavaScript
**Archivos críticos:**
- Campaign builder JS
- Automation workflow builder
- List import wizard
- Chart/analytics JS
- Form builder

**Adaptaciones:**
- Vite en lugar de Webpack Mix
- Modularizar código
- ES6+ syntax

**Librerías a mantener:**
- jQuery (para DevExpress)
- DevExpress widgets
- Chart.js
- Select2
- Dropzone.js

- **Esfuerzo:** 40 horas
- **Riesgo:** Medio

#### 8.2 CSS/SCSS
- Adaptar a TailwindCSS 4.0 + Bootstrap 5.3
- Mantener estilos específicos de email builder
- Responsive adjustments

- **Esfuerzo:** 30 horas
- **Riesgo:** Bajo-Medio

---

### Fase 9: Service Providers & Configuration (Semana 15)

#### 9.1 Service Providers
**Migrar:**
1. `MailerServiceProvider.php` ⚠️ **CRÍTICO**
2. `JobServiceProvider.php`
3. `EventServiceProvider.php`
4. `StorageServiceProvider.php`

**Registrar en:** `modules/Mailing/Providers/MailingServiceProvider.php`

#### 9.2 Events & Listeners
**Eventos críticos:**
- `CampaignSent`
- `SubscriberImported`
- `BounceReceived`
- `LinkClicked`
- `EmailOpened`

#### 9.3 Configuración
**Archivos:**
- `config/mailing.php` (configuración principal)
- `config/sending_servers.php`
- `.env` variables

- **Esfuerzo:** 24 horas
- **Riesgo:** Medio

---

### Fase 10: Testing & QA (Semana 16)

#### 10.1 Tests Unitarios
- Models
- Jobs
- Services
- Helpers

#### 10.2 Tests de Integración
- Campaign creation & sending
- Subscriber import
- Automation workflows
- Tracking & analytics

#### 10.3 Tests de API
- RESTful endpoints
- Authentication
- Rate limiting

- **Esfuerzo:** 60 horas
- **Riesgo:** Medio
- **Prioridad:** ALTA

---

## 🚫 Archivos que NO Deben Migrarse

### Código Redundante o Obsoleto

#### 1. Sistema de Usuarios
```
❌ app/Model/User.php
❌ app/Model/Admin.php
❌ app/Model/AdminGroup.php
❌ app/Model/Customer.php
❌ app/Model/CustomerGroup.php
❌ app/Model/SubAccount.php
❌ app/Http/Controllers/AdminController.php
❌ app/Http/Controllers/CustomerController.php
❌ app/Http/Controllers/Auth/
❌ resources/views/auth/
❌ database/migrations/*_users_table.php
```
**Razón:** Alsernet ya tiene su propio sistema de auth con Sanctum + Spatie Permission

#### 2. Instalador
```
❌ app/Http/Controllers/InstallController.php
❌ resources/views/install/
❌ database/migrations/*_install_*.php
```
**Razón:** El módulo se instala via `php artisan module:install`

#### 3. Payment Gateways (evaluar)
```
⚠️ app/Cashier/ (Stripe, PayPal, Braintree, Coinpayments)
⚠️ app/Library/BillingManager.php
⚠️ app/Model/Subscription.php
⚠️ app/Model/Invoice.php
⚠️ app/Model/Plan.php
```
**Razón:** Evaluar si Alsernet necesita billing propio o usar sistema externo

#### 4. Integraciones Específicas (evaluar caso por caso)
```
⚠️ app/Model/WooCommerce.php
⚠️ app/Model/Lazada.php
⚠️ app/Model/WpPost.php
⚠️ app/Library/Lazada/
```
**Razón:** Integraciones de e-commerce específicas, migrar solo si necesario

#### 5. Upgrade Manager
```
❌ app/Library/UpgradeManager.php
❌ upgrade/
```
**Razón:** No aplicable a arquitectura modular

#### 6. Testing Legacy
```
❌ tests/ (de Acelle)
```
**Razón:** Escribir tests nuevos para Laravel 12 + PHPUnit 11

---

## 🔄 Archivos que Requieren Adaptación (No Copia Directa)

### Categoría A: Adaptación Crítica (reescritura parcial)

#### 1. Modelos con Relaciones a User
**Todos los modelos que tengan:**
```php
// ANTES
public function customer() {
    return $this->belongsTo('Acelle\Model\Customer');
}
public function admin() {
    return $this->belongsTo('Acelle\Model\Admin');
}

// DESPUÉS
public function user() {
    return $this->belongsTo('App\Models\User');
}
```

**Modelos afectados:**
- `Campaign.php`
- `MailList.php`
- `Automation2.php`
- `Template.php`
- `SendingServer.php`
- Todos los logs (TrackingLog, OpenLog, etc.)

**Esfuerzo por modelo:** 2-4 horas

#### 2. Controladores con Autenticación
```php
// ANTES
use Acelle\Model\Customer;
$customer = $request->user()->customer;

// DESPUÉS
use App\Models\User;
$user = $request->user();
// Verificar roles: $user->hasRole('mailing_user')
```

**Esfuerzo:** Revisar todos los controladores (50+ horas)

#### 3. Migraciones con Foreign Keys a Users
```php
// ANTES
$table->integer('customer_id')->unsigned();
$table->foreign('customer_id')->references('id')->on('customers');

// DESPUÉS
$table->foreignId('user_id')->constrained('users')->onDelete('cascade');
```

#### 4. Service Providers
```php
// ANTES
namespace Acelle\Providers;
class MailerServiceProvider extends ServiceProvider

// DESPUÉS
namespace Modules\Mailing\Providers;
class MailingServiceProvider extends ServiceProvider
```

**Cambios adicionales:**
- Rutas: registrar con prefijo `mailing/`
- Views: namespace `mailing::`
- Config: publicar con tag `mailing-config`

#### 5. Routes
```php
// ANTES (routes/web.php global)
Route::group(['prefix' => 'lists'], function() {
    Route::get('/', 'MailListController@index');
});

// DESPUÉS (modules/Mailing/routes/web.php)
Route::middleware(['auth', 'verified'])
    ->prefix('mailing')
    ->name('mailing.')
    ->group(function() {
        Route::resource('lists', MailListController::class);
    });
```

#### 6. Jobs con Queue Naming
```php
// ANTES
class RunCampaign extends Job {
    public $queue = 'default';
}

// DESPUÉS
class RunCampaign extends Job {
    public $queue = 'mailing_campaigns'; // Queue dedicada
}
```

#### 7. Mail Classes
```php
// ANTES
namespace Acelle\Mail;
class SubscriptionDoneMailer extends Mailable

// DESPUÉS
namespace Modules\Mailing\Mail;
class CampaignSentNotification extends Mailable
```

#### 8. Blade Components
```php
// ANTES
@include('helpers._table')
@component('layouts.core.basic')

// DESPUÉS
@include('mailing::components.table')
@extends('mailing::layouts.app')
```

#### 9. Comandos Artisan
```php
// ANTES
namespace Acelle\Console\Commands;
protected $signature = 'campaign:run';

// DESPUÉS
namespace Modules\Mailing\Console\Commands;
protected $signature = 'mailing:campaign:run';
```

#### 10. Config Files
```php
// ANTES
config('app.sending_server_default')

// DESPUÉS
config('mailing.sending_server.default')
```

---

### Categoría B: Adaptación Menor (ajustes de namespace)

**Estos archivos solo necesitan:**
1. Cambio de namespace
2. Actualizar imports
3. Verificar funcionamiento

**Ejemplos:**
- Traits sin dependencias externas
- Helpers puros
- Enums y constantes
- Validators
- Form Requests básicos
- Policies (adaptar a Spatie Permission)

**Esfuerzo promedio:** 30 minutos por archivo

---

## 📊 Estimación de Esfuerzo Detallada

| Fase | Componente | Horas | Riesgo | Prioridad |
|------|-----------|-------|--------|-----------|
| 1 | Configuración Base | 16 | Bajo | CRÍTICA |
| 1 | Traits y Contratos | 12 | Bajo | CRÍTICA |
| 1 | Helpers y Utilidades | 20 | Medio | ALTA |
| 2 | Migraciones Core | 60 | Alto | CRÍTICA |
| 2 | Seeders | 8 | Bajo | MEDIA |
| 3 | Modelos Eloquent | 90 | Alto | CRÍTICA |
| 4 | Library Classes | 50 | Alto | CRÍTICA |
| 5 | Jobs & Queue | 40 | Medio-Alto | CRÍTICA |
| 6 | Controladores | 100 | Alto | CRÍTICA |
| 6 | Middlewares | 8 | Medio | MEDIA |
| 7 | Vistas Blade | 150 | Medio-Alto | ALTA |
| 8 | JavaScript | 40 | Medio | ALTA |
| 8 | CSS/SCSS | 30 | Bajo-Medio | MEDIA |
| 9 | Service Providers | 16 | Medio | ALTA |
| 9 | Events & Listeners | 8 | Medio | MEDIA |
| 10 | Tests Unitarios | 30 | Medio | ALTA |
| 10 | Tests Integración | 30 | Medio | ALTA |
| - | **Buffer/Imprevistos (20%)** | 140 | - | - |
| | **TOTAL** | **848 horas** | | |

**Conversión a semanas (40h/semana):** ~21 semanas
**Conversión a semanas (equipo de 2):** ~10-11 semanas

---

## ⚠️ Riesgos y Consideraciones

### Riesgos Técnicos

#### 1. Incompatibilidad de Versiones Laravel (ALTO)
**Problema:** Laravel 8 → Laravel 12 tiene cambios breaking:
- Estructura de carpetas diferente
- Métodos deprecados (dates vs casts)
- Queue system cambios
- Mail system refactorizado

**Mitigación:**
- Usar `search-docs` de Laravel Boost para documentación oficial
- Tests exhaustivos en cada componente
- Migración incremental con feature flags

#### 2. Dependencias Externas Obsoletas (MEDIO)
**Problema:** Algunas dependencias de Acelle son antiguas o incompatibles con PHP 8.4

**Mitigación:**
- Verificar cada paquete en Packagist
- Buscar alternativas modernas
- Fork and patch si necesario

#### 3. Conflictos de Namespace (MEDIO)
**Problema:** Posibles colisiones con módulos existentes (Mail, Mailer, MailsSettings)

**Mitigación:**
- Namespace estricto: `Modules\Mailing\`
- Prefijo en todas las rutas: `mailing/`
- Prefijo en tablas: `mailing_`

#### 4. Performance de Envío Masivo (ALTO)
**Problema:** Envíos de 100k+ emails requieren optimización extrema

**Mitigación:**
- Redis para rate limiting
- Queue workers dedicados
- Chunking inteligente de subscribers
- Monitoreo con Horizon

#### 5. Tracking Pixel & Link Tracking (MEDIO)
**Problema:** Sistema de tracking debe ser robusto y rápido

**Mitigación:**
- Cache agresivo en Redis
- CDN para pixel.gif
- Async processing de clicks/opens

---

### Riesgos de Negocio

#### 1. Pérdida de Funcionalidad (ALTO)
**Problema:** Migración incompleta = usuarios insatisfechos

**Mitigación:**
- Checklist exhaustivo de features
- Testing con usuarios reales
- Documentación completa

#### 2. Downtime Durante Migración (MEDIO)
**Problema:** Sistema debe seguir funcionando

**Mitigación:**
- Feature flags para activar/desactivar módulo
- Migración de datos en background
- Rollback plan

#### 3. Capacitación de Usuarios (MEDIO)
**Problema:** UI/UX diferente puede confundir

**Mitigación:**
- Documentación visual
- Videos tutoriales
- Tooltips in-app

---

### Riesgos de Proyecto

#### 1. Estimación Incorrecta (MEDIO)
**Problema:** 21 semanas puede ser optimista

**Mitigación:**
- Buffer del 20% incluido
- Sprints de 2 semanas con review
- Re-estimación cada fase

#### 2. Scope Creep (MEDIO)
**Problema:** Agregar features durante migración

**Mitigación:**
- Documento de scope firmado
- Change request process
- Backlog para v2.0

#### 3. Recursos Insuficientes (ALTO)
**Problema:** 1 desarrollador = 21 semanas

**Mitigación:**
- Considerar 2 desarrolladores = 10-11 semanas
- Priorizar features críticas (MVP)
- Migración por fases funcionales

---

## 📝 Cambios de Namespace Necesarios

### Mapeo Completo

```php
// MODELOS
Acelle\Model\*                    → Modules\Mailing\Models\*

// CONTROLADORES
Acelle\Http\Controllers\*         → Modules\Mailing\Http\Controllers\*

// JOBS
Acelle\Jobs\*                     → Modules\Mailing\Jobs\*

// MAIL
Acelle\Mail\*                     → Modules\Mailing\Mail\*

// EVENTS
Acelle\Events\*                   → Modules\Mailing\Events\*

// LISTENERS
Acelle\Listeners\*                → Modules\Mailing\Listeners\*

// LIBRARY
Acelle\Library\*                  → Modules\Mailing\Library\*

// PROVIDERS
Acelle\Providers\*                → Modules\Mailing\Providers\*

// REQUESTS
Acelle\Http\Requests\*            → Modules\Mailing\Http\Requests\*

// MIDDLEWARE
Acelle\Http\Middleware\*          → Modules\Mailing\Http\Middleware\*

// OBSERVERS
Acelle\Observers\*                → Modules\Mailing\Observers\*

// POLICIES
Acelle\Policies\*                 → Modules\Mailing\Policies\*

// TRAITS
Acelle\Library\Traits\*           → Modules\Mailing\Traits\*

// CONTRACTS
Acelle\Library\Contracts\*        → Modules\Mailing\Contracts\*

// CONSOLE
Acelle\Console\Commands\*         → Modules\Mailing\Console\Commands\*

// EXCEPTIONS
Acelle\Library\Exception\*        → Modules\Mailing\Exceptions\*
```

### Rutas y Views

```php
// RUTAS
Route::get('/campaigns')                    → Route::get('/mailing/campaigns')
route('campaigns.index')                    → route('mailing.campaigns.index')

// VISTAS
resources/views/campaigns/index.blade.php   → resources/views/mailing/campaigns/index.blade.php
@include('helpers._table')                  → @include('mailing::components.table')

// CONFIG
config('app.sending_server')                → config('mailing.sending_server')

// TRADUCCIONES
trans('messages.campaign_sent')             → trans('mailing::messages.campaign_sent')
```

---

## 🔧 Configuraciones que Deben Ajustarse

### 1. Variables de Entorno

**Agregar a `.env`:**
```bash
# === Mailing Module Configuration ===

# Default Sending Server
MAILING_DEFAULT_SENDING_SERVER=smtp

# Campaign Settings
MAILING_CAMPAIGN_MAX_DELIVERY_RATE=100000 # emails/hour
MAILING_CAMPAIGN_BATCH_SIZE=500
MAILING_CAMPAIGN_QUEUE=mailing_campaigns

# Tracking Settings
MAILING_TRACKING_ENABLED=true
MAILING_TRACKING_PIXEL_CACHE=3600 # seconds
MAILING_TRACKING_LINK_REDIRECT_CACHE=3600

# Import/Export Settings
MAILING_IMPORT_MAX_FILE_SIZE=10485760 # 10MB
MAILING_IMPORT_QUEUE=mailing_imports
MAILING_EXPORT_QUEUE=mailing_exports

# Email Verification
MAILING_EMAIL_VERIFICATION_ENABLED=false
MAILING_EMAIL_VERIFICATION_PROVIDER=null # emailable, zerobounce, etc.

# Sending Server Credentials (examples)
AWS_SES_KEY=
AWS_SES_SECRET=
AWS_SES_REGION=us-east-1

MAILGUN_DOMAIN=
MAILGUN_SECRET=
MAILGUN_ENDPOINT=api.mailgun.net

SENDGRID_API_KEY=

SPARKPOST_API_KEY=

# Automation Settings
MAILING_AUTOMATION_ENABLED=true
MAILING_AUTOMATION_QUEUE=mailing_automation

# Bounce & Feedback Settings
MAILING_BOUNCE_HANDLER_ENABLED=true
MAILING_FEEDBACK_HANDLER_ENABLED=true

# Storage
MAILING_STORAGE_DISK=local # or s3, public, etc.
MAILING_STORAGE_PATH=mailing

# AI Features (optional)
MAILING_AI_ENABLED=false
OPENAI_API_KEY=

# Rate Limiting
MAILING_RATE_LIMIT_WINDOW=60 # seconds
MAILING_RATE_LIMIT_MAX_ATTEMPTS=1000
```

### 2. Config Files

**`config/mailing.php`:**
```php
<?php

return [
    // Module Settings
    'enabled' => env('MAILING_ENABLED', true),
    'version' => '1.0.0',

    // Campaign Settings
    'campaigns' => [
        'max_delivery_rate' => env('MAILING_CAMPAIGN_MAX_DELIVERY_RATE', 100000),
        'batch_size' => env('MAILING_CAMPAIGN_BATCH_SIZE', 500),
        'queue' => env('MAILING_CAMPAIGN_QUEUE', 'mailing_campaigns'),
        'timeout' => env('MAILING_CAMPAIGN_TIMEOUT', 300), // seconds
    ],

    // Tracking Settings
    'tracking' => [
        'enabled' => env('MAILING_TRACKING_ENABLED', true),
        'pixel_cache' => env('MAILING_TRACKING_PIXEL_CACHE', 3600),
        'link_redirect_cache' => env('MAILING_TRACKING_LINK_REDIRECT_CACHE', 3600),
        'store_user_agent' => true,
        'store_ip_location' => true,
    ],

    // Import/Export Settings
    'import' => [
        'max_file_size' => env('MAILING_IMPORT_MAX_FILE_SIZE', 10485760),
        'queue' => env('MAILING_IMPORT_QUEUE', 'mailing_imports'),
        'allowed_extensions' => ['csv', 'txt', 'xlsx'],
        'chunk_size' => 1000,
    ],

    'export' => [
        'queue' => env('MAILING_EXPORT_QUEUE', 'mailing_exports'),
        'chunk_size' => 5000,
    ],

    // Email Verification
    'verification' => [
        'enabled' => env('MAILING_EMAIL_VERIFICATION_ENABLED', false),
        'provider' => env('MAILING_EMAIL_VERIFICATION_PROVIDER'),
        'verify_on_import' => true,
        'verify_on_subscribe' => false,
    ],

    // Sending Servers
    'sending_servers' => [
        'default' => env('MAILING_DEFAULT_SENDING_SERVER', 'smtp'),
        'enabled_types' => [
            'smtp',
            'sendmail',
            'amazon-ses',
            'mailgun',
            'sendgrid',
            'sparkpost',
            'elasticemail',
        ],
    ],

    // Automation
    'automation' => [
        'enabled' => env('MAILING_AUTOMATION_ENABLED', true),
        'queue' => env('MAILING_AUTOMATION_QUEUE', 'mailing_automation'),
        'max_triggers_per_subscriber' => 100,
    ],

    // Bounce & Feedback
    'bounce' => [
        'enabled' => env('MAILING_BOUNCE_HANDLER_ENABLED', true),
        'max_retries' => 3,
    ],

    'feedback' => [
        'enabled' => env('MAILING_FEEDBACK_HANDLER_ENABLED', true),
    ],

    // Storage
    'storage' => [
        'disk' => env('MAILING_STORAGE_DISK', 'local'),
        'path' => env('MAILING_STORAGE_PATH', 'mailing'),
    ],

    // AI Features
    'ai' => [
        'enabled' => env('MAILING_AI_ENABLED', false),
        'provider' => 'openai',
        'model' => 'gpt-4',
    ],

    // Rate Limiting
    'rate_limit' => [
        'window' => env('MAILING_RATE_LIMIT_WINDOW', 60),
        'max_attempts' => env('MAILING_RATE_LIMIT_MAX_ATTEMPTS', 1000),
    ],

    // UI Settings
    'ui' => [
        'items_per_page' => 25,
        'campaign_builder_autosave' => true,
        'show_advanced_options' => false,
    ],
];
```

### 3. Queue Configuration

**`config/queue.php` (actualizar):**
```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],

    // Dedicated Mailing Queues
    'mailing_campaigns' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'mailing_campaigns',
        'retry_after' => 300,
        'block_for' => null,
    ],

    'mailing_imports' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'mailing_imports',
        'retry_after' => 600,
        'block_for' => null,
    ],

    'mailing_exports' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'mailing_exports',
        'retry_after' => 600,
        'block_for' => null,
    ],

    'mailing_automation' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'mailing_automation',
        'retry_after' => 180,
        'block_for' => null,
    ],
],
```

### 4. Horizon Configuration

**`config/horizon.php` (actualizar):**
```php
'environments' => [
    'production' => [
        'supervisor-mailing-campaigns' => [
            'connection' => 'redis',
            'queue' => ['mailing_campaigns'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
            'timeout' => 300,
        ],

        'supervisor-mailing-imports' => [
            'connection' => 'redis',
            'queue' => ['mailing_imports', 'mailing_exports'],
            'balance' => 'auto',
            'processes' => 3,
            'tries' => 3,
            'timeout' => 600,
        ],

        'supervisor-mailing-automation' => [
            'connection' => 'redis',
            'queue' => ['mailing_automation'],
            'balance' => 'auto',
            'processes' => 5,
            'tries' => 3,
            'timeout' => 180,
        ],
    ],
],
```

### 5. Filesystem Configuration

**`config/filesystems.php` (agregar):**
```php
'disks' => [
    'mailing' => [
        'driver' => 'local',
        'root' => storage_path('app/mailing'),
        'visibility' => 'private',
    ],

    'mailing_public' => [
        'driver' => 'local',
        'root' => storage_path('app/public/mailing'),
        'url' => env('APP_URL').'/storage/mailing',
        'visibility' => 'public',
    ],
],
```

### 6. Mail Configuration

**`config/mail.php` (verificar compatibilidad):**
```php
// Asegurar que drivers estén disponibles
'mailers' => [
    'smtp' => [...],
    'ses' => [...],
    'mailgun' => [...],
    'sendgrid' => [...],
    // etc.
],
```

### 7. Permissions Configuration

**Agregar roles y permisos (Spatie):**
```php
// database/seeders/MailingPermissionsSeeder.php

$permissions = [
    // Campaigns
    'mailing.campaigns.view',
    'mailing.campaigns.create',
    'mailing.campaigns.edit',
    'mailing.campaigns.delete',
    'mailing.campaigns.send',
    'mailing.campaigns.pause',
    'mailing.campaigns.resume',

    // Lists
    'mailing.lists.view',
    'mailing.lists.create',
    'mailing.lists.edit',
    'mailing.lists.delete',
    'mailing.lists.import',
    'mailing.lists.export',

    // Subscribers
    'mailing.subscribers.view',
    'mailing.subscribers.create',
    'mailing.subscribers.edit',
    'mailing.subscribers.delete',

    // Templates
    'mailing.templates.view',
    'mailing.templates.create',
    'mailing.templates.edit',
    'mailing.templates.delete',

    // Automation
    'mailing.automation.view',
    'mailing.automation.create',
    'mailing.automation.edit',
    'mailing.automation.delete',
    'mailing.automation.start',
    'mailing.automation.stop',

    // Settings
    'mailing.settings.view',
    'mailing.settings.edit',
    'mailing.sending_servers.manage',
    'mailing.domains.manage',

    // Reports
    'mailing.reports.view',
    'mailing.reports.export',
];
```

---

## 📚 Documentación Requerida

### Durante la Migración

1. **CHANGELOG.md**
   - Cambios en cada fase
   - Breaking changes
   - Deprecations

2. **MIGRATION_LOG.md**
   - Registro de decisiones técnicas
   - Problemas encontrados y soluciones
   - Performance benchmarks

3. **API_DOCUMENTATION.md**
   - Endpoints disponibles
   - Request/Response examples
   - Authentication

### Post-Migración

4. **USER_GUIDE.md**
   - Cómo usar cada feature
   - Screenshots
   - Best practices

5. **DEVELOPER_GUIDE.md**
   - Arquitectura del módulo
   - Cómo extender funcionalidad
   - Testing guidelines

6. **DEPLOYMENT_GUIDE.md**
   - Requisitos del servidor
   - Configuración de queues
   - Monitoreo y mantenimiento

---

## ✅ Checklist de Finalización

### Funcionalidad Core
- [ ] Campaign creation & sending
- [ ] List management
- [ ] Subscriber import/export
- [ ] Template builder
- [ ] Automation workflows
- [ ] Tracking (opens, clicks, bounces)
- [ ] Reports & analytics
- [ ] Forms & landing pages
- [ ] API endpoints

### Integración con Alsernet
- [ ] Authentication via Sanctum
- [ ] Permissions via Spatie
- [ ] Media via Spatie MediaLibrary
- [ ] Activity logging
- [ ] UI consistent with Modernize template
- [ ] Redis caching integration
- [ ] Horizon queue monitoring

### Testing
- [ ] Unit tests (>80% coverage)
- [ ] Integration tests
- [ ] API tests
- [ ] Performance tests (sending speed)
- [ ] Load tests (10k+ subscribers)

### Documentación
- [ ] Code documentation (PHPDoc)
- [ ] User guide
- [ ] Developer guide
- [ ] API documentation
- [ ] Deployment guide

### Performance
- [ ] Database indexes optimized
- [ ] Query optimization
- [ ] Redis caching implemented
- [ ] CDN for static assets
- [ ] Queue workers configured

### Security
- [ ] CSRF protection
- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] Rate limiting
- [ ] Permission checks

---

## 🎓 Recursos y Referencias

### Documentación Oficial (usar `search-docs` de Laravel Boost)
- Laravel 12 Documentation
- Laravel Queue & Horizon
- Laravel Mail
- Spatie Permission
- Spatie MediaLibrary
- Bootstrap 5.3
- TailwindCSS 4.0

### Herramientas de Desarrollo
- Laravel Pint (code style)
- Laravel Telescope (debugging)
- Laravel Pulse (monitoring)
- PHPUnit 11 (testing)
- Postman (API testing)

### Proyectos de Referencia
- Acelle Mail (origen)
- Mautic (automation similar)
- Sendy (lista de referencia)

---

## 📞 Contacto y Soporte

**Equipo de Desarrollo:**
- Lead Developer: [Nombre]
- Backend Developer: [Nombre]
- Frontend Developer: [Nombre]

**Stakeholders:**
- Product Owner: [Nombre]
- QA Lead: [Nombre]

**Schedule:**
- Daily Standup: 9:00 AM
- Sprint Review: Viernes 4:00 PM
- Planning: Lunes 10:00 AM

---

## 📌 Notas Finales

1. **Prioridad absoluta:** Campaign sending debe funcionar perfectamente antes de release
2. **Testing:** No omitir tests bajo ninguna circunstancia
3. **Performance:** Monitoring constante con Horizon durante desarrollo
4. **Documentación:** Actualizar en cada PR, no al final
5. **Code Review:** Todo código debe ser revisado por al menos 1 persona
6. **Git Strategy:** Feature branches + PR to `develop` + merge to `main` on release

**Versión del Documento:** 1.0
**Última Actualización:** 2026-01-29
**Próxima Revisión:** Después de Fase 2
