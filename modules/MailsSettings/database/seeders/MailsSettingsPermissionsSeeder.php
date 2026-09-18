<?php

namespace Modules\MailsSettings\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MailsSettingsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'mails-settings.outgoing.view',
            'mails-settings.outgoing.update',
            'mails-settings.outgoing.test',
            'mails-settings.incoming.view',
            'mails-settings.incoming.update',
            'mails-settings.gmail.connect',
            'mails-settings.gmail.disconnect',
            'mails-settings.imap.create',
            'mails-settings.imap.update',
            'mails-settings.imap.delete',
            'mails-settings.imap.test',
            'mails-settings.mailgun.update',
            'mails-settings.phplist.update',
            'mails-settings.phplist.test',
            'mails-settings.api-key.generate',
            'mails-settings.api-docs.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superSettings = Role::where('name', 'super-settings')->first();
        if ($superSettings) {
            $superSettings->givePermissionTo($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
