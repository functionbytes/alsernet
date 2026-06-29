<?php
/**
 * SCAFFOLD: Plantilla base de test Feature para shortcodes de un Template.
 *
 * USO:
 * 1. Copia a: modules/Template/Templates/{Name}/Tests/Feature/{Name}{Category}ShortcodesTest.php
 * 2. Reemplaza:
 *    {Name}     → Nombre del template
 *    {Category} → Categoría
 *    {shortcode} → Nombre del shortcode (snake_case en métodos, kebab-case en string)
 * 3. Asegúrate de tener test methods para CADA shortcode de la clase
 * 4. Mín. 3 tests por shortcode:
 *    - Happy path (atributos válidos)
 *    - Edge case (falta atributo requerido → empty)
 *    - Enum validation (style inválido → fallback)
 */

namespace Modules\Template\Templates\{Name}\Tests\Feature;

use Tests\TestCase;

class {Name}{Category}ShortcodesTest extends TestCase
{
    /* ============================================================
       SHORTCODE 1: [shortcode-1-name]
       ============================================================ */

    /** @test */
    public function test_shortcode_1_renders_with_required_attrs(): void
    {
        $result = shortcode('[shortcode-1-name title="Test Title"][/shortcode-1-name]');

        $this->assertStringContainsString('shortcode-1-class', $result);
        $this->assertStringContainsString('Test Title', $result);
    }

    /** @test */
    public function test_shortcode_1_uses_default_style_when_not_provided(): void
    {
        $result = shortcode('[shortcode-1-name title="Test"][/shortcode-1-name]');

        $this->assertStringContainsString('shortcode-1-class--default', $result);
    }

    /** @test */
    public function test_shortcode_1_validates_style_enum_with_fallback(): void
    {
        // Estilo inválido → fallback a default
        $result = shortcode('[shortcode-1-name title="Test" style="invalid"][/shortcode-1-name]');

        $this->assertStringContainsString('shortcode-1-class--default', $result);
        $this->assertStringNotContainsString('shortcode-1-class--invalid', $result);
    }

    /** @test */
    public function test_shortcode_1_returns_empty_when_required_attr_missing(): void
    {
        // Sin title (atributo requerido)
        $result = shortcode('[shortcode-1-name][/shortcode-1-name]');

        $this->assertEmpty(trim($result));
    }

    /** @test */
    public function test_shortcode_1_escapes_html_in_attributes(): void
    {
        // XSS protection en atributos
        $result = shortcode('[shortcode-1-name title="<script>alert(1)</script>"][/shortcode-1-name]');

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    /** @test */
    public function test_shortcode_1_supports_custom_class_attribute(): void
    {
        $result = shortcode('[shortcode-1-name title="Test" class="custom-class extra"][/shortcode-1-name]');

        $this->assertStringContainsString('custom-class', $result);
        $this->assertStringContainsString('extra', $result);
    }

    /* ============================================================
       SHORTCODE 2: [shortcode-2-name]
       ============================================================ */

    /** @test */
    public function test_shortcode_2_renders_with_required_attrs(): void
    {
        $result = shortcode('[shortcode-2-name attr="value"]');

        $this->assertNotEmpty($result);
    }

    /** @test */
    public function test_shortcode_2_handles_missing_attr_gracefully(): void
    {
        $result = shortcode('[shortcode-2-name]');

        // Debe retornar empty o placeholder, NO romper
        $this->assertIsString($result);
    }

    /* ============================================================
       SHORTCODE 3: parent + children
       ============================================================ */

    /** @test */
    public function test_parent_renders_with_children(): void
    {
        $result = shortcode('[parent][child title="A"]Content[/child][child title="B"]Content[/child][/parent]');

        $this->assertStringContainsString('parent-class', $result);
        $this->assertStringContainsString('A', $result);
        $this->assertStringContainsString('B', $result);
    }

    /** @test */
    public function test_parent_renders_empty_with_no_children(): void
    {
        $result = shortcode('[parent][/parent]');

        // Comportamiento esperado: empty wrapper o nada
        $this->assertIsString($result);
    }

    /* ============================================================
       INTEGRATION TESTS
       ============================================================ */

    /** @test */
    public function test_multiple_shortcodes_render_together(): void
    {
        $content = '[shortcode-1-name title="A"][/shortcode-1-name]'
                 . '[shortcode-2-name attr="B"]'
                 . '[parent][child title="C"]X[/child][/parent]';

        $result = shortcode($content);

        $this->assertStringContainsString('A', $result);
        $this->assertStringContainsString('B', $result);
        $this->assertStringContainsString('C', $result);
    }

    /** @test */
    public function test_shortcodes_load_only_when_template_active(): void
    {
        // Verificar que estos shortcodes solo existen si template {slug} está active.
        // Si está inactivo, deberían no estar registrados.
        $active = \Modules\Template\Models\Template::where('slug', '{slug}')->value('status');

        if ($active === 'active') {
            $this->assertTrue(app('shortcode')->has('shortcode-1-name'));
        } else {
            $this->assertFalse(app('shortcode')->has('shortcode-1-name'));
        }
    }
}
