<?php

namespace Modules\Template\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Template\Models\Template;

/**
 * Seeder del template Riode.
 *
 * Crea el row en `templates` que activa los shortcodes específicos de Riode
 * (cta, countdown, counter, icon-box, title, tabs, slider, banner, hotspot, etc.).
 *
 * El TemplateServiceProvider lee `status='active'` y carga las clases de:
 *   modules/Template/Templates/Riode/Shortcodes/*.php
 *
 * Para activar:
 *   php artisan db:seed --class="Modules\\Template\\Database\\Seeders\\RiodeTemplateSeeder"
 *
 * Para desactivar (volver a otro template):
 *   UPDATE templates SET status='inactive' WHERE slug='riode';
 *   UPDATE templates SET status='active' WHERE slug='default';
 */
class RiodeTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Desactivar templates previos antes de activar Riode (solo 1 puede estar activo).
        Template::query()->where('status', 'active')->update(['status' => 'inactive']);

        Template::updateOrCreate(
            ['slug' => 'riode'],
            [
                'name' => 'Riode',
                'description' => 'Template Riode (D-Themes) — incluye 15 shortcodes especiales: cta, countdown, counter, icon-box, title, tabs, slider, banner, hotspot, etc.',
                'template_path' => 'modules/Template/Templates/Riode',
                'status' => 'active',
                'author' => 'D-Themes (adaptado para Alsernet)',
                'version' => '1.0.0',
            ]
        );

        $this->command?->info('✓ Template Riode activado.');
        $this->command?->info('✓ 15 shortcodes específicos cargarán al boot:');
        $this->command?->info('  Content (7): cta, cta-column, countdown, counter, counter-grid, icon-box, icon-box-grid');
        $this->command?->info('  Structure (8): title, tabs, tab, slider, slide, banner, hotspot, hotspot-pin');
    }
}
