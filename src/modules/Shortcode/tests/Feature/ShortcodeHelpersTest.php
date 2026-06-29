<?php

namespace Modules\Shortcode\Tests\Feature;

use Tests\TestCase;

class ShortcodeHelpersTest extends TestCase
{
    public function test_shortcode_atts_merges_and_filters(): void
    {
        $result = shortcode_atts(
            ['class' => 'btn', 'url' => '#'],
            ['url' => '/foo', 'ignored' => 'x']
        );

        $this->assertEquals(['class' => 'btn', 'url' => '/foo'], $result);
    }

    public function test_do_shortcode_is_alias_of_shortcode(): void
    {
        $a = shortcode('[alert]ok[/alert]');
        $b = do_shortcode('[alert]ok[/alert]');

        $this->assertEquals($a, $b);
    }

    public function test_all_shortcodes_registered_returns_metadata(): void
    {
        $registered = all_shortcodes_registered();

        $this->assertIsArray($registered);
        $this->assertNotEmpty($registered);
        $this->assertArrayHasKey('name', $registered[0]);
        $this->assertArrayHasKey('description', $registered[0]);
        $this->assertArrayHasKey('example', $registered[0]);
        $this->assertArrayHasKey('attributes', $registered[0]);
    }
}
