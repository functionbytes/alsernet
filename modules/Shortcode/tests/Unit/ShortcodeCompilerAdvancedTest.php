<?php

namespace Modules\Shortcode\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Modules\Shortcode\Compiler\ShortcodeCompiler;
use Modules\Shortcode\Events\ShortcodeCompiled;
use Modules\Shortcode\Events\ShortcodeCompiling;
use Modules\Shortcode\Events\ShortcodeRegistered;
use Modules\Shortcode\Exceptions\ShortcodeCompilationException;
use Tests\TestCase;

class ShortcodeCompilerAdvancedTest extends TestCase
{
    protected ShortcodeCompiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->compiler = new ShortcodeCompiler;
    }

    // ---------------------------------------------------------------------
    // Nesting limit
    // ---------------------------------------------------------------------

    public function test_nesting_limit_prevents_runaway_recursion(): void
    {
        config(['shortcode.max_nesting_level' => 3]);

        // Genera una salida que siempre contiene otro shortcode (expansión infinita).
        $this->compiler->register('expand', function ($attrs, $content) {
            return '[expand]'.$content.'[/expand]';
        });

        $result = $this->compiler->compile('[expand]x[/expand]');

        // No debe lanzar, no debe colgar. Debe salir tras max_nesting_level pases.
        $this->assertStringContainsString('[expand]', $result);
    }

    public function test_compiler_completes_nested_shortcodes_within_pass_budget(): void
    {
        config(['shortcode.max_nesting_level' => 5]);

        $this->compiler->register('outer', fn ($a, $c) => "<OUTER>$c</OUTER>");
        $this->compiler->register('inner', fn ($a, $c) => "<INNER>$c</INNER>");

        $result = $this->compiler->compile('[outer][inner]hi[/inner][/outer]');

        $this->assertEquals('<OUTER><INNER>hi</INNER></OUTER>', $result);
    }

    // ---------------------------------------------------------------------
    // Error handling modes
    // ---------------------------------------------------------------------

    public function test_error_handling_silent_returns_raw(): void
    {
        config(['shortcode.error_handling' => 'silent']);

        $this->compiler->register('boom', function () {
            throw new \RuntimeException('fail');
        });

        $result = $this->compiler->compile('[boom]x[/boom]');

        $this->assertEquals('[boom]x[/boom]', $result);
    }

    public function test_error_handling_display_wraps_in_error_span(): void
    {
        config(['shortcode.error_handling' => 'display']);

        $this->compiler->register('boom', function () {
            throw new \RuntimeException('kaboom');
        });

        $result = $this->compiler->compile('[boom /]');

        $this->assertStringContainsString('shortcode-error', $result);
        $this->assertStringContainsString('kaboom', $result);
    }

    public function test_error_handling_throw_raises_exception(): void
    {
        config(['shortcode.error_handling' => 'throw']);

        $this->compiler->register('boom', function () {
            throw new \RuntimeException('fail');
        });

        $this->expectException(ShortcodeCompilationException::class);

        $this->compiler->compile('[boom /]');
    }

    // ---------------------------------------------------------------------
    // Whitelist
    // ---------------------------------------------------------------------

    public function test_whitelist_blocks_non_allowed_shortcodes(): void
    {
        $this->compiler->register('safe', fn () => 'SAFE');
        $this->compiler->register('danger', fn () => 'DANGER');

        $result = $this->compiler->compileWithContext(
            '[safe /] and [danger /]',
            allowed: ['safe']
        );

        $this->assertStringContainsString('SAFE', $result);
        $this->assertStringContainsString('[danger /]', $result);
        $this->assertStringNotContainsString('DANGER', $result);
    }

    // ---------------------------------------------------------------------
    // Events
    // ---------------------------------------------------------------------

    public function test_register_dispatches_registered_event(): void
    {
        Event::fake();

        $this->compiler->register('demo', fn () => '');

        Event::assertDispatched(
            ShortcodeRegistered::class,
            fn (ShortcodeRegistered $e) => $e->name === 'demo'
        );
    }

    public function test_compile_dispatches_compiling_and_compiled_events(): void
    {
        Event::fake();

        $this->compiler->register('demo', fn () => 'X');
        $this->compiler->compile('[demo /]');

        Event::assertDispatched(ShortcodeCompiling::class);
        Event::assertDispatched(
            ShortcodeCompiled::class,
            fn (ShortcodeCompiled $e) => $e->compiled === 'X' && $e->fromCache === false
        );
    }

    public function test_second_compile_hits_cache(): void
    {
        config(['shortcode.cache' => true, 'shortcode.cache_duration' => 60]);

        $this->compiler->register('demo', fn () => 'CACHED');
        $this->compiler->compile('[demo /]');

        Event::fake();

        $result = $this->compiler->compile('[demo /]');

        $this->assertEquals('CACHED', $result);
        Event::assertDispatched(
            ShortcodeCompiled::class,
            fn (ShortcodeCompiled $e) => $e->fromCache === true
        );
    }

    public function test_clear_cache_invalidates_previous_compilation(): void
    {
        config(['shortcode.cache' => true]);

        $this->compiler->register('demo', fn () => 'FIRST');
        $first = $this->compiler->compile('[demo /]');

        // Cambiamos el callback y limpiamos cache.
        $this->compiler->register('demo', fn () => 'SECOND');
        $this->compiler->clearCache();

        $second = $this->compiler->compile('[demo /]');

        $this->assertEquals('FIRST', $first);
        $this->assertEquals('SECOND', $second);
    }

    // ---------------------------------------------------------------------
    // Max content size
    // ---------------------------------------------------------------------

    public function test_content_exceeding_max_size_is_returned_as_is(): void
    {
        $huge = str_repeat('x', ShortcodeCompiler::MAX_CONTENT_SIZE + 10).'[demo /]';

        $this->compiler->register('demo', fn () => 'COMPILED');

        $result = $this->compiler->compile($huge);

        $this->assertEquals($huge, $result);
        $this->assertStringContainsString('[demo /]', $result);
    }

    // ---------------------------------------------------------------------
    // Atts helper
    // ---------------------------------------------------------------------

    public function test_atts_merges_defaults_and_drops_unknown_keys(): void
    {
        $result = $this->compiler->atts(
            ['class' => 'btn', 'url' => '#'],
            ['url' => '/foo', 'extra' => 'ignored']
        );

        $this->assertEquals(['class' => 'btn', 'url' => '/foo'], $result);
    }
}
