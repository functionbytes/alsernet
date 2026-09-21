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
            'mails-settings.outgoing.view' => 'Ver configuración de correo saliente',
            'mails-settings.outgoing.update' => 'Actualizar configuración de correo saliente',
            'mails-settings.outgoing.test' => 'Probar correo saliente',
            'mails-settings.incoming.view' => 'Ver configuración de correo entrante',
            'mails-settings.incoming.update' => 'Actualizar configuración de correo entrante',
            'mails-settings.gmail.connect' => 'Conectar cuenta de Gmail',
            'mails-settings.gmail.disconnect' => 'Desconectar cuenta de Gmail',
            'mails-settings.imap.create' => 'Crear conexión IMAP',
            'mails-settings.imap.update' => 'Actualizar conexión IMAP',
            'mails-settings.imap.delete' => 'Eliminar conexión IMAP',
            'mails-settings.imap.test' => 'Probar conexión IMAP',
            'mails-settings.mailgun.update' => 'Actualizar configuración de Mailgun',
            'mails-settings.phplist.update' => 'Actualizar configuración de phpList',
            'mails-settings.phplist.test' => 'Probar conexión phpList',
            'mails-settings.api-key.generate' => 'Generar clave de API de correo',
            'mails-settings.api-docs.view' => 'Ver documentación de la API de correo',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        $superSettings = Role::where('name', 'super-settings')->first();
        if ($superSettings) {
            $superSettings->givePermissionTo(array_keys($permissions));
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
