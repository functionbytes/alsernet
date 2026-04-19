<?php

namespace Modules\Cookie\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Cookie\Models\CookieInventory;

class CookieInventorySeeder extends Seeder
{
    public function run(): void
    {
        $cookies = [
            [
                'name' => 'cookie_for_consent',
                'provider' => 'Este sitio',
                'category' => 'essential',
                'purpose' => 'Almacena el consentimiento de cookies del usuario',
                'duration' => '1 año',
                'sort_order' => 0,
            ],
            [
                'name' => 'PHPSESSID',
                'provider' => 'Este sitio',
                'category' => 'essential',
                'purpose' => 'Mantiene la sesión del usuario activa',
                'duration' => 'Sesión',
                'sort_order' => 1,
            ],
            [
                'name' => 'XSRF-TOKEN',
                'provider' => 'Este sitio',
                'category' => 'essential',
                'purpose' => 'Protección contra ataques CSRF',
                'duration' => 'Sesión',
                'sort_order' => 2,
            ],
            [
                'name' => '_ga',
                'provider' => 'Google Analytics',
                'category' => 'analytics',
                'purpose' => 'Distingue usuarios únicos para análisis',
                'duration' => '2 años',
                'sort_order' => 3,
            ],
            [
                'name' => '_ga_*',
                'provider' => 'Google Analytics',
                'category' => 'analytics',
                'purpose' => 'Persiste el estado de la sesión para GA4',
                'duration' => '2 años',
                'sort_order' => 4,
            ],
            [
                'name' => '_gid',
                'provider' => 'Google Analytics',
                'category' => 'analytics',
                'purpose' => 'Distingue usuarios durante 24h',
                'duration' => '24 horas',
                'sort_order' => 5,
            ],
            [
                'name' => '_fbp',
                'provider' => 'Meta (Facebook)',
                'category' => 'marketing',
                'purpose' => 'Identifica navegadores para remarketing',
                'duration' => '3 meses',
                'sort_order' => 6,
            ],
            [
                'name' => '_fbc',
                'provider' => 'Meta (Facebook)',
                'category' => 'marketing',
                'purpose' => 'Almacena el parámetro fbclid de la URL',
                'duration' => '3 meses',
                'sort_order' => 7,
            ],
            [
                'name' => '_ttp',
                'provider' => 'TikTok',
                'category' => 'marketing',
                'purpose' => 'Mide la efectividad de los anuncios',
                'duration' => '13 meses',
                'sort_order' => 8,
            ],
            [
                'name' => '_uetsid',
                'provider' => 'Microsoft UET',
                'category' => 'marketing',
                'purpose' => 'Almacena sesiones de usuario para UET',
                'duration' => '1 día',
                'sort_order' => 9,
            ],
            [
                'name' => 'li_sugr',
                'provider' => 'LinkedIn',
                'category' => 'marketing',
                'purpose' => 'Atribución de conversiones de LinkedIn',
                'duration' => '3 meses',
                'sort_order' => 10,
            ],
            [
                'name' => 'twid',
                'provider' => 'Twitter/X',
                'category' => 'marketing',
                'purpose' => 'Autenticación en Twitter/X',
                'duration' => '5 años',
                'sort_order' => 11,
            ],
        ];

        foreach ($cookies as $cookie) {
            CookieInventory::firstOrCreate(
                ['name' => $cookie['name']],
                array_merge($cookie, ['is_active' => true])
            );
        }

        $this->command->info('Cookie inventory seeded with '.count($cookies).' entries.');
    }
}
