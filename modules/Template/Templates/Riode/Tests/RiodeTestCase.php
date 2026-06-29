<?php

namespace Modules\Template\Templates\Riode\Tests;

use Modules\Template\Models\Template;
use Modules\Template\Templates\Riode\Shortcodes\RiodeContentShortcodes;
use Modules\Template\Templates\Riode\Shortcodes\RiodeEffectsShortcodes;
use Modules\Template\Templates\Riode\Shortcodes\RiodeMarketplaceShortcodes;
use Modules\Template\Templates\Riode\Shortcodes\RiodeMediaShortcodes;
use Modules\Template\Templates\Riode\Shortcodes\RiodeStructureShortcodes;
use Modules\Template\Templates\Riode\Shortcodes\RiodeUtilityShortcodes;
use Modules\Template\Tests\TestCase;

/**
 * Base test case para tests detallados de shortcodes Riode.
 *
 * Garantiza que:
 * - El template Riode está activo en DB
 * - Las 6 clases de shortcodes Riode están registradas en el compilador singleton
 * - El namespace de views `riode::` está disponible
 */
abstract class RiodeTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $viewsPath = base_path('modules/Template/Templates/Riode/Resources/views');
        if (is_dir($viewsPath) && ! view()->exists('riode::shortcodes.cta')) {
            view()->addNamespace('riode', $viewsPath);
        }

        $compiler = app('shortcode');

        (new RiodeContentShortcodes($compiler))->registerAll();
        (new RiodeStructureShortcodes($compiler))->registerAll();
        (new RiodeUtilityShortcodes($compiler))->registerAll();
        (new RiodeMediaShortcodes($compiler))->registerAll();
        (new RiodeEffectsShortcodes($compiler))->registerAll();
        (new RiodeMarketplaceShortcodes($compiler))->registerAll();
    }
}
