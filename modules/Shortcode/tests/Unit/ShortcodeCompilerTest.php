<?php

namespace Modules\Shortcode\Tests\Unit;

use Modules\Shortcode\Compiler\ShortcodeCompiler;
use Tests\TestCase;

class ShortcodeCompilerTest extends TestCase
{
    protected ShortcodeCompiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compiler = new ShortcodeCompiler;
    }

    /** @test */
    public function it_can_register_a_shortcode()
    {
        $this->compiler->register('test', function ($attrs, $content) {
            return 'TEST';
        });

        $this->assertTrue($this->compiler->has('test'));
    }

    /** @test */
    public function it_can_compile_a_simple_shortcode()
    {
        $this->compiler->register('test', function ($attrs, $content) {
            return 'COMPILED';
        });

        $result = $this->compiler->compile('[test]content[/test]');

        $this->assertEquals('COMPILED', $result);
    }

    /** @test */
    public function it_can_parse_shortcode_attributes()
    {
        $this->compiler->register('test', function ($attrs, $content) {
            return $attrs['foo'].'-'.$attrs['bar'];
        });

        $result = $this->compiler->compile('[test foo="hello" bar="world"]content[/test]');

        $this->assertEquals('hello-world', $result);
    }

    /** @test */
    public function it_can_compile_self_closing_shortcode()
    {
        $this->compiler->register('test', function ($attrs, $content) {
            return 'SELF-CLOSING';
        });

        $result = $this->compiler->compile('[test /]');

        $this->assertEquals('SELF-CLOSING', $result);
    }

    /** @test */
    public function it_returns_original_if_shortcode_not_registered()
    {
        $original = '[unknown]content[/unknown]';
        $result = $this->compiler->compile($original);

        $this->assertEquals($original, $result);
    }

    /** @test */
    public function it_can_strip_shortcodes()
    {
        $content = 'Before [test]content[/test] After';
        $result = $this->compiler->strip($content);

        $this->assertEquals('Before  After', $result);
    }

    /** @test */
    public function it_can_handle_nested_shortcodes()
    {
        $this->compiler->register('outer', function ($attrs, $content) {
            return '<div class="outer">'.$content.'</div>';
        });

        $this->compiler->register('inner', function ($attrs, $content) {
            return '<span class="inner">'.$content.'</span>';
        });

        $result = $this->compiler->compile('[outer][inner]text[/inner][/outer]');

        $this->assertStringContainsString('class="outer"', $result);
        $this->assertStringContainsString('class="inner"', $result);
        $this->assertStringContainsString('text', $result);
    }

    /** @test */
    public function it_can_unregister_shortcode()
    {
        $this->compiler->register('test', function ($attrs, $content) {
            return 'TEST';
        });

        $this->assertTrue($this->compiler->has('test'));

        $this->compiler->unregister('test');

        $this->assertFalse($this->compiler->has('test'));
    }

    /** @test */
    public function it_can_get_all_registered_shortcodes()
    {
        $this->compiler->register('test1', function () {});
        $this->compiler->register('test2', function () {});

        $all = $this->compiler->all();

        $this->assertContains('test1', $all);
        $this->assertContains('test2', $all);
    }

    /** @test */
    public function it_can_clear_cache()
    {
        $this->compiler->register('test', function ($attrs, $content) {
            return 'TEST';
        });

        // First compilation
        $this->compiler->compile('[test]content[/test]');

        // Clear cache
        $this->compiler->clearCache();

        // Should still work after cache clear
        $result = $this->compiler->compile('[test]content[/test]');
        $this->assertEquals('TEST', $result);
    }

    /** @test */
    public function it_handles_empty_content()
    {
        $result = $this->compiler->compile('');

        $this->assertEquals('', $result);
    }

    /** @test */
    public function it_handles_content_without_shortcodes()
    {
        $content = 'Just plain text without shortcodes';
        $result = $this->compiler->compile($content);

        $this->assertEquals($content, $result);
    }

    /** @test */
    public function it_handles_shortcode_with_empty_content()
    {
        $this->compiler->register('test', function ($attrs, $content) {
            return empty($content) ? 'EMPTY' : 'NOT EMPTY';
        });

        $result = $this->compiler->compile('[test][/test]');

        $this->assertEquals('EMPTY', $result);
    }

    /** @test */
    public function it_escapes_html_in_attributes()
    {
        $this->compiler->register('test', function ($attrs, $content) {
            return htmlspecialchars($attrs['data']);
        });

        $result = $this->compiler->compile('[test data="<script>alert(1)</script>"]content[/test]');

        $this->assertStringNotContainsString('<script>', $result);
    }
}
