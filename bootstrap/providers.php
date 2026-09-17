<?php

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use Modules\Activity\Providers\ActivityServiceProvider;
use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Backup\Providers\BackupServiceProvider;
use Modules\Core\Providers\CoreServiceProvider;
use Modules\Database\Providers\DatabaseServiceProvider;
use Modules\Document\Providers\DocumentsServiceProvider;
use Modules\Erp\Providers\ErpServiceProvider;
use Modules\Forms\Providers\FormsServiceProvider;
use Modules\GiftMessage\Providers\GiftMessageServiceProvider;
use Modules\Health\Providers\HealthServiceProvider;
use Modules\HelpdeskTickets\Providers\HelpdeskTicketsServiceProvider;
use Modules\Mailer\Providers\MailerServiceProvider;
use Modules\MailsSettings\Providers\MailsSettingsServiceProvider;
use Modules\Media\Providers\MediaServiceProvider;
use Modules\Modules\Providers\EventServiceProvider;
use Modules\Notification\Providers\NotificationServiceProvider;
use Modules\PriceLabels\Providers\PriceLabelsServiceProvider;
use Modules\Queue\Providers\QueueServiceProvider;
use Modules\Role\Providers\RoleServiceProvider;
use Modules\Storage\Providers\StorageServiceProvider;
use Modules\Supplier\Providers\SupplierServiceProvider;
use Modules\System\Providers\SystemServiceProvider;
use Modules\Theme\Providers\ThemeServiceProvider;
use Modules\User\Providers\UserServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    ActivityServiceProvider::class,
    AuthServiceProvider::class,
    BackupServiceProvider::class,
    CoreServiceProvider::class,
    DatabaseServiceProvider::class,
    DocumentsServiceProvider::class,
    ErpServiceProvider::class,
    FormsServiceProvider::class,
    GiftMessageServiceProvider::class,
    HealthServiceProvider::class,
    HelpdeskTicketsServiceProvider::class,
    MailerServiceProvider::class,
    MailsSettingsServiceProvider::class,
    MediaServiceProvider::class,
    EventServiceProvider::class,
    NotificationServiceProvider::class,
    PriceLabelsServiceProvider::class,
    QueueServiceProvider::class,
    RoleServiceProvider::class,
    StorageServiceProvider::class,
    SupplierServiceProvider::class,
    SystemServiceProvider::class,
    ThemeServiceProvider::class,
    UserServiceProvider::class,
];
