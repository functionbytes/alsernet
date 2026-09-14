<?php

namespace Modules\Supplier\Tests\Unit\Helpers;

use Modules\Supplier\Helpers\HtmlSanitizer;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_removes_script_tags(): void
    {
        $input = '<p>Hello</p><script>alert(1)</script>';
        $output = HtmlSanitizer::clean($input);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('</script>', $output);
        $this->assertStringContainsString('<p>Hello</p>', $output);
    }

    public function test_removes_onerror_attribute_from_img(): void
    {
        $input = '<img src="x.jpg" onerror="alert(1)">';
        $output = HtmlSanitizer::clean($input);

        $this->assertStringNotContainsString('onerror', $output);
        $this->assertStringContainsString('<img', $output);
    }

    public function test_removes_onclick_attribute(): void
    {
        $input = '<p onclick="evil()">text</p>';
        $output = HtmlSanitizer::clean($input);

        $this->assertStringNotContainsString('onclick', $output);
        $this->assertStringContainsString('<p', $output);
    }

    public function test_neutralises_javascript_href(): void
    {
        $input = '<a href="javascript:alert(1)">click</a>';
        $output = HtmlSanitizer::clean($input);

        $this->assertStringNotContainsString('javascript:', $output);
        $this->assertStringContainsString('href="#"', $output);
    }

    public function test_neutralises_data_uri_href(): void
    {
        $input = '<a href="data:text/html,<script>alert(1)</script>">click</a>';
        $output = HtmlSanitizer::clean($input);

        $this->assertStringNotContainsString('data:', $output);
        $this->assertStringContainsString('href="#"', $output);
    }

    public function test_preserves_allowed_tags(): void
    {
        $input = '<p>text</p><strong>bold</strong><em>italic</em><ul><li>item</li></ul>';
        $output = HtmlSanitizer::clean($input);

        $this->assertStringContainsString('<p>text</p>', $output);
        $this->assertStringContainsString('<strong>bold</strong>', $output);
        $this->assertStringContainsString('<em>italic</em>', $output);
        $this->assertStringContainsString('<ul>', $output);
        $this->assertStringContainsString('<li>item</li>', $output);
    }

    public function test_returns_empty_string_for_null_input(): void
    {
        $this->assertSame('', HtmlSanitizer::clean(null));
    }

    public function test_returns_empty_string_for_empty_string(): void
    {
        $this->assertSame('', HtmlSanitizer::clean(''));
    }

    public function test_strips_disallowed_tags_but_keeps_text(): void
    {
        $input = '<style>body{color:red}</style><p>visible</p>';
        $output = HtmlSanitizer::clean($input);

        $this->assertStringNotContainsString('<style>', $output);
        $this->assertStringContainsString('visible', $output);
    }

    public function test_removes_onload_attribute(): void
    {
        $input = '<body onload="evil()"><p>text</p></body>';
        $output = HtmlSanitizer::clean($input);

        $this->assertStringNotContainsString('onload', $output);
    }

    public function test_neutralises_vbscript_href(): void
    {
        $input = '<a href="vbscript:msgbox()">click</a>';
        $output = HtmlSanitizer::clean($input);

        $this->assertStringNotContainsString('vbscript:', $output);
    }

    public function test_plain_text_passes_through_unchanged(): void
    {
        $input = 'Plain text without any HTML.';
        $output = HtmlSanitizer::clean($input);

        $this->assertSame($input, $output);
    }

    public function test_custom_allowed_tags_restricts_output(): void
    {
        $input = '<p>text</p><strong>bold</strong><script>bad</script>';
        $output = HtmlSanitizer::clean($input, '<p>');

        $this->assertStringContainsString('<p>text</p>', $output);
        $this->assertStringNotContainsString('<strong>', $output);
        $this->assertStringNotContainsString('<script>', $output);
    }

    public function test_removes_iframe(): void
    {
        $input = '<p>before</p><iframe src="https://evil.example"></iframe><p>after</p>';
        $output = HtmlSanitizer::clean($input);

        $this->assertStringNotContainsString('<iframe', $output);
        $this->assertStringContainsString('before', $output);
        $this->assertStringContainsString('after', $output);
    }

    public function test_removes_svg_and_its_script(): void
    {
        $input = '<p>safe</p><svg><script>alert(1)</script></svg>';
        $output = HtmlSanitizer::clean($input);

        $this->assertStringNotContainsString('<svg', $output);
        $this->assertStringNotContainsString('alert(1)', $output);
        $this->assertStringContainsString('safe', $output);
    }

    public function test_removes_style_attribute(): void
    {
        $input = '<p style="position:fixed;top:0">text</p>';
        $output = HtmlSanitizer::clean($input);

        $this->assertStringNotContainsString('style=', $output);
        $this->assertStringContainsString('text', $output);
    }

    public function test_neutralises_unquoted_javascript_href(): void
    {
        $input = '<a href=javascript:alert(1)>click</a>';
        $output = HtmlSanitizer::clean($input);

        $this->assertStringNotContainsString('javascript:', $output);
        $this->assertStringNotContainsString('alert', $output);
    }

    public function test_removes_data_uri_from_img_src(): void
    {
        $input = '<img src="data:text/html;base64,PHNjcmlwdD4=" alt="x">';
        $output = HtmlSanitizer::clean($input);

        $this->assertStringNotContainsString('data:', $output);
    }

    public function test_keeps_safe_external_link(): void
    {
        $input = '<a href="https://example.com/page">link</a>';
        $output = HtmlSanitizer::clean($input);

        $this->assertStringContainsString('href="https://example.com/page"', $output);
        $this->assertStringContainsString('link', $output);
    }

    public function test_neutralises_php_tags(): void
    {
        $input = '<p>before</p><?php echo "x"; ?><p>after</p>';
        $output = HtmlSanitizer::clean($input);

        // The literal "<?php" opening tag must not survive (it is escaped to text).
        $this->assertStringNotContainsString('<?php', $output);
        $this->assertStringContainsString('<p>before</p>', $output);
        $this->assertStringContainsString('<p>after</p>', $output);
    }
}
