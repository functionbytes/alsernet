<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;
use Modules\Campaign\Models\Template\PageTemplate;
use Modules\Campaign\Models\Template\Template;
use Modules\Campaign\Models\Template\TemplateCategory;
use Modules\Campaign\Services\PageTemplateService;

/**
 * Siembra las plantillas de página Base/Extended desde resources/themes/default/master/sample/page.
 * Idempotente: re-ejecutar reemplaza las plantillas con el mismo nombre.
 *
 * Espejo de la sección resetPageTemplates() del TemplateSeeder de acellemail.
 */
class SeedPageTemplatesCommand extends Command
{
    protected $signature = 'campaign:seed-page-templates {--fresh : Borra todas las page templates antes de sembrar}';

    protected $description = 'Siembra las plantillas de página (Base + Extended) para el builder';

    /** tier => base|extended por cada muestra de resources/themes/default/master/sample/page */
    private array $templates = [
        ['tier' => 'base', 'file' => 'McShowcase', 'name' => 'Simple Showcase'],
        ['tier' => 'base', 'file' => 'McProductShowcase', 'name' => 'Simple Product'],
        ['tier' => 'base', 'file' => 'McShoppingCart', 'name' => 'Simple Cart'],
        ['tier' => 'base', 'file' => 'McCheckout', 'name' => 'Simple Checkout'],
        ['tier' => 'base', 'file' => 'McComplete', 'name' => 'Simple Order Complete'],
        ['tier' => 'extended', 'file' => 'ProductShowcase', 'name' => 'Product Showcase'],
        ['tier' => 'extended', 'file' => 'ShoppingCart', 'name' => 'Shopping Cart'],
        ['tier' => 'extended', 'file' => 'Checkout', 'name' => 'Checkout'],
        ['tier' => 'extended', 'file' => 'OrderComplete', 'name' => 'Order Complete'],
    ];

    public function handle(PageTemplateService $service): int
    {
        // Categorías Base / Extended (idempotente).
        $base = TemplateCategory::firstOrCreate(['name' => 'Base']);
        $extended = TemplateCategory::firstOrCreate(['name' => 'Extended']);
        $this->info("Categorías: Base(#{$base->id}) Extended(#{$extended->id})");

        if ($this->option('fresh')) {
            foreach (PageTemplate::with('template')->get() as $pt) {
                $pt->template?->deleteAndCleanup();
                $pt->delete();
            }
            $this->warn('Page templates existentes borradas (--fresh).');
        }

        $basePath = module_path('Campaign', 'resources/themes/default/master/sample/page');
        $seeded = 0;

        foreach ($this->templates as $meta) {
            $jsonFile = $basePath.'/'.$meta['file'].'.json';
            $htmlFile = $basePath.'/'.$meta['file'].'.html';

            if (! file_exists($jsonFile) || ! file_exists($htmlFile)) {
                $this->warn("Saltando {$meta['name']} — falta json/html.");

                continue;
            }

            // Idempotencia: borrar la page template previa con el mismo nombre.
            foreach (PageTemplate::with('template')->where('name', $meta['name'])->get() as $old) {
                $old->template?->deleteAndCleanup();
                $old->delete();
            }

            $json = (string) file_get_contents($jsonFile);
            $content = (string) file_get_contents($htmlFile);

            // Crear el Template subyacente con el contenido de la muestra.
            $template = Template::createBuilderTemplate('default', $meta['name'], $json, $content);

            // Thumbnail si existe.
            foreach (['png', 'svg', 'jpg'] as $ext) {
                $thumb = $basePath.'/'.$meta['file'].'.'.$ext;
                if (file_exists($thumb)) {
                    $template->updateThumbnailFromPath($thumb);
                    break;
                }
            }

            // Envolver con PageTemplate y clasificar.
            $categoryName = $meta['tier'] === 'base' ? 'Base' : 'Extended';
            $service->seedBaseTemplate($meta['name'], $template, $categoryName);

            // El servicio clona el template (deja el original suelto): limpiarlo.
            $template->deleteAndCleanup();

            $seeded++;
            $this->line("  ✓ {$meta['name']} ({$categoryName})");
        }

        $this->info("Sembradas {$seeded} plantillas de página.");

        return self::SUCCESS;
    }
}
