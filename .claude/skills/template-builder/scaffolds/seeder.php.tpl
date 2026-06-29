<?php
/**
 * SCAFFOLD: Plantilla base de seeder para activar un template.
 *
 * USO:
 * 1. Copia a: modules/Template/database/seeders/{Name}TemplateSeeder.php
 * 2. Reemplaza:
 *    {Name}        → Nombre del template (PascalCase, ej: Wolmart)
 *    {slug}        → slug kebab-case (ej: wolmart)
 *    {description} → Descripción del template
 *    {origin}      → Origen (ThemeForest URL, autor, etc.)
 *    {Author}      → Autor original
 *    {N}           → Número de shortcodes
 * 3. Ejecutar:
 *    composer dump-autoload
 *    php artisan db:seed --class="Modules\\Template\\Database\\Seeders\\{Name}TemplateSeeder"
 *    php artisan optimize:clear
 */

namespace Modules\Template\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Template\Models\Template;

/**
 * Seeder del template {Name}.
 *
 * Inserta el row en `templates` con `status='active'` que activa los shortcodes
 * específicos del template (ubicados en modules/Template/Templates/{Name}/Shortcodes/).
 *
 * El TemplateServiceProvider auto-descubre las clases via glob() y las registra al boot.
 *
 * Para activar:
 *   composer dump-autoload
 *   php artisan db:seed --class="Modules\\Template\\Database\\Seeders\\{Name}TemplateSeeder"
 *   php artisan optimize:clear
 *
 * Para desactivar (volver a otro template):
 *   UPDATE templates SET status='inactive' WHERE slug='{slug}';
 *   UPDATE templates SET status='active' WHERE slug='other-template-slug';
 */
class {Name}TemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Desactivar templates previos antes de activar este (solo 1 puede estar activo).
        Template::query()->where('status', 'active')->update(['status' => 'inactive']);

        Template::updateOrCreate(
            ['slug' => '{slug}'],
            [
                'name' => '{Name}',
                'description' => '{description}',
                'template_path' => 'modules/Template/Templates/{Name}',
                'status' => 'active',
                'author' => '{Author} (adaptado para Alsernet)',
                'version' => '1.0.0',
            ]
        );

        $this->command?->info('✓ Template {Name} activado.');
        $this->command?->info('✓ {N} shortcodes específicos cargarán al boot:');
        $this->command?->info('  Categorías: Content, Structure, Utility, Media, Effects, Marketplace');
        $this->command?->info('');
        $this->command?->info('Verificar con:');
        $this->command?->info('  php artisan shortcode:list');
    }
}
