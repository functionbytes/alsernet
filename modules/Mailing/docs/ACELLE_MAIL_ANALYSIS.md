# Acelle Mail System - Análisis Completo

## Fecha de análisis
**29 de enero de 2026**

---

## 1. Resumen ejecutivo

Este documento analiza la infraestructura de correo electrónico de **Acelle Mail**, un sistema de email marketing avanzado. El análisis abarca las clases Mailable, plantillas, notificaciones y el sistema de envío de correos.

### Ubicación principal
```
/Users/functionbytes/Function/Coding/acelle/app/Mail/
```

### Componentes principales identificados
- **3 clases Mailable** personalizadas
- **1 clase Notification** personalizada
- **Sistema de notificaciones** basado en base de datos
- **Múltiples proveedores de envío** (SMTP, API)
- **Event listeners** para automatización de emails

---

## 2. Clases Mailable

### 2.1 RegistrationConfirmationMailer

**Ubicación:** `/Users/functionbytes/Function/Coding/acelle/app/Mail/RegistrationConfirmationMailer.php`

**Propósito:** Enviar email de confirmación de registro a nuevos usuarios.

**Características:**
- Extiende: `Illuminate\Mail\Mailable`
- Traits: `Queueable`, `SerializesModels`
- Soporta cola de envío (puede procesarse en segundo plano)

**Código principal:**
```php
class RegistrationConfirmationMailer extends Mailable
{
    use Queueable, SerializesModels;

    protected $content;
    public $subject;

    public function __construct($content, $subject)
    {
        $this->content = $content;
        $this->subject = $subject;
    }

    public function build()
    {
        return $this->subject($this->subject)
                    ->from(config('mail.from')['address'], config('mail.from')['name'])
                    ->view('users.registration_confirmation_email')
                    ->with(['content' => $this->content]);
    }
}
```

**Vista asociada:**
- `resources/views/users/registration_confirmation_email.blade.php`
- Contenido: `{!! $content !!}` (HTML dinámico)

**Parámetros:**
- `$content` (string): Contenido HTML del email
- `$subject` (string): Asunto del correo

**Remitente:**
- Configurado desde `config('mail.from')`

---

### 2.2 SettingMailerTest

**Ubicación:** `/Users/functionbytes/Function/Coding/acelle/app/Mail/SettingMailerTest.php`

**Propósito:** Probar configuración del sistema de correo electrónico.

**Características:**
- Clase de prueba para validar configuración SMTP/Mailer
- No requiere parámetros en constructor
- Subject traducible

**Código principal:**
```php
class SettingMailerTest extends Mailable
{
    use Queueable, SerializesModels;

    public function build()
    {
        return $this->subject(trans('messages.setting.mailer.test.email_subject'))
            ->view('emails.SettingMailerTest');
    }
}
```

**Vista asociada:**
- `resources/views/emails/SettingMailerTest.blade.php`

**Uso:**
- Verifica que las credenciales SMTP/API funcionan correctamente
- Envía email de prueba desde el panel de administración

---

### 2.3 SubscriptionDoneMailer

**Ubicación:** `/Users/functionbytes/Function/Coding/acelle/app/Mail/SubscriptionDoneMailer.php`

**Propósito:** Notificar al cliente cuando se completa una suscripción.

**Características:**
- Vinculado con modelo `Subscription`
- Subject traducible
- Vista dinámica según gateway de pago

**Código principal:**
```php
class SubscriptionDoneMailer extends Mailable
{
    use Queueable, SerializesModels;

    protected $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    public function build()
    {
        return $this->subject(trans('messages.subscription_done.email_subject'))
            ->view('subscription.email.subscription_done_' . \Acelle\Model\Setting::get('system.payment_gateway'))
            ->with([
                'customerName' => $this->getNewOrActiveGeneralSubscription()->user->customer->displayName(),
                'planName' => $this->getNewOrActiveGeneralSubscription()->planGeneral->name,
                'link' => action('SubscriptionController@index'),
            ]);
    }
}
```

**Vistas asociadas (dinámicas):**
- `resources/views/subscription/email/subscription_done_{gateway}.blade.php`
- Varía según gateway configurado: PayPal, Stripe, etc.

**Datos pasados a la vista:**
- `customerName`: Nombre del cliente
- `planName`: Nombre del plan contratado
- `link`: URL al panel de suscripciones

**⚠️ Nota:** El método `getNewOrActiveGeneralSubscription()` no se encontró en el código proporcionado, puede estar en trait o clase padre.

---

## 3. Sistema de notificaciones

### 3.1 Modelo Notification

**Ubicación:** `/Users/functionbytes/Function/Coding/acelle/app/Model/Notification.php`

**Propósito:** Sistema de notificaciones persistentes en base de datos.

**Características principales:**
- Almacena notificaciones del sistema (info, warning, error)
- Trait: `HasUid` (identificadores únicos)
- Niveles: `LEVEL_INFO`, `LEVEL_WARNING`, `LEVEL_ERROR`

**Estructura de tabla:**
```
notifications
├── id
├── uid (único)
├── type (clase de notificación)
├── title
├── message
├── level (info/warning/error)
├── visibility (boolean)
├── debug (información adicional)
├── created_at
└── updated_at
```

**Métodos estáticos principales:**

```php
// Crear notificación informativa
Notification::info([
    'title' => 'Título',
    'message' => 'Mensaje',
]);

// Crear advertencia
Notification::warning([
    'title' => 'Advertencia',
    'message' => 'Descripción del problema',
]);

// Crear error
Notification::error([
    'title' => 'Error',
    'message' => 'Descripción del error',
]);

// Registrar con callback
Notification::recordIfFails(function() {
    // Código que puede fallar
}, 'Título de notificación', function($exception) {
    // Callback opcional de error
});
```

**Funcionalidades:**
- `top($limit)`: Obtiene las notificaciones más recientes visibles
- `cleanup()`: Limpia todas las notificaciones
- `cleanupDuplicateNotifications($title)`: Elimina duplicados por título
- `hide()`: Oculta una notificación sin eliminarla
- `filter($request)`: Filtra notificaciones por keyword y nivel
- `search($request)`: Búsqueda avanzada con ordenamiento

---

### 3.2 Notification Personalizada: ResetPassword

**Ubicación:** `/Users/functionbytes/Function/Coding/acelle/app/Notifications/ResetPassword.php`

**Propósito:** Enviar email de recuperación de contraseña.

**Características:**
- Extiende: `Illuminate\Auth\Notifications\ResetPassword`
- Usa sistema de notificaciones de Laravel
- URL personalizable para reset

**Código principal:**
```php
class ResetPassword extends BaseResetPassword
{
    protected $resetPasswordUrl;

    public function __construct($token, $resetPasswordUrl)
    {
        $this->token = $token;
        $this->resetPasswordUrl = $resetPasswordUrl;
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->line(trans('messages.click_here_to_reset_password'))
            ->action(trans('messages.reset_password'), $this->resetPasswordUrl);
    }
}
```

**Características:**
- Usa `MailMessage` de Laravel (no Blade directo)
- Texto traducible
- Botón de acción con URL dinámica

---

### 3.3 Clases de notificación especializadas

#### BackendError

**Ubicación:** `/Users/functionbytes/Function/Coding/acelle/app/Library/Notification/BackendError.php`

```php
namespace Acelle\Library\Notification;
use Acelle\Model\Notification;

class BackendError extends Notification
{
    // Notificación para errores de backend (comandos, jobs)
}
```

**Uso:** Registra errores críticos del sistema backend.

---

#### CronJob

**Ubicación:** `/Users/functionbytes/Function/Coding/acelle/app/Library/Notification/CronJob.php`

**Propósito:** Verificar que los cronjobs se ejecutan correctamente.

**Características:**
- Verifica última ejecución del cron
- Alerta si el intervalo mínimo no se cumple
- Auto-limpia notificaciones duplicadas

**Código principal:**
```php
class CronJob extends Notification
{
    public static function check()
    {
        $title = 'CronJob';
        self::cleanupDuplicateNotifications($title);

        $interval = Setting::get('cronjob_min_interval');
        if (!self::isCronjobExecutedWithin($interval)) {
            $warning = [
                'title' => $title,
                'message' => trans('messages.admin.notification.cronjob_not_active', [
                    'cronjob_min_interval' => $interval,
                    'cronjob_last_executed' => self::getLastExecutionDateTime()
                ]),
            ];
            self::warning($warning);
        }
    }

    private static function isCronjobExecutedWithin($diff)
    {
        $timestamp = Setting::get('cronjob_last_execution');
        if (is_null($timestamp)) {
            return false;
        }

        $lastexec = \Carbon\Carbon::createFromTimestamp($timestamp);
        $checked = new \Carbon\Carbon(sprintf('%s ago', $diff));

        return $lastexec->gte($checked);
    }

    public static function getLastExecutionDateTime()
    {
        $timestamp = Setting::get('cronjob_last_execution');
        if (is_null($timestamp)) {
            return '#unknown';
        }
        return \Carbon\Carbon::createFromTimestamp($timestamp)->toDateTimeString();
    }
}
```

**Configuración relacionada:**
- `cronjob_min_interval`: Intervalo mínimo entre ejecuciones
- `cronjob_last_execution`: Timestamp de última ejecución

---

#### SystemUrl

**Ubicación:** `/Users/functionbytes/Function/Coding/acelle/app/Library/Notification/SystemUrl.php`

**Propósito:** Verificar que la URL del sistema coincide con la configurada.

**Código principal:**
```php
class SystemUrl extends Notification
{
    public static function check()
    {
        $title = trans('messages.admin.notification.system_url_title');
        self::cleanupDuplicateNotifications($title);

        $current = url('/');
        $cached = config('app.url');

        if ($current != $cached) {
            $warning = [
                'title' => $title,
                'message' => trans('messages.admin.notification.system_url_not_match', [
                    'cached' => $cached,
                    'current' => $current
                ]),
            ];
            self::warning($warning);
        }
    }
}
```

**Uso:**
- Detecta cambios de dominio/URL
- Alerta sobre inconsistencias en configuración

---

## 4. Sistema de envío de correos

### 4.1 MailerServiceProvider

**Ubicación:** `/Users/functionbytes/Function/Coding/acelle/app/Providers/MailerServiceProvider.php`

**Propósito:** Configurar el servicio de envío de correos (`xmailer`).

**Características:**
- Implementa: `DeferrableProvider` (carga diferida)
- Registra servicio: `xmailer`
- Soporta múltiples tipos de servidores

**Código principal:**
```php
class MailerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->bind('xmailer', function ($app) {
            $mailer = Setting::get('mailer.mailer') ?: Setting::get('mailer.driver');

            switch ($mailer) {
                case SendingServer::TYPE_SMTP:
                    $server = SendingServerSmtp::instantiateFromSettings([
                        'host' => Setting::get('mailer.host') ?? config('mail.host'),
                        'smtp_port' => Setting::get('mailer.port') ?? config('mail.port'),
                        'smtp_protocol' => Setting::get('mailer.encryption') ?? config('mail.encryption'),
                        'smtp_username' => Setting::get('mailer.username') ?? config('mail.username'),
                        'smtp_password' => Setting::get('mailer.password') ?? config('mail.password'),
                        'from_name' => Setting::get('mailer.from.name') ?? config('mail.from.name'),
                        'from_address' => Setting::get('mailer.from.address') ?? config('mail.from.address'),
                    ]);
                    break;

                case SendingServer::TYPE_SENDMAIL:
                    $server = SendingServerSendmail::instantiateFromSettings([
                        'sendmail_path' => Setting::get('mailer.sendmail_path') ?? config('mail.sendmail'),
                        'from_name' => Setting::get('mailer.from.name') ?? config('mail.from.name'),
                        'from_address' => Setting::get('mailer.from.address') ?? config('mail.from.address'),
                    ]);
                    break;

                default:
                    throw new \Exception("Mail mailer '{$mailer}' not found", 1);
            }

            return $server;
        });
    }

    public function provides()
    {
        return ['xmailer'];
    }
}
```

**Tipos de servidor soportados:**
- `smtp`: SMTP tradicional
- `sendmail`: Sendmail local

**Configuración:**
- Prioriza configuración de BD (`Setting::get()`)
- Fallback a archivo config si no existe en BD

**Uso:**
```php
$mailer = App::make('xmailer');
$mailer->send($message);
```

---

### 4.2 SendingServer - Modelo base

**Ubicación:** `/Users/functionbytes/Function/Coding/acelle/app/Model/SendingServer.php`

**Propósito:** Clase abstracta para diferentes tipos de servidores de envío.

**Tipos de servidor soportados:**

```php
// SMTP/API Providers
TYPE_AMAZON_API = 'amazon-api'
TYPE_AMAZON_SMTP = 'amazon-smtp'
TYPE_SENDGRID_API = 'sendgrid-api'
TYPE_SENDGRID_SMTP = 'sendgrid-smtp'
TYPE_MAILGUN_API = 'mailgun-api'
TYPE_MAILGUN_SMTP = 'mailgun-smtp'
TYPE_ELASTICEMAIL_API = 'elasticemail-api'
TYPE_ELASTICEMAIL_SMTP = 'elasticemail-smtp'
TYPE_SPARKPOST_API = 'sparkpost-api'
TYPE_SPARKPOST_SMTP = 'sparkpost-smtp'
TYPE_SMTP = 'smtp'
TYPE_SENDMAIL = 'sendmail'
TYPE_BLASTENGINE_API = 'blastengine-api'
TYPE_BLASTENGINE_SMTP = 'blastengine-smtp'
```

**Mapeo de clases:**
```php
public static $serverMapping = [
    self::TYPE_AMAZON_API => 'SendingServerAmazonApi',
    self::TYPE_AMAZON_SMTP => 'SendingServerAmazonSmtp',
    self::TYPE_SMTP => 'SendingServerSmtp',
    self::TYPE_SENDMAIL => 'SendingServerSendmail',
    self::TYPE_MAILGUN_API => 'SendingServerMailgunApi',
    self::TYPE_MAILGUN_SMTP => 'SendingServerMailgunSmtp',
    self::TYPE_SENDGRID_API => 'SendingServerSendGridApi',
    self::TYPE_SENDGRID_SMTP => 'SendingServerSendGridSmtp',
    self::TYPE_ELASTICEMAIL_API => 'SendingServerElasticEmailApi',
    self::TYPE_ELASTICEMAIL_SMTP => 'SendingServerElasticEmailSmtp',
    self::TYPE_SPARKPOST_API => 'SendingServerSparkPostApi',
    self::TYPE_SPARKPOST_SMTP => 'SendingServerSparkPostSmtp',
    self::TYPE_BLASTENGINE_API => 'SendingServerBlastengineApi',
    self::TYPE_BLASTENGINE_SMTP => 'SendingServerBlastengineSmtp',
];
```

**Estado de entrega:**
- `DELIVERY_STATUS_SENT = 'sent'`
- `DELIVERY_STATUS_FAILED = 'failed'`

**Estado del servidor:**
- `STATUS_ACTIVE = 'active'`
- `STATUS_INACTIVE = 'inactive'`

**Atributos principales:**
```php
protected $fillable = [
    'name', 'type', 'host',
    'aws_access_key_id', 'aws_secret_access_key', 'aws_region',
    'domain', 'api_key', 'api_secret_key',
    'smtp_username', 'smtp_password', 'smtp_port', 'smtp_protocol',
    'quota_value', 'sendmail_path', 'quota_base', 'quota_unit',
    'bounce_handler_id', 'feedback_loop_handler_id',
    'status', 'default_from_email', 'username'
];
```

**Características:**
- Gestión de cuotas de envío
- Manejo de bounces y feedback loops
- Rate limiting por servidor
- Identidades de remitente verificadas

**Modelos derivados encontrados:**
1. `SendingServerAmazon.php` - Amazon SES
2. `SendingServerAmazonSmtp.php` - Amazon SES vía SMTP
3. `SendingServerElasticEmailSmtp.php` - ElasticEmail SMTP
4. `SendingServerMailgunSmtp.php` - Mailgun SMTP
5. `SendingServerSendGridApi.php` - SendGrid API
6. `SendingServerSendGridSmtp.php` - SendGrid SMTP
7. `SendingServerSendmail.php` - Sendmail local
8. `SendingServerSparkPost.php` - SparkPost
9. `SendingServerSparkPostSmtp.php` - SparkPost SMTP

---

## 5. Event Listeners para emails

### 5.1 SendListNotificationToSubscriber

**Ubicación:** `/Users/functionbytes/Function/Coding/acelle/app/Listeners/SendListNotificationToSubscriber.php`

**Propósito:** Enviar notificaciones automáticas a suscriptores.

**Eventos escuchados:**
1. `MailListSubscription` - Cuando alguien se suscribe
2. `MailListUnsubscription` - Cuando alguien se desuscribe

**Código principal:**
```php
class SendListNotificationToSubscriber
{
    public function handleMailListSubscription(MailListSubscription $event)
    {
        $subscriber = $event->subscriber;
        $list = $subscriber->mailList;

        if ($list->send_welcome_email) {
            $list->sendSubscriptionWelcomeEmail($subscriber);
        }
    }

    public function handleMailListUnsubscription(MailListUnsubscription $event)
    {
        $subscriber = $event->subscriber;
        $list = $subscriber->mailList;

        if ($list->unsubscribe_notification) {
            $list->sendUnsubscriptionNotificationEmail($subscriber);
        }
    }

    public function subscribe($events)
    {
        $events->listen(
            'Acelle\Events\MailListSubscription',
            [SendListNotificationToSubscriber::class, 'handleMailListSubscription']
        );

        $events->listen(
            'Acelle\Events\MailListUnsubscription',
            [SendListNotificationToSubscriber::class, 'handleMailListUnsubscription']
        );
    }
}
```

**Flujo:**
1. Usuario se suscribe → evento `MailListSubscription`
2. Listener verifica si `send_welcome_email` está activo
3. Si está activo, envía email de bienvenida
4. Usuario se desuscribe → evento `MailListUnsubscription`
5. Listener verifica si `unsubscribe_notification` está activo
6. Si está activo, envía email de despedida

---

### 5.2 Métodos de envío en MailList

**Ubicación:** `/Users/functionbytes/Function/Coding/acelle/app/Model/MailList.php`

#### sendSubscriptionWelcomeEmail()

```php
public function sendSubscriptionWelcomeEmail($subscriber)
{
    $list = $this;

    $layout = \Acelle\Model\Layout::where('alias', 'sign_up_welcome_email')->first();
    $send_page = \Acelle\Model\Page::findPage($list, $layout);
    $this->sendMail($subscriber, $send_page, $send_page->getTransformedSubject($subscriber));
}
```

**Proceso:**
1. Busca layout con alias `sign_up_welcome_email`
2. Encuentra página personalizada para la lista
3. Envía email con asunto transformado (tags reemplazados)

---

#### sendUnsubscriptionNotificationEmail()

```php
public function sendUnsubscriptionNotificationEmail($subscriber)
{
    $list = $this;

    $layout = \Acelle\Model\Layout::where('alias', 'unsubscribe_goodbye_email')->first();
    $send_page = \Acelle\Model\Page::findPage($list, $layout);
    $this->sendMail($subscriber, $send_page, $send_page->getTransformedSubject($subscriber));
}
```

**Proceso:**
1. Busca layout con alias `unsubscribe_goodbye_email`
2. Encuentra página personalizada para la lista
3. Envía email con asunto transformado

---

#### sendSubscriptionNotificationEmailToListOwner()

```php
public function sendSubscriptionNotificationEmailToListOwner($subscriber)
{
    $template = Layout::where('alias', 'subscribe_notification_for_list_owner')->first();

    $message = $template->getMessage(function ($html) use ($subscriber) {
        $html = str_replace('{LIST_NAME}', $this->name, $html);
        $html = str_replace('{EMAIL}', $subscriber->email, $html);
        $html = str_replace('{FULL_NAME}', $subscriber->getFullName(), $html);
        return $html;
    });

    // ... código de envío ...
}
```

**Variables de template disponibles:**
- `{LIST_NAME}` - Nombre de la lista
- `{EMAIL}` - Email del suscriptor
- `{FULL_NAME}` - Nombre completo del suscriptor

---

#### sendUnsubscriptionNotificationEmailToListOwner()

```php
public function sendUnsubscriptionNotificationEmailToListOwner($subscriber)
{
    $message = new ExtendedSwiftMessage();
    $message->setContentType('text/html; charset=utf-8');
    $message->setEncoder(new \Swift_Mime_ContentEncoder_PlainContentEncoder('8bit'));
    $message->setTo([$this->customer->user->email => $this->customer->displayName()]);

    $template = \Acelle\Model\Layout::where('alias', 'unsubscribe_notification_for_list_owner')->first();
    $htmlContent = $template->content;

    $htmlContent = str_replace('{LIST_NAME}', $this->name, $htmlContent);
    $htmlContent = str_replace('{EMAIL}', $subscriber->email, $htmlContent);
    $htmlContent = str_replace('{FULL_NAME}', $subscriber->getFullName(), $htmlContent);

    $message->setSubject($template->subject);
    $message->addPart($htmlContent, 'text/html');

    $mailer = App::make('xmailer');
    $result = $mailer->sendWithDefaultFromAddress($message);
}
```

**Proceso:**
1. Crea mensaje SwiftMailer
2. Configura charset UTF-8
3. Destinatario: propietario de la lista
4. Reemplaza variables en template
5. Envía usando servicio `xmailer`

---

## 6. Plantillas de correo

### 6.1 Layouts del sistema

Acelle utiliza un sistema de **layouts y pages** para emails:

**Tipos de layouts identificados:**
1. `sign_up_welcome_email` - Email de bienvenida al suscribirse
2. `unsubscribe_goodbye_email` - Email de despedida al desuscribirse
3. `subscribe_notification_for_list_owner` - Notificación al propietario (suscripción)
4. `unsubscribe_notification_for_list_owner` - Notificación al propietario (baja)

**Sistema de transformación:**
- Los layouts tienen placeholders: `{LIST_NAME}`, `{EMAIL}`, `{FULL_NAME}`
- Se transforman dinámicamente con datos del suscriptor
- Método: `getTransformedSubject($subscriber)`

---

### 6.2 Vistas Blade encontradas

#### Registration confirmation
**Ruta:** `resources/views/users/registration_confirmation_email.blade.php`
```blade
{!! $content !!}
```
- Recibe HTML dinámico completo
- Sin estructura fija

#### Mailer test
**Ruta:** `resources/views/emails/SettingMailerTest.blade.php`
- Email de prueba simple
- Verifica conectividad SMTP/API

#### Subscription done
**Ruta dinámica:** `resources/views/subscription/email/subscription_done_{gateway}.blade.php`
- Template varía según gateway de pago
- Variables disponibles:
  - `$customerName`
  - `$planName`
  - `$link`

---

## 7. Arquitectura de envío

### 7.1 Flujo de envío estándar

```
1. Aplicación crea Mailable
   ↓
2. Laravel serializa Mailable (si usa cola)
   ↓
3. Job de cola procesa Mailable
   ↓
4. MailerServiceProvider proporciona 'xmailer'
   ↓
5. SendingServer selecciona proveedor
   ↓
6. Envío mediante API/SMTP
   ↓
7. Tracking log registra resultado
```

---

### 7.2 Flujo de eventos automáticos

```
Usuario se suscribe
   ↓
MailListSubscription event
   ↓
SendListNotificationToSubscriber listener
   ↓
Verifica send_welcome_email flag
   ↓
MailList->sendSubscriptionWelcomeEmail()
   ↓
Busca layout sign_up_welcome_email
   ↓
Transforma placeholders
   ↓
Envía mediante xmailer
```

---

### 7.3 Providers de envío

**Categorías:**

#### API-based (más rápido, mayor throughput)
- Amazon SES API
- SendGrid API
- Mailgun API
- ElasticEmail API
- SparkPost API
- BlastEngine API

#### SMTP-based (mayor compatibilidad)
- Amazon SES SMTP
- SendGrid SMTP
- Mailgun SMTP
- ElasticEmail SMTP
- SparkPost SMTP
- BlastEngine SMTP
- SMTP genérico

#### Local
- Sendmail (sin servidor externo)

---

## 8. Características avanzadas

### 8.1 Rate limiting
- Configurado por servidor de envío
- Campos: `quota_value`, `quota_base`, `quota_unit`
- Permite limitar envíos por hora/día/mes

### 8.2 Bounce handling
- Vinculado con `bounce_handler_id`
- Gestión automática de rebotes
- Feedback loop para quejas

### 8.3 Queue support
- Todos los Mailables usan trait `Queueable`
- Permite envío asíncrono
- Mejora rendimiento en envíos masivos

### 8.4 Tracking
- Tracking logs por cada envío
- Estados: `sent`, `failed`
- Vinculado con modelo `TrackingLog`

### 8.5 Multi-tenancy
- Cada lista puede tener servidor de envío propio
- Configuración por cliente (customer)
- Aislamiento entre cuentas

---

## 9. Configuración requerida

### 9.1 Variables de entorno

```env
# Configuración de correo
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=user@example.com
MAIL_PASSWORD=password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Sistema Acelle"
```

### 9.2 Settings en base de datos

**Tabla: `settings`**
```
mailer.mailer → smtp|sendmail
mailer.host → SMTP host
mailer.port → SMTP port
mailer.encryption → tls|ssl
mailer.username → SMTP user
mailer.password → SMTP password
mailer.from.name → Nombre remitente
mailer.from.address → Email remitente
mailer.sendmail_path → Ruta sendmail
cronjob_min_interval → Intervalo mínimo cron
cronjob_last_execution → Timestamp última ejecución
system.payment_gateway → Gateway de pago activo
```

---

## 10. Migraciones necesarias

### 10.1 Tabla notifications
```sql
CREATE TABLE notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uid VARCHAR(255) UNIQUE NOT NULL,
    type VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    level ENUM('info', 'warning', 'error') DEFAULT 'info',
    visibility BOOLEAN DEFAULT TRUE,
    debug TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 10.2 Tabla sending_servers
```sql
CREATE TABLE sending_servers (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uid VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    host VARCHAR(255) NULL,
    smtp_port INT NULL,
    smtp_protocol VARCHAR(10) NULL,
    smtp_username VARCHAR(255) NULL,
    smtp_password VARCHAR(255) NULL,
    api_key VARCHAR(255) NULL,
    api_secret_key VARCHAR(255) NULL,
    aws_access_key_id VARCHAR(255) NULL,
    aws_secret_access_key VARCHAR(255) NULL,
    aws_region VARCHAR(50) NULL,
    domain VARCHAR(255) NULL,
    sendmail_path VARCHAR(255) NULL,
    quota_value INT DEFAULT 0,
    quota_base VARCHAR(20) DEFAULT 'hour',
    quota_unit INT DEFAULT 1,
    bounce_handler_id BIGINT NULL,
    feedback_loop_handler_id BIGINT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    default_from_email VARCHAR(255) NULL,
    username VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 11. Dependencias de paquetes

**Composer packages identificados:**
```json
{
    "swiftmailer/swiftmailer": "^6.0",
    "illuminate/mail": "^8.0|^9.0",
    "illuminate/notifications": "^8.0|^9.0",
    "illuminate/queue": "^8.0|^9.0",
    "aws/aws-sdk-php": "^3.0",
    "sendgrid/sendgrid": "^7.0",
    "mailgun/mailgun-php": "^3.0",
    "guzzlehttp/guzzle": "^7.0"
}
```

---

## 12. API de notificaciones

### Endpoints identificados:
- `GET /api/notifications` - Listar notificaciones
- `POST /api/notifications` - Crear notificación
- `DELETE /api/notifications/{id}` - Eliminar notificación
- `PUT /api/notifications/{id}/hide` - Ocultar notificación

**Controlador:** `App\Http\Controllers\Api\NotificationController`

---

## 13. Testing

### Test mailer
```php
use Acelle\Mail\SettingMailerTest;
use Illuminate\Support\Facades\Mail;

// Enviar test
Mail::to('test@example.com')->send(new SettingMailerTest());
```

### Test con fake
```php
Mail::fake();

// Ejecutar código que envía mail
dispatch(new SendWelcomeEmail($user));

// Verificar
Mail::assertSent(RegistrationConfirmationMailer::class, function ($mail) use ($user) {
    return $mail->hasTo($user->email);
});
```

---

## 14. Puntos de mejora identificados

### 14.1 Seguridad
- ⚠️ Contraseñas SMTP almacenadas en texto plano en tabla `sending_servers`
- **Recomendación:** Encriptar con `encrypt()` antes de guardar

### 14.2 Performance
- ⚠️ Método `getNewOrActiveGeneralSubscription()` no encontrado
- Puede causar N+1 queries
- **Recomendación:** Eager loading de relaciones

### 14.3 Mantenibilidad
- ⚠️ Vista dinámica según gateway: `subscription_done_{gateway}.blade.php`
- Dificulta mantenimiento
- **Recomendación:** Vista única con secciones condicionales

### 14.4 Escalabilidad
- ✅ Sistema de colas implementado
- ✅ Rate limiting por servidor
- ⚠️ Falta monitoreo de queue health
- **Recomendación:** Integrar Laravel Horizon para monitoreo

---

## 15. Integración con sistema actual

### 15.1 Adaptaciones necesarias

**Para usar en sistema Alsernet:**

1. **Renombrar namespace:**
```php
// Acelle
namespace Acelle\Mail;

// Alsernet
namespace App\Mail;
```

2. **Adaptar Service Provider:**
```php
// Registrar en config/app.php
'providers' => [
    App\Providers\MailerServiceProvider::class,
]
```

3. **Migrar tablas:**
```bash
php artisan migrate --path=database/migrations/acelle
```

4. **Copiar vistas:**
```bash
cp -r acelle/resources/views/emails/* resources/views/emails/
```

5. **Configurar Settings:**
```php
Setting::set('mailer.host', env('MAIL_HOST'));
Setting::set('mailer.port', env('MAIL_PORT'));
// etc...
```

---

### 15.2 Compatibilidad con Laravel 12

**Cambios necesarios:**

#### Middleware registration
```php
// Acelle (Laravel 8)
protected $middleware = [
    // ...
];

// Laravel 12 (bootstrap/app.php)
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(MailerMiddleware::class);
})
```

#### Service Provider
```php
// bootstrap/providers.php
return [
    App\Providers\MailerServiceProvider::class,
];
```

#### Notification channels
```php
// Laravel 12 usa via() method
public function via($notifiable): array
{
    return ['mail', 'database'];
}
```

---

### 15.3 Modernización recomendada

#### 1. Usar Mailables con Markdown
```php
public function build()
{
    return $this->markdown('emails.subscription.done')
                ->subject('Subscription Complete');
}
```

#### 2. Usar Notifications en lugar de Mailables
```php
$user->notify(new SubscriptionDone($subscription));
```

#### 3. Usar Queued Notifications
```php
class SubscriptionDone extends Notification implements ShouldQueue
{
    use Queueable;
    // ...
}
```

#### 4. Usar Events y Listeners (ya implementado)
```php
// Ya existe en Acelle
event(new MailListSubscription($subscriber));
```

---

## 16. Conclusiones

### Fortalezas del sistema Acelle Mail:

1. ✅ **Multi-provider:** Soporta 14+ proveedores de email
2. ✅ **Queue-ready:** Todos los emails pueden procesarse en segundo plano
3. ✅ **Event-driven:** Arquitectura basada en eventos para automatización
4. ✅ **Rate limiting:** Control de cuotas por servidor
5. ✅ **Notification system:** Sistema robusto de notificaciones persistentes
6. ✅ **Template system:** Layouts reutilizables con variables dinámicas
7. ✅ **Multi-tenancy:** Aislamiento entre clientes

### Debilidades identificadas:

1. ⚠️ **Seguridad:** Credenciales sin encriptar
2. ⚠️ **Documentación:** Métodos sin documentar (`getNewOrActiveGeneralSubscription`)
3. ⚠️ **Vistas dinámicas:** Dificultan mantenimiento
4. ⚠️ **Laravel 8/9:** Necesita migración a Laravel 12

### Recomendaciones finales:

1. **Migrar gradualmente** componentes útiles a sistema Alsernet
2. **Modernizar** a Laravel 12 conventions (Middleware, Providers)
3. **Encriptar** credenciales sensibles
4. **Documentar** métodos faltantes
5. **Monitorear** con Laravel Horizon
6. **Testear** exhaustivamente tras migración

---

## 17. Referencias

### Documentación oficial consultada:
- Laravel 12 Mail: https://laravel.com/docs/12.x/mail
- Laravel 12 Notifications: https://laravel.com/docs/12.x/notifications
- Laravel 12 Queues: https://laravel.com/docs/12.x/queues
- SwiftMailer: https://swiftmailer.symfony.com/docs/introduction.html

### Archivos clave analizados:
1. `/Users/functionbytes/Function/Coding/acelle/app/Mail/RegistrationConfirmationMailer.php`
2. `/Users/functionbytes/Function/Coding/acelle/app/Mail/SettingMailerTest.php`
3. `/Users/functionbytes/Function/Coding/acelle/app/Mail/SubscriptionDoneMailer.php`
4. `/Users/functionbytes/Function/Coding/acelle/app/Model/Notification.php`
5. `/Users/functionbytes/Function/Coding/acelle/app/Notifications/ResetPassword.php`
6. `/Users/functionbytes/Function/Coding/acelle/app/Providers/MailerServiceProvider.php`
7. `/Users/functionbytes/Function/Coding/acelle/app/Model/SendingServer.php`
8. `/Users/functionbytes/Function/Coding/acelle/app/Listeners/SendListNotificationToSubscriber.php`

### Modelos relacionados:
- `MailList.php` - Gestión de listas de correo
- `Subscriber.php` - Suscriptores
- `Campaign.php` - Campañas de email
- `TrackingLog.php` - Logs de envío
- `Layout.php` - Plantillas de email
- `Page.php` - Páginas de email

---

**Autor del análisis:** Claude Sonnet 4.5
**Fecha:** 29 de enero de 2026
**Versión del documento:** 1.0
