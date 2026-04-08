<?php

namespace Modules\Mailrelay\Tests\Unit;

use Mockery;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Entities\MailProvider;
use Modules\Mailrelay\Exceptions\CampaignRenderException;
use Modules\Mailrelay\Services\CampaignRendererService;
use Tests\TestCase;

class CampaignRendererServiceTest extends TestCase
{
    private CampaignRendererService $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new CampaignRendererService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function render_returns_html_content_when_campaign_has_inline_html(): void
    {
        $campaign = $this->makeCampaign([
            'html_content' => '<h1>Hello World</h1><p>This is a test.</p>',
            'mailer_template_id' => null,
        ]);

        $result = $this->renderer->render($campaign);

        $this->assertEquals('<h1>Hello World</h1><p>This is a test.</p>', $result);
    }

    /** @test */
    public function render_replaces_variables_in_html_content(): void
    {
        $campaign = $this->makeCampaign([
            'html_content' => '<p>Hello {NAME}, welcome to {COMPANY}!</p>',
            'mailer_template_id' => null,
        ]);

        $result = $this->renderer->render($campaign, [
            'NAME' => 'John',
            'COMPANY' => 'Acme Corp',
        ]);

        $this->assertEquals('<p>Hello John, welcome to Acme Corp!</p>', $result);
    }

    /** @test */
    public function render_handles_variables_with_curly_braces_already(): void
    {
        $campaign = $this->makeCampaign([
            'html_content' => '<p>Hello {NAME}!</p>',
            'mailer_template_id' => null,
        ]);

        $result = $this->renderer->render($campaign, [
            '{NAME}' => 'Jane',
        ]);

        $this->assertEquals('<p>Hello Jane!</p>', $result);
    }

    /** @test */
    public function render_ignores_array_and_object_variables(): void
    {
        $campaign = $this->makeCampaign([
            'html_content' => '<p>Hello {NAME}! {METADATA}</p>',
            'mailer_template_id' => null,
        ]);

        $result = $this->renderer->render($campaign, [
            'NAME' => 'John',
            'METADATA' => ['key' => 'value'], // Should be ignored
        ]);

        $this->assertEquals('<p>Hello John! {METADATA}</p>', $result);
    }

    /** @test */
    public function render_throws_when_no_html_content_and_no_template(): void
    {
        $campaign = $this->makeCampaign([
            'id' => 42,
            'html_content' => null,
            'mailer_template_id' => null,
        ]);

        $this->expectException(CampaignRenderException::class);
        $this->expectExceptionMessage('Campaign #42 has no HTML content');

        $this->renderer->render($campaign);
    }

    /** @test */
    public function render_throws_when_html_content_is_empty_and_no_template(): void
    {
        $campaign = $this->makeCampaign([
            'id' => 99,
            'html_content' => '',
            'mailer_template_id' => null,
        ]);

        $this->expectException(CampaignRenderException::class);
        $this->expectExceptionMessage('Campaign #99 has no HTML content');

        $this->renderer->render($campaign);
    }

    /** @test */
    public function validate_returns_error_when_subject_is_missing(): void
    {
        $campaign = $this->makeCampaign([
            'subject' => null,
            'html_content' => '<p>Content</p>',
            'mail_provider_id' => 1,
        ]);

        $result = $this->renderer->validate($campaign);

        $this->assertFalse($result['valid']);
        $this->assertContains('El campaign debe tener un asunto', $result['errors']);
    }

    /** @test */
    public function validate_returns_error_when_subject_is_empty(): void
    {
        $campaign = $this->makeCampaign([
            'subject' => '',
            'html_content' => '<p>Content</p>',
            'mail_provider_id' => 1,
        ]);

        $result = $this->renderer->validate($campaign);

        $this->assertFalse($result['valid']);
        $this->assertContains('El campaign debe tener un asunto', $result['errors']);
    }

    /** @test */
    public function validate_returns_error_when_no_content_and_no_template(): void
    {
        $campaign = $this->makeCampaign([
            'subject' => 'Test Subject',
            'html_content' => null,
            'mailer_template_id' => null,
            'mail_provider_id' => 1,
        ]);

        $result = $this->renderer->validate($campaign);

        $this->assertFalse($result['valid']);
        $this->assertContains(
            'El campaign debe tener contenido HTML o un template asignado',
            $result['errors']
        );
    }

    /** @test */
    public function validate_returns_error_when_provider_is_missing(): void
    {
        $campaign = $this->makeCampaign([
            'subject' => 'Test',
            'html_content' => '<p>Content</p>',
            'mail_provider_id' => null,
        ]);

        $result = $this->renderer->validate($campaign);

        $this->assertFalse($result['valid']);
        $this->assertContains(
            'El campaign debe tener un provider de email asignado',
            $result['errors']
        );
    }

    /** @test */
    public function validate_returns_valid_when_all_fields_present(): void
    {
        $campaign = $this->makeCampaign([
            'subject' => 'Valid Subject',
            'html_content' => '<p>Valid content</p>',
            'mailer_template_id' => null,
            'mail_provider_id' => 1,
        ]);

        $result = $this->renderer->validate($campaign);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function validate_returns_multiple_errors_at_once(): void
    {
        $campaign = $this->makeCampaign([
            'subject' => '',
            'html_content' => null,
            'mailer_template_id' => null,
            'mail_provider_id' => null,
        ]);

        $result = $this->renderer->validate($campaign);

        $this->assertFalse($result['valid']);
        $this->assertCount(3, $result['errors']);
    }

    /** @test */
    public function validate_accepts_campaign_with_template_id_instead_of_html(): void
    {
        // Template validation requires DB lookup, which will return null in unit test.
        // This tests the flow where mailer_template_id is set but template not found.
        $campaign = $this->makeCampaign([
            'subject' => 'With Template',
            'html_content' => null,
            'mailer_template_id' => 999,
            'mail_provider_id' => 1,
        ]);

        $result = $this->renderer->validate($campaign);

        // Template won't be found in unit test context, so expect template error
        $this->assertFalse($result['valid']);
        $this->assertContains('Template ID 999 no encontrado', $result['errors']);
    }

    /** @test */
    public function render_with_empty_variables_returns_unchanged_html(): void
    {
        $html = '<h1>No variables here</h1>';
        $campaign = $this->makeCampaign([
            'html_content' => $html,
            'mailer_template_id' => null,
        ]);

        $result = $this->renderer->render($campaign, []);

        $this->assertEquals($html, $result);
    }

    /** @test */
    public function render_replaces_multiple_occurrences_of_same_variable(): void
    {
        $campaign = $this->makeCampaign([
            'html_content' => '<p>{NAME} says hi. Love, {NAME}</p>',
            'mailer_template_id' => null,
        ]);

        $result = $this->renderer->render($campaign, ['NAME' => 'Alice']);

        $this->assertEquals('<p>Alice says hi. Love, Alice</p>', $result);
    }

    /**
     * Create a Campaign mock/stub with given attributes.
     */
    private function makeCampaign(array $attributes = []): Campaign
    {
        $campaign = new Campaign;

        // Set default values
        $defaults = [
            'id' => $attributes['id'] ?? 1,
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'html_content' => '<p>Test content</p>',
            'mailer_template_id' => null,
            'lang_id' => 1,
            'mail_provider_id' => 1,
            'template_variables' => [],
            'track_opens' => true,
            'track_clicks' => true,
            'metadata' => [],
        ];

        $merged = array_merge($defaults, $attributes);

        foreach ($merged as $key => $value) {
            $campaign->$key = $value;
        }

        // Mock the mailProvider relationship for getCampaignVariables
        if (! isset($attributes['_skip_provider_relation'])) {
            $mockProvider = new MailProvider;
            $mockProvider->credentials = [
                'from_email' => 'sender@test.com',
                'from_name' => 'Test Sender',
            ];
            $campaign->setRelation('mailProvider', $mockProvider);
        }

        return $campaign;
    }
}
