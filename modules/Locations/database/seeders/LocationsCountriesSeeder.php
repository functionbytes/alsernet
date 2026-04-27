<?php

namespace Modules\Locations\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Locations\Models\Country;

class LocationsCountriesSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'México',           'code' => 'MX', 'phone_code' => '+52',  'currency_code' => 'MXN', 'currency_symbol' => '$',  'flag_emoji' => '🇲🇽', 'order' => 1],
            ['name' => 'Colombia',          'code' => 'CO', 'phone_code' => '+57',  'currency_code' => 'COP', 'currency_symbol' => '$',  'flag_emoji' => '🇨🇴', 'order' => 2],
            ['name' => 'Argentina',         'code' => 'AR', 'phone_code' => '+54',  'currency_code' => 'ARS', 'currency_symbol' => '$',  'flag_emoji' => '🇦🇷', 'order' => 3],
            ['name' => 'Chile',             'code' => 'CL', 'phone_code' => '+56',  'currency_code' => 'CLP', 'currency_symbol' => '$',  'flag_emoji' => '🇨🇱', 'order' => 4],
            ['name' => 'Perú',              'code' => 'PE', 'phone_code' => '+51',  'currency_code' => 'PEN', 'currency_symbol' => 'S/', 'flag_emoji' => '🇵🇪', 'order' => 5],
            ['name' => 'Venezuela',         'code' => 'VE', 'phone_code' => '+58',  'currency_code' => 'VES', 'currency_symbol' => 'Bs.', 'flag_emoji' => '🇻🇪', 'order' => 6],
            ['name' => 'Ecuador',           'code' => 'EC', 'phone_code' => '+593', 'currency_code' => 'USD', 'currency_symbol' => '$',  'flag_emoji' => '🇪🇨', 'order' => 7],
            ['name' => 'Bolivia',           'code' => 'BO', 'phone_code' => '+591', 'currency_code' => 'BOB', 'currency_symbol' => 'Bs', 'flag_emoji' => '🇧🇴', 'order' => 8],
            ['name' => 'Paraguay',          'code' => 'PY', 'phone_code' => '+595', 'currency_code' => 'PYG', 'currency_symbol' => '₲', 'flag_emoji' => '🇵🇾', 'order' => 9],
            ['name' => 'Uruguay',           'code' => 'UY', 'phone_code' => '+598', 'currency_code' => 'UYU', 'currency_symbol' => '$U', 'flag_emoji' => '🇺🇾', 'order' => 10],
            ['name' => 'Brasil',            'code' => 'BR', 'phone_code' => '+55',  'currency_code' => 'BRL', 'currency_symbol' => 'R$', 'flag_emoji' => '🇧🇷', 'order' => 11],
            ['name' => 'Guatemala',         'code' => 'GT', 'phone_code' => '+502', 'currency_code' => 'GTQ', 'currency_symbol' => 'Q',  'flag_emoji' => '🇬🇹', 'order' => 12],
            ['name' => 'Honduras',          'code' => 'HN', 'phone_code' => '+504', 'currency_code' => 'HNL', 'currency_symbol' => 'L',  'flag_emoji' => '🇭🇳', 'order' => 13],
            ['name' => 'El Salvador',       'code' => 'SV', 'phone_code' => '+503', 'currency_code' => 'USD', 'currency_symbol' => '$',  'flag_emoji' => '🇸🇻', 'order' => 14],
            ['name' => 'Nicaragua',         'code' => 'NI', 'phone_code' => '+505', 'currency_code' => 'NIO', 'currency_symbol' => 'C$', 'flag_emoji' => '🇳🇮', 'order' => 15],
            ['name' => 'Costa Rica',        'code' => 'CR', 'phone_code' => '+506', 'currency_code' => 'CRC', 'currency_symbol' => '₡', 'flag_emoji' => '🇨🇷', 'order' => 16],
            ['name' => 'Panamá',            'code' => 'PA', 'phone_code' => '+507', 'currency_code' => 'PAB', 'currency_symbol' => 'B/.', 'flag_emoji' => '🇵🇦', 'order' => 17],
            ['name' => 'Cuba',              'code' => 'CU', 'phone_code' => '+53',  'currency_code' => 'CUP', 'currency_symbol' => '$',  'flag_emoji' => '🇨🇺', 'order' => 18],
            ['name' => 'República Dominicana', 'code' => 'DO', 'phone_code' => '+1', 'currency_code' => 'DOP', 'currency_symbol' => 'RD$', 'flag_emoji' => '🇩🇴', 'order' => 19],
            ['name' => 'Puerto Rico',       'code' => 'PR', 'phone_code' => '+1',   'currency_code' => 'USD', 'currency_symbol' => '$',  'flag_emoji' => '🇵🇷', 'order' => 20],
            ['name' => 'España',            'code' => 'ES', 'phone_code' => '+34',  'currency_code' => 'EUR', 'currency_symbol' => '€',  'flag_emoji' => '🇪🇸', 'order' => 21],
            ['name' => 'Estados Unidos',    'code' => 'US', 'phone_code' => '+1',   'currency_code' => 'USD', 'currency_symbol' => '$',  'flag_emoji' => '🇺🇸', 'order' => 22],
        ];

        foreach ($countries as $data) {
            Country::firstOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}
