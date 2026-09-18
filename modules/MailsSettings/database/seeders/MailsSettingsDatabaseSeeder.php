<?php

namespace Modules\MailsSettings\Database\Seeders;

use Illuminate\Database\Seeder;

class MailsSettingsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MailsSettingsPermissionsSeeder::class,
        ]);
    }
}
