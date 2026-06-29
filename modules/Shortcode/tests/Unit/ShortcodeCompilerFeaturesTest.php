<?php

namespace Modules\Shortcode\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Modules\Shortcode\Compiler\ShortcodeCompiler;
use Tests\TestCase;

class ShortcodeCompilerFeaturesTest extends TestCase
{
    protected ShortcodeCompiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->compiler = new ShortcodeCompiler;
    }

    // ---------------------------------------------------------------------
    // Escape [[...]]
    // ---------------------------------------------------------------------

    public function test_double_bracket_escapes_shortcode(): void
    {
        $this->compiler->register('button', fn () => 'COMPILED');

        $result = $this->compiler->compile('Literal: [[button]] vs [button /]');

        $this->assertStringContainsString('[button]', $result);
        $this->assertStringContainsString('COMPILED', $result);
        $this->assertStringNotContainsString('[[button]]', $result);
    }

    public function test_double_bracket_escapes_paired_shortcode(): void
    {
        $this->compiler->register('alert', fn ($a, $c) => "<A>$c</A>");

        $result = $this->compiler->compile('[[alert]texto[/alert]]');

        $this->assertStringContainsString('[alert]texto[/alert]', $result);
        $this->assertStringNotContainsString('<A>', $result);
    }

    // ---------------------------------------------------------------------
    // HTML comments
    // ---------------------------------------------------------------------

    public function test_shortcodes_in_html_comments_are_not_compiled(): void
    {
        $this->compiler->register('button', fn () => 'COMPILED');

        $result = $this->compiler->compile('<!-- [button /] --> y [button /]');

        $this->assertStringContainsString('<!-- [button /] -->', $result);
        $this->assertStringContainsString('COMPILED', $result);
    }

    // ---------------------------------------------------------------------
    // Raw flag
    // ---------------------------------------------------------------------

    public function test_raw_shortcode_preserves_inner_shortcodes(): void
    {
        $this->compiler->register(
            'code',
            fn ($a, $c) => "<pre>$c</pre>",
            ['raw' => true]
        );
        $this->compiler->register('button', fn () => 'SHOULD_NOT_COMPILE');

        $result = $this->compiler->compile('[code]Uso: [button /] aquí[/code]');

        $this->assertStringContainsString('<pre>Uso: [button /] aquí</pre>', $result);
        $this->assertStringNotContainsString('SHOULD_NOT_COMPILE', $result);
    }

    // ---------------------------------------------------------------------
    // Nested same-tag
    // ---------------------------------------------------------------------

    public function test_nested_same_tag_shortcodes_compile_correctly(): void
    {
        $this->compiler->register('card', function ($attrs, $content) {
            return '<card>'.app('shortcode')->compile($content).'</card>';
        });

        app()->instance('shortcode', $this->compiler);

        $result = $this->compiler->compile('[card][card]x[/card][/card]');

        $this->assertEquals('<card><card>x</card></card>', $result);
    }

    public function test_sequential_same_tag_shortcodes_do_not_merge(): void
    {
        $this->compiler->register('card', fn ($a, $c) => "<C>$c</C>");

        $result = $this->compiler->compile('[card]a[/card] [card]b[/card]');

        $this->assertEquals('<C>a</C> <C>b</C>', $result);
    }

    // ---------------------------------------------------------------------
    // Per-shortcode cacheable
    // ---------------------------------------------------------------------

    public function test_non_cacheable_shortcode_bypasses_cache(): void
    {
        config(['shortcode.cache' => true]);

        $counter = 0;
        $this->compiler->register(
            'now',
            function () use (&$counter) {
                $counter++;

                return 'CALL#'.$counter;
            },
            ['cacheable' => false]
        );

        $a = $this->compiler->compile('[now /]');
        $b = $this->compiler->compile('[now /]');

        $this->assertEquals('CALL#1', $a);
        $this->assertEquals('CALL#2', $b);
    }

    public function test_cacheable_shortcode_uses_cache(): void
    {
        config(['shortcode.cache' => true, 'shortcode.cache_duration' => 60]);

        $counter = 0;
        $this->compiler->register('once', function () use (&$counter) {
            $counter++;

            return 'CALL#'.$counter;
        });

        $a = $this->compiler->compile('[once /]');
        $b = $this->compiler->compile('[once /]');

        $this->assertEquals('CALL#1', $a);
        $this->assertEquals('CALL#1', $b);
    }

    // ---------------------------------------------------------------------
    // Aliases
    // ---------------------------------------------------------------------

    public function test_alias_dispatches_to_target_handler(): void
    {
        $this->compiler->register('button', fn ($a, $c) => 'BUTTON:'.$c);
        $this->compiler->register('btn', fn () => '', ['alias_of' => 'button']);

        $result = $this->compiler->compile('[btn]hello[/btn]');

        $this->assertEquals('BUTTON:hello', $result);
    }

    public function test_has_detects_aliases(): void
    {
        $this->compiler->register('button', fn () => '');
        $this->compiler->register('btn', fn () => '', ['alias_of' => 'button']);

        $this->assertTrue($this->compiler->has('btn'));
    }

    // ---------------------------------------------------------------------
    // Deprecations
    // ---------------------------------------------------------------------

    public function test_deprecated_shortcode_emits_user_deprecated(): void
    {
        $this->compiler->register(
            'old',
            fn () => 'OUT',
            ['deprecated' => '2.0']
        );

        $triggered = false;
        set_error_handler(function ($errno) use (&$triggered) {
            if ($errno === E_USER_DEPRECATED) {
                $triggered = true;
            }

            return true;
        }, E_USER_DEPRECATED);

        try {
            $this->compiler->compile('[old /]');
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($triggered, 'Esperaba E_USER_DEPRECATED al usar shortcode deprecado');
    }

    // ---------------------------------------------------------------------
    // removeAll / getRegex / find
    // ---------------------------------------------------------------------

    public function test_remove_all_clears_every_shortcode(): void
    {
        $this->compiler->register('a', fn () => '');
        $this->compiler->register('b', fn () => '');

        $this->compiler->removeAll();

        $this->assertEquals([], $this->compiler->all());
    }

    public function test_get_regex_matches_only_registered_shortcodes(): void
    {
        $this->compiler->register('alpha', fn () => '');
        $this->compiler->register('beta', fn () => '');

        $regex = $this->compiler->getRegex();

        $this->assertMatchesRegularExpression($regex, '[alpha]x[/alpha]');
        $this->assertMatchesRegularExpression($regex, '[beta /]');
        $this->assertDoesNotMatchRegularExpression($regex, '[gamma]x[/gamma]');
    }

    public function test_find_returns_shortcode_occurrences_with_metadata(): void
    {
        $this->compiler->register('alpha', fn () => '');
        $this->compiler->register('beta', fn () => '');

        $found = $this->compiler->find('Pre [alpha foo="bar"]x[/alpha] mid [beta /] fin');

        $this->assertCount(2, $found);
        $this->assertEquals('alpha', $found[0]['name']);
        $this->assertEquals(['foo' => 'bar'], $found[0]['attributes']);
        $this->assertEquals('x', $found[0]['content']);
        $this->assertEquals('beta', $found[1]['name']);
    }

    // ---------------------------------------------------------------------
    // Backtrack limit (smoke test: no crash)
    // ---------------------------------------------------------------------

    public function test_compile_restores_backtrack_limit_after_run(): void
    {
        $before = ini_get('pcre.backtrack_limit');

        $this->compiler->register('demo', fn () => 'D');
        $this->compiler->compile('[demo /]');

        $after = ini_get('pcre.backtrack_limit');

        $this->assertEquals($before, $after);
    }
}
