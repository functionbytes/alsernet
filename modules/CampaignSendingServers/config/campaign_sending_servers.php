<?php

use Modules\CampaignSendingServers\Models\SendingServerAmazonApi;
use Modules\CampaignSendingServers\Models\SendingServerAmazonSmtp;
use Modules\CampaignSendingServers\Models\SendingServerBrevoApi;
use Modules\CampaignSendingServers\Models\SendingServerElasticEmailApi;
use Modules\CampaignSendingServers\Models\SendingServerElasticEmailSmtp;
use Modules\CampaignSendingServers\Models\SendingServerMailgunApi;
use Modules\CampaignSendingServers\Models\SendingServerMailgunSmtp;
use Modules\CampaignSendingServers\Models\SendingServerSendGridApi;
use Modules\CampaignSendingServers\Models\SendingServerSendGridSmtp;
use Modules\CampaignSendingServers\Models\SendingServerSendmail;
use Modules\CampaignSendingServers\Models\SendingServerSmtp;
use Modules\CampaignSendingServers\Models\SendingServerSparkPostApi;
use Modules\CampaignSendingServers\Models\SendingServerSparkPostSmtp;

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del módulo CampaignSendingServers
    |--------------------------------------------------------------------------
    */

    // Proveedores soportados por el módulo. Cada clave es el `type` que se
    // guarda en la columna `type` de la tabla `campaign_sending_servers` y
    // mapea (vía SendingServer::mapServerType) a la clase concreta.
    'providers' => [
        'amazon-api' => ['label' => 'Amazon SES (API)', 'class' => SendingServerAmazonApi::class],
        'amazon-smtp' => ['label' => 'Amazon SES (SMTP)', 'class' => SendingServerAmazonSmtp::class],
        'sendgrid-api' => ['label' => 'SendGrid (API)', 'class' => SendingServerSendGridApi::class],
        'sendgrid-smtp' => ['label' => 'SendGrid (SMTP)', 'class' => SendingServerSendGridSmtp::class],
        'mailgun-api' => ['label' => 'Mailgun (API)', 'class' => SendingServerMailgunApi::class],
        'mailgun-smtp' => ['label' => 'Mailgun (SMTP)', 'class' => SendingServerMailgunSmtp::class],
        'sparkpost-api' => ['label' => 'SparkPost (API)', 'class' => SendingServerSparkPostApi::class],
        'sparkpost-smtp' => ['label' => 'SparkPost (SMTP)', 'class' => SendingServerSparkPostSmtp::class],
        'elasticemail-api' => ['label' => 'ElasticEmail (API)', 'class' => SendingServerElasticEmailApi::class],
        'elasticemail-smtp' => ['label' => 'ElasticEmail (SMTP)', 'class' => SendingServerElasticEmailSmtp::class],
        'brevo-api' => ['label' => 'Brevo (API)', 'class' => SendingServerBrevoApi::class],
        'smtp' => ['label' => 'SMTP genérico', 'class' => SendingServerSmtp::class],
        'sendmail' => ['label' => 'Sendmail (local)', 'class' => SendingServerSendmail::class],
    ],

    // Rate limits por defecto al crear un servidor. Pueden sobrescribirse por
    // servidor desde la UI del manager.
    'default_rate_limits' => [
        'per_minute' => 60,
        'per_hour' => 3600,
        'per_day' => 50000,
    ],

    // Configuración del cifrado de credenciales: usamos los Casts encrypted
    // de Laravel, que dependen de APP_KEY. Si APP_KEY rota, las credenciales
    // existentes en BD quedan ilegibles — usar `php artisan key:rotate` con cuidado.
    'encrypted_attributes' => [
        'api_key',
        'api_secret_key',
        'aws_access_key_id',
        'aws_secret_access_key',
        'smtp_password',
    ],

    // Tabla del cron `campaign_sending_servers:verify-tracking-domains`.
    'verify_tracking_domains' => [
        'cron' => 'hourly',
    ],

    'verify_senders' => [
        'cron' => 'hourly',
    ],

    // Email verification: NeverBounce / ZeroBounce.
    // driver = neverbounce | zerobounce | null (desactivado)
    'email_verification' => [
        'driver' => env('CAMPAIGN_VERIFIER_DRIVER'),
        'api_key' => env('CAMPAIGN_VERIFIER_API_KEY'),
    ],
];
