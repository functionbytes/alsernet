<?php

namespace Modules\HelpdeskLivechat\Database\Seeders;

use Illuminate\Database\Seeder;

class HelpdeskLivechatDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HelpdeskLivechatPermissionsSeeder::class,
        ]);
    }
}
