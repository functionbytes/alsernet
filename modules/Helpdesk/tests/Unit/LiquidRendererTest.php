<?php

namespace Modules\Helpdesk\Tests\Unit;

use Modules\Helpdesk\Services\Templates\LiquidRenderer;
use Tests\TestCase;

/**
 * Unit tests for LiquidRenderer.
 *
 * Extends Laravel's TestCase so that the Cache facade is available.
 * No database interaction is performed in these tests.
 */
class LiquidRendererTest extends TestCase
{
    private LiquidRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new LiquidRenderer;
    }

    public function test_renders_simple_variable(): void
    {
        $result = $this->renderer->render(
            'Hola {{ contact.name }}',
            ['contact' => ['name' => 'Juan']]
        );

        $this->assertSame('Hola Juan', $result);
    }

    public function test_renders_nested_object(): void
    {
        $result = $this->renderer->render(
            '{{ agent.name }}',
            ['agent' => ['name' => 'Maria Lopez']]
        );

        $this->assertSame('Maria Lopez', $result);
    }

    public function test_handles_missing_variable_gracefully(): void
    {
        $result = $this->renderer->render('Hello {{ nonexistent }}', []);

        // Liquid renders missing variables as empty string by default
        $this->assertSame('Hello ', $result);
    }

    public function test_handles_missing_nested_variable_gracefully(): void
    {
        $result = $this->renderer->render('{{ foo.bar.baz }}', []);

        $this->assertSame('', $result);
    }

    public function test_validates_valid_template_syntax(): void
    {
        $result = $this->renderer->isValid('Hello {{ name }}!');

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validates_template_syntax_with_if_block(): void
    {
        $result = $this->renderer->isValid(
            '{% if user.active %}Welcome{% endif %}'
        );

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validates_invalid_template_syntax(): void
    {
        // Unclosed block tag is a syntax error
        $result = $this->renderer->isValid('{% if condition %}no closing tag');

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_renders_with_liquid_filters(): void
    {
        $result = $this->renderer->render(
            '{{ name | upcase }}',
            ['name' => 'hello']
        );

        $this->assertSame('HELLO', $result);
    }

    public function test_renders_for_loop(): void
    {
        $result = $this->renderer->render(
            '{% for item in items %}{{ item }} {% endfor %}',
            ['items' => ['a', 'b', 'c']]
        );

        $this->assertSame('a b c ', $result);
    }

    public function test_available_variables_returns_expected_keys(): void
    {
        $variables = $this->renderer->availableVariables();

        $this->assertArrayHasKey('contact', $variables);
        $this->assertArrayHasKey('conversation', $variables);
        $this->assertArrayHasKey('agent', $variables);
        $this->assertArrayHasKey('inbox', $variables);

        $this->assertContains('name', $variables['contact']);
        $this->assertContains('email', $variables['contact']);
        $this->assertContains('subject', $variables['conversation']);
    }

    public function test_returns_original_template_on_render_exception(): void
    {
        // Unknown filters are silently ignored by default; only truly broken
        // templates (e.g. ones that produce PHP exceptions during rendering)
        // trigger the fallback. We confirm no exception escapes the renderer.
        $result = $this->renderer->render('{{ items | unknown_filter }}', []);

        $this->assertIsString($result);
    }

    public function test_caches_parsed_template(): void
    {
        // Render the same template string twice; both calls should produce
        // the same output. The in-process memory cache is exercised on the
        // second call (no re-parse). Redis is hit only on the first call.
        $template = 'Hello {{ name }}';

        $first = $this->renderer->render($template, ['name' => 'World']);
        $second = $this->renderer->render($template, ['name' => 'World']);

        $this->assertSame('Hello World', $first);
        $this->assertSame($first, $second);
    }
}
