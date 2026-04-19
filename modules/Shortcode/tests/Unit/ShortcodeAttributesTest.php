<?php

namespace Modules\Shortcode\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Modules\Shortcode\Compiler\ShortcodeCompiler;
use Tests\TestCase;

class ShortcodeAttributesTest extends TestCase
{
    protected ShortcodeCompiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->compiler = new ShortcodeCompiler;
    }

    public function test_unquoted_attribute_values_are_parsed(): void
    {
        $this->compiler->register('t', fn ($a) => $a['class'].'|'.$a['url']);

        $result = $this->compiler->compile('[t class=primary url=/foo /]');

        $this->assertEquals('primary|/foo', $result);
    }

    public function test_boolean_attributes_become_true_string(): void
    {
        $this->compiler->register('t', fn ($a) => ($a['disabled'] ?? 'no').'|'.($a['active'] ?? 'no'));

        $result = $this->compiler->compile('[t disabled active /]');

        $this->assertEquals('true|true', $result);
    }

    public function test_mixed_quoted_and_unquoted_attributes_coexist(): void
    {
        $this->compiler->register('t', fn ($a) => implode(',', [
            $a['a'] ?? '',
            $a['b'] ?? '',
            $a['c'] ?? '',
        ]));

        $result = $this->compiler->compile('[t a="one" b=\'two\' c=three /]');

        $this->assertEquals('one,two,three', $result);
    }

    public function test_positional_attributes_get_numeric_keys(): void
    {
        $this->compiler->register('t', fn ($a) => ($a[0] ?? '').'|'.($a[1] ?? ''));

        $result = $this->compiler->compile('[t "first" "second" /]');

        $this->assertEquals('first|second', $result);
    }

    public function test_mixed_positional_and_named(): void
    {
        $this->compiler->register('t', fn ($a) => ($a['url'] ?? '').'|'.($a[0] ?? ''));

        $result = $this->compiler->compile('[t url="/foo" "extra" /]');

        $this->assertEquals('/foo|extra', $result);
    }

    public function test_hyphenated_names_still_work_with_new_parser(): void
    {
        $this->compiler->register('t', fn ($a) => $a['data-id'].'|'.$a['aria-label']);

        $result = $this->compiler->compile('[t data-id="42" aria-label="Cerrar" /]');

        $this->assertEquals('42|Cerrar', $result);
    }
}
