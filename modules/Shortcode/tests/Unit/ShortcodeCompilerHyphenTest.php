<?php

namespace Modules\Shortcode\Tests\Unit;

use Modules\Shortcode\Compiler\ShortcodeCompiler;
use Tests\TestCase;

class ShortcodeCompilerHyphenTest extends TestCase
{
    protected ShortcodeCompiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compiler = new ShortcodeCompiler;
    }

    /** @test */
    public function it_compiles_self_closing_shortcode_with_hyphen_in_name(): void
    {
        $this->compiler->register('contact-form', function ($attrs, $content) {
            return 'FORM';
        });

        $result = $this->compiler->compile('[contact-form /]');

        $this->assertEquals('FORM', $result);
    }

    /** @test */
    public function it_compiles_paired_shortcode_with_hyphen_in_name(): void
    {
        $this->compiler->register('accordion-item', function ($attrs, $content) {
            return 'ITEM:'.$content;
        });

        $result = $this->compiler->compile('[accordion-item title="x"]body[/accordion-item]');

        $this->assertEquals('ITEM:body', $result);
    }

    /** @test */
    public function it_parses_attribute_names_with_hyphens(): void
    {
        $this->compiler->register('test', function ($attrs, $content) {
            return ($attrs['data-id'] ?? 'none').'|'.($attrs['aria-label'] ?? 'none');
        });

        $result = $this->compiler->compile('[test data-id="42" aria-label="Cerrar"]x[/test]');

        $this->assertEquals('42|Cerrar', $result);
    }

    /** @test */
    public function it_accepts_single_quoted_attributes(): void
    {
        $this->compiler->register('test', function ($attrs, $content) {
            return $attrs['foo'] ?? 'none';
        });

        $result = $this->compiler->compile("[test foo='bar']x[/test]");

        $this->assertEquals('bar', $result);
    }

    /** @test */
    public function it_strips_shortcodes_with_hyphens(): void
    {
        $input = 'antes [contact-form title="X" /] medio [accordion-item]y[/accordion-item] fin';

        $result = $this->compiler->strip($input);

        $this->assertStringNotContainsString('[', $result);
        $this->assertStringNotContainsString(']', $result);
        $this->assertStringContainsString('antes', $result);
        $this->assertStringContainsString('medio', $result);
        $this->assertStringContainsString('fin', $result);
    }

    /** @test */
    public function it_returns_empty_string_from_image_shortcode_when_id_is_non_numeric(): void
    {
        $result = shortcode('[image id="abc"; DROP TABLE users; /]');

        $this->assertEquals('', $result);
    }

    /** @test */
    public function get_registered_returns_metadata_array(): void
    {
        $this->compiler->register('demo', function () {}, [
            'description' => 'Demo',
            'example' => '[demo /]',
            'attributes' => ['foo' => 'bar'],
        ]);

        $registered = $this->compiler->getRegistered();

        $this->assertCount(1, $registered);
        $this->assertEquals('demo', $registered[0]['name']);
        $this->assertEquals('Demo', $registered[0]['description']);
        $this->assertEquals('[demo /]', $registered[0]['example']);
        $this->assertEquals(['foo' => 'bar'], $registered[0]['attributes']);
    }
}
