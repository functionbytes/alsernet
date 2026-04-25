<?php

namespace Modules\EcommercePayment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Setting;

class EcommercePaymentDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'ecommerce_payment.wompi.status' => '0',
            'ecommerce_payment.wompi.name' => 'Wompi',
            'ecommerce_payment.wompi.description' => 'Paga con Wompi',
            'ecommerce_payment.wompi.mode' => 'sandbox',
            'ecommerce_payment.wompi.public_key' => '',
            'ecommerce_payment.wompi.private_key' => '',
            'ecommerce_payment.wompi.integrity_secret' => '',
            'ecommerce_payment.wompi.event_secret' => '',
            'ecommerce_payment.wompi.notification_email' => '',
        ];

        foreach ($settings as $key => $value) {
            if (! Setting::where('key', $key)->exists()) {
                Setting::set($key, $value);
            }
        }
    }
}
