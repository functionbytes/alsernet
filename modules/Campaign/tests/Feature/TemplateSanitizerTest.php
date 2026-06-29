<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Models\Template\Template;
use Modules\Campaign\Services\TemplateSanitizer;
use Tests\TestCase;

class TemplateSanitizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_purify_removes_script_tags(): void
    {
        $dirty = '<p>Hola</p><script>alert("xss")</script>';
        $clean = TemplateSanitizer::purify($dirty);

        $this->assertStringNotContainsString('<script>', $clean);
        $this->assertStringContainsString('<p>Hola</p>', $clean);
    }

    public function test_purify_removes_inline_event_handlers(): void
    {
        $dirty = '<p onclick="alert(1)">Hola</p>';
        $clean = TemplateSanitizer::purify($dirty);

        $this->assertStringNotContainsString('onclick', $clean);
    }

    public function test_validate_detects_http_links(): void
    {
        $html = '<a href="http://example.com">Link</a>';
        $result = TemplateSanitizer::validate($html);

        $this->assertFalse($result['ok']);
        $this->assertContains('Hay links con protocolo HTTP inseguro.', $result['issues']);
    }

    public function test_validate_detects_missing_image_alt(): void
    {
        $html = '<img src="https://example.com/img.png">';
        $result = TemplateSanitizer::validate($html);

        $this->assertFalse($result['ok']);
        $this->assertContains('Hay imágenes sin atributo alt.', $result['issues']);
    }

    public function test_template_is_sanitized_on_save(): void
    {
        $template = Template::create([
            'name' => 'Test',
            'content' => '<p>Safe</p><script>bad()</script>',
            'builder' => false,
        ]);

        $this->assertStringNotContainsString('<script>', $template->fresh()->content);
    }
}
