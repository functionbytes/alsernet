<?php

namespace Modules\Optimize\Tests\Unit;

use Modules\Optimize\Http\Middleware\InjectFontDisplay;
use PHPUnit\Framework\TestCase;

class InjectFontDisplayTest extends TestCase
{
    private InjectFontDisplay $mw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mw = new InjectFontDisplay;
    }

    public function test_adds_font_display_swap_to_face_without_it(): void
    {
        $in = '<style>@font-face{font-family:"Foo";src:url(foo.woff2);}</style>';
        $out = $this->mw->apply($in);

        $this->assertStringContainsString('font-display:swap', $out);
    }

    public function test_does_not_duplicate_font_display_when_already_present(): void
    {
        $in = '<style>@font-face{font-family:"Foo";font-display:block;src:url(foo.woff2);}</style>';
        $out = $this->mw->apply($in);

        $this->assertStringNotContainsString('font-display:swap', $out);
        $this->assertStringContainsString('font-display:block', $out);
    }

    public function test_appends_display_swap_to_google_fonts_url(): void
    {
        $in = '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400">';
        $out = $this->mw->apply($in);

        $this->assertStringContainsString('display=swap', $out);
    }

    public function test_does_not_modify_google_fonts_url_that_already_has_display(): void
    {
        $in = '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter&display=optional">';
        $out = $this->mw->apply($in);

        $this->assertStringContainsString('display=optional', $out);
        $this->assertStringNotContainsString('display=swap', $out);
    }
}
