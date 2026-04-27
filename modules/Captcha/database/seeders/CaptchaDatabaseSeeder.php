<?php

namespace Modules\Captcha\Database\Seeders;

use Illuminate\Database\Seeder;

class CaptchaDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CaptchaPermissionsSeeder::class,
        ]);
    }
}
