<?php
/**
 * SCAFFOLD: Plantilla base para clase de Shortcodes de un Template.
 *
 * USO:
 * 1. Copia a: modules/Template/Templates/{Name}/Shortcodes/{Name}{Category}Shortcodes.php
 * 2. Reemplaza placeholders:
 *    {Name}     → Nombre del template (PascalCase, ej: Wolmart)
 *    {Category} → Categoría (Content/Structure/Utility/Media/Effects/Marketplace)
 *    {slug}     → slug del template (kebab-case, ej: wolmart)
 *    {ShortcodeName} → Nombre del shortcode (PascalCase para método, kebab para nombre)
 * 3. Adapta atributos según docs en analisis/03-element-components.md
 * 4. Crea el view Blade correspondiente en Resources/views/shortcodes/
 * 5. Crea test Feature en Tests/Feature/
 */

namespace Modules\Template\Templates\{Name}\Shortcodes;

use Modules\Shortcode\Compiler\ShortcodeCompiler;

class {Name}{Category}Shortcodes
{
    public function __construct(private readonly ShortcodeCompiler $compiler) {}

    /**
     * Registra todos los shortcodes de la categoría.
     * Se invoca automáticamente desde TemplateServiceProvider::registerActiveTemplateShortcodes()
     * cuando el template está activo en DB (status='active').
     */
    public function registerAll(): void
    {
        $this->register{ShortcodeName1}();
        $this->register{ShortcodeName2}();
        $this->register{ShortcodeName3}();
        // ... añadir más
    }

    // -------------------------------------------------------------------------
    // [{shortcode-1-name} attr1="value" attr2="value"]content[/{shortcode-1-name}]
    // -------------------------------------------------------------------------
    protected function register{ShortcodeName1}(): void
    {
        $this->compiler->register('{shortcode-1-name}', function (array $attrs, string $content): string {
            // Validación rápida (return early si falta atributo crítico)
            if (empty($attrs['title'] ?? null) && trim($content) === '') {
                return '';
            }

            return view('{slug}::shortcodes.{shortcode-1-name}', compact('attrs', 'content'))->render();
        }, [
            'description' => 'Descripción breve de qué hace el shortcode.',
            'example' => '[{shortcode-1-name} attr1="value" attr2="value"]Contenido[/{shortcode-1-name}]',
            'attributes' => [
                'attr1' => 'Descripción del atributo 1',
                'attr2' => 'Descripción del atributo 2',
                'class' => 'Clases CSS extras (opcional)',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [{shortcode-2-name} ... /]
    // -------------------------------------------------------------------------
    protected function register{ShortcodeName2}(): void
    {
        $this->compiler->register('{shortcode-2-name}', function (array $attrs, string $content = ''): string {
            return view('{slug}::shortcodes.{shortcode-2-name}', compact('attrs'))->render();
        }, [
            'description' => '...',
            'example' => '[{shortcode-2-name} attr="value"]',
            'attributes' => [],
        ]);
    }

    // -------------------------------------------------------------------------
    // [{shortcode-3-name}] — child de un shortcode parent
    // -------------------------------------------------------------------------
    protected function register{ShortcodeName3}(): void
    {
        $this->compiler->register('{shortcode-3-name}', function (array $attrs, string $content): string {
            return view('{slug}::shortcodes.{shortcode-3-name}', compact('attrs', 'content'))->render();
        }, [
            'description' => 'Child shortcode. Usar dentro de [parent].',
            'example' => '[parent][{shortcode-3-name} attr="value"]Contenido[/{shortcode-3-name}][/parent]',
            'attributes' => [],
        ]);
    }
}
