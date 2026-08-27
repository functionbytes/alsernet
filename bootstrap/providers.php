<?php

/**
 * Module providers configuration
 *
 * This configuration dynamically loads service providers based on module activation status.
 * Modules listed in modules_statuses.json with 'false' will be excluded.
 *
 * Critical modules (always loaded): Core, Auth, Role, Modules, Theme
 */

// Define all available module providers
$allProviders = [
    // Core framework modules (always loaded)
    'App\Providers\AppServiceProvider' => true,

    // Module providers - mapped by module name for dynamic loading
    'Modules\Activity\Providers\ActivityServiceProvider' => 'Activity',
    'Modules\Analytics\Providers\AnalyticsServiceProvider' => 'Analytics',
    'Modules\Auth\Providers\AuthServiceProvider' => 'Auth',
    'Modules\Backup\Providers\BackupServiceProvider' => 'Backup',
    'Modules\Core\Providers\CoreServiceProvider' => true, // Always load
    'Modules\Database\Providers\DatabaseServiceProvider' => 'Database',
    'Modules\Document\Providers\DocumentsServiceProvider' => 'Document',
    'Modules\Erp\Providers\ErpServiceProvider' => 'Erp',
    'Modules\GiftMessage\Providers\GiftMessageServiceProvider' => 'GiftMessage',
    'Modules\Health\Providers\HealthServiceProvider' => 'Health',
    'Modules\Mailer\Providers\MailerServiceProvider' => 'Mailer',
    'Modules\MailsSettings\Providers\MailsSettingsServiceProvider' => 'MailsSettings',
    'Modules\Media\Providers\MediaServiceProvider' => 'Media',
    'Modules\Modules\Providers\EventServiceProvider' => true, // Always load
    'Modules\Notification\Providers\NotificationServiceProvider' => 'Notification',
    'Modules\PriceLabels\Providers\PriceLabelsServiceProvider' => 'PriceLabels',
    'Modules\Pulse\Providers\EventServiceProvider' => 'Pulse',
    'Modules\Queue\Providers\QueueServiceProvider' => 'Queue',
    'Modules\Role\Providers\RoleServiceProvider' => true, // Always load
    'Modules\Storage\Providers\StorageServiceProvider' => 'Storage',
    'Modules\Supplier\Providers\SupplierServiceProvider' => 'Supplier',
    'Modules\System\Providers\SystemServiceProvider' => 'System',
    'Modules\Telescope\Providers\TelescopeServiceProvider' => 'Telescope',
    'Modules\Theme\Providers\ThemeServiceProvider' => true, // Always load
    'Modules\User\Providers\UserServiceProvider' => 'User',
];

// Load module statuses
$modulesStatusFile = base_path('modules_statuses.json');
$modulesStatus = [];

if (file_exists($modulesStatusFile)) {
    $modulesStatus = json_decode(file_get_contents($modulesStatusFile), true) ?? [];
}

// Filter providers based on module activation status
$providers = [];

foreach ($allProviders as $providerClass => $moduleName) {
    // Always load core app provider and critical modules
    if ($moduleName === true) {
        $providers[] = $providerClass;

        continue;
    }

    // For module providers, check if the module is enabled
    if (isset($modulesStatus[$moduleName]) && $modulesStatus[$moduleName] === true) {
        $providers[] = $providerClass;
    }
}

return $providers;
