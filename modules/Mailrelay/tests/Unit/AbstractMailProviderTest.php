<?php

namespace Modules\Mailrelay\Tests\Unit;

use Modules\Mailrelay\Providers\Mail\AbstractMailProvider;
use Tests\TestCase;

class AbstractMailProviderTest extends TestCase
{
    private AbstractMailProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a concrete implementation for testing the abstract class.
        // Public helper methods are prefixed with "expose" to avoid Pint
        // converting them to snake_case (it treats "test*" as PHPUnit methods).
        $this->provider = new class(['from_email' => 'default@test.com', 'from_name' => 'Default Sender', 'reply_to' => 'reply@test.com', 'timeout' => 15]) extends AbstractMailProvider
        {
            public function send(string $to, string $subject, string $htmlContent, array $options = []): array
            {
                return $this->successResponse('test-msg-id');
            }

            public function sendBulk(array $recipients, string $subject, string $htmlContent, array $options = []): array
            {
                return $this->successResponse(null, ['sent_count' => count($recipients)]);
            }

            public function syncTemplate(string $templateName, string $htmlContent, string $subject): ?string
            {
                return 'template-id';
            }

            public function getStats(string $campaignId): array
            {
                return [];
            }

            // phpcs:ignore PSR1.Methods.CamelCapsMethodName -- required by interface
            public function test_connection(): bool
            {
                return true;
            }

            public function getName(): string
            {
                return 'TestProvider';
            }

            public function getRateLimits(): array
            {
                return ['emails_per_hour' => 1000, 'emails_per_day' => 10000];
            }

            // Expose protected methods for testing
            public function exposeIsValidEmail(string $email): bool
            {
                return $this->isValidEmail($email);
            }

            public function exposeHtmlToPlainText(string $html): string
            {
                return $this->htmlToPlainText($html);
            }

            public function exposePrepareOptions(array $options): array
            {
                return $this->prepareOptions($options);
            }

            public function exposeErrorResponse(string $message, ?\Throwable $exception = null): array
            {
                return $this->errorResponse($message, $exception);
            }

            public function exposeSuccessResponse(?string $messageId = null, array $extra = []): array
            {
                return $this->successResponse($messageId, $extra);
            }

            public function exposeGetConfig(string $key, $default = null)
            {
                return $this->getConfig($key, $default);
            }
        };
    }

    // -------------------------------------------------------
    // isValidEmail
    // -------------------------------------------------------

    /** @test */
    public function is_valid_email_accepts_standard_email(): void
    {
        $this->assertTrue($this->provider->exposeIsValidEmail('user@example.com'));
    }

    /** @test */
    public function is_valid_email_accepts_email_with_subdomain(): void
    {
        $this->assertTrue($this->provider->exposeIsValidEmail('user@sub.domain.com'));
    }

    /** @test */
    public function is_valid_email_accepts_email_with_plus(): void
    {
        $this->assertTrue($this->provider->exposeIsValidEmail('user+tag@example.com'));
    }

    /** @test */
    public function is_valid_email_accepts_email_with_dots(): void
    {
        $this->assertTrue($this->provider->exposeIsValidEmail('first.last@example.com'));
    }

    /** @test */
    public function is_valid_email_rejects_empty_string(): void
    {
        $this->assertFalse($this->provider->exposeIsValidEmail(''));
    }

    /** @test */
    public function is_valid_email_rejects_missing_at_sign(): void
    {
        $this->assertFalse($this->provider->exposeIsValidEmail('userexample.com'));
    }

    /** @test */
    public function is_valid_email_rejects_missing_domain(): void
    {
        $this->assertFalse($this->provider->exposeIsValidEmail('user@'));
    }

    /** @test */
    public function is_valid_email_rejects_missing_local_part(): void
    {
        $this->assertFalse($this->provider->exposeIsValidEmail('@example.com'));
    }

    /** @test */
    public function is_valid_email_rejects_spaces(): void
    {
        $this->assertFalse($this->provider->exposeIsValidEmail('user @example.com'));
    }

    /** @test */
    public function is_valid_email_rejects_plain_text(): void
    {
        $this->assertFalse($this->provider->exposeIsValidEmail('not-an-email'));
    }

    // -------------------------------------------------------
    // htmlToPlainText
    // -------------------------------------------------------

    /** @test */
    public function html_to_plain_text_strips_basic_tags(): void
    {
        $html = '<h1>Title</h1><p>Paragraph text</p>';
        $result = $this->provider->exposeHtmlToPlainText($html);

        $this->assertStringContainsString('Title', $result);
        $this->assertStringContainsString('Paragraph text', $result);
        $this->assertStringNotContainsString('<h1>', $result);
        $this->assertStringNotContainsString('<p>', $result);
    }

    /** @test */
    public function html_to_plain_text_removes_script_tags(): void
    {
        $html = '<p>Before</p><script>alert("xss")</script><p>After</p>';
        $result = $this->provider->exposeHtmlToPlainText($html);

        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('Before', $result);
        $this->assertStringContainsString('After', $result);
    }

    /** @test */
    public function html_to_plain_text_removes_style_tags(): void
    {
        $html = '<style>.red { color: red; }</style><p>Visible text</p>';
        $result = $this->provider->exposeHtmlToPlainText($html);

        $this->assertStringNotContainsString('color: red', $result);
        $this->assertStringContainsString('Visible text', $result);
    }

    /** @test */
    public function html_to_plain_text_decodes_html_entities(): void
    {
        $html = '<p>Price: &amp; taxes &lt;included&gt;</p>';
        $result = $this->provider->exposeHtmlToPlainText($html);

        $this->assertStringContainsString('Price: & taxes <included>', $result);
    }

    /** @test */
    public function html_to_plain_text_handles_empty_string(): void
    {
        $result = $this->provider->exposeHtmlToPlainText('');

        $this->assertEquals('', $result);
    }

    /** @test */
    public function html_to_plain_text_handles_plain_text_input(): void
    {
        $result = $this->provider->exposeHtmlToPlainText('Just plain text');

        $this->assertEquals('Just plain text', $result);
    }

    // -------------------------------------------------------
    // prepareOptions
    // -------------------------------------------------------

    /** @test */
    public function prepare_options_uses_config_defaults_when_no_overrides(): void
    {
        $result = $this->provider->exposePrepareOptions([]);

        $this->assertEquals('default@test.com', $result['from_email']);
        $this->assertEquals('Default Sender', $result['from_name']);
        $this->assertEquals('reply@test.com', $result['reply_to']);
        $this->assertTrue($result['track_opens']);
        $this->assertTrue($result['track_clicks']);
    }

    /** @test */
    public function prepare_options_overrides_defaults_with_provided_values(): void
    {
        $result = $this->provider->exposePrepareOptions([
            'from_email' => 'custom@test.com',
            'from_name' => 'Custom Sender',
            'reply_to' => 'custom-reply@test.com',
            'track_opens' => false,
            'track_clicks' => false,
        ]);

        $this->assertEquals('custom@test.com', $result['from_email']);
        $this->assertEquals('Custom Sender', $result['from_name']);
        $this->assertEquals('custom-reply@test.com', $result['reply_to']);
        $this->assertFalse($result['track_opens']);
        $this->assertFalse($result['track_clicks']);
    }

    /** @test */
    public function prepare_options_partially_overrides(): void
    {
        $result = $this->provider->exposePrepareOptions([
            'from_email' => 'override@test.com',
        ]);

        $this->assertEquals('override@test.com', $result['from_email']);
        $this->assertEquals('Default Sender', $result['from_name']);
        $this->assertEquals('reply@test.com', $result['reply_to']);
    }

    // -------------------------------------------------------
    // errorResponse
    // -------------------------------------------------------

    /** @test */
    public function error_response_returns_correct_structure(): void
    {
        $result = $this->provider->exposeErrorResponse('Something went wrong');

        $this->assertFalse($result['success']);
        $this->assertNull($result['message_id']);
        $this->assertEquals('Something went wrong', $result['error']);
        $this->assertArrayNotHasKey('exception', $result);
    }

    /** @test */
    public function error_response_includes_exception_details_when_provided(): void
    {
        $exception = new \RuntimeException('Connection refused');
        $result = $this->provider->exposeErrorResponse('Send failed', $exception);

        $this->assertFalse($result['success']);
        $this->assertEquals('Send failed', $result['error']);
        $this->assertEquals('RuntimeException', $result['exception']);
        $this->assertEquals('Connection refused', $result['exception_message']);
    }

    // -------------------------------------------------------
    // successResponse
    // -------------------------------------------------------

    /** @test */
    public function success_response_returns_correct_structure(): void
    {
        $result = $this->provider->exposeSuccessResponse('msg-12345');

        $this->assertTrue($result['success']);
        $this->assertEquals('msg-12345', $result['message_id']);
        $this->assertNull($result['error']);
    }

    /** @test */
    public function success_response_with_null_message_id(): void
    {
        $result = $this->provider->exposeSuccessResponse();

        $this->assertTrue($result['success']);
        $this->assertNull($result['message_id']);
        $this->assertNull($result['error']);
    }

    /** @test */
    public function success_response_merges_extra_data(): void
    {
        $result = $this->provider->exposeSuccessResponse('msg-001', [
            'campaign_id' => 'camp-001',
            'sent_count' => 150,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('msg-001', $result['message_id']);
        $this->assertEquals('camp-001', $result['campaign_id']);
        $this->assertEquals(150, $result['sent_count']);
    }

    // -------------------------------------------------------
    // getConfig
    // -------------------------------------------------------

    /** @test */
    public function get_config_returns_existing_value(): void
    {
        $this->assertEquals('default@test.com', $this->provider->exposeGetConfig('from_email'));
        $this->assertEquals(15, $this->provider->exposeGetConfig('timeout'));
    }

    /** @test */
    public function get_config_returns_default_for_missing_key(): void
    {
        $this->assertNull($this->provider->exposeGetConfig('nonexistent'));
        $this->assertEquals('fallback', $this->provider->exposeGetConfig('nonexistent', 'fallback'));
    }
}
