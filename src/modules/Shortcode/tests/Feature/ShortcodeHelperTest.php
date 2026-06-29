<?php

namespace Modules\Shortcode\Tests\Feature;

use Tests\TestCase;

class ShortcodeHelperTest extends TestCase
{
    /** @test */
    public function shortcode_helper_compiles_content()
    {
        register_shortcode('test', function ($attrs, $content) {
            return 'HELPER_TEST';
        });

        $result = shortcode('[test]content[/test]');

        $this->assertEquals('HELPER_TEST', $result);
    }

    /** @test */
    public function strip_shortcodes_helper_removes_shortcodes()
    {
        $content = 'Before [test]content[/test] After';
        $result = strip_shortcodes($content);

        $this->assertEquals('Before  After', $result);
    }

    /** @test */
    public function has_shortcode_helper_checks_existence()
    {
        register_shortcode('exists', function () {});

        $this->assertTrue(has_shortcode('exists'));
        $this->assertFalse(has_shortcode('not_exists'));
    }

    /** @test */
    public function all_shortcodes_helper_returns_array()
    {
        $shortcodes = all_shortcodes();

        $this->assertIsArray($shortcodes);
    }

    /** @test */
    public function register_shortcode_helper_registers_shortcode()
    {
        register_shortcode('new_test', function ($attrs, $content) {
            return 'NEW_TEST';
        });

        $this->assertTrue(has_shortcode('new_test'));
    }
}
