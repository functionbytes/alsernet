<?php

namespace Modules\Mailrelay\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Entities\MailProvider;
use Modules\Mailrelay\Enums\CampaignStatus;
use Modules\Mailrelay\Services\CampaignRendererService;
use Modules\Mailrelay\Services\CampaignService;
use Modules\Mailrelay\Services\ProviderManager;
use Tests\TestCase;

class CampaignServiceTest extends TestCase
{
    use RefreshDatabase;

    private CampaignService $service;

    private ProviderManager $providerManager;

    private CampaignRendererService $rendererService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->providerManager = Mockery::mock(ProviderManager::class);
        $this->rendererService = Mockery::mock(CampaignRendererService::class);

        $this->service = new CampaignService(
            $this->providerManager,
            $this->rendererService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------
    // create()
    // -------------------------------------------------------

    /** @test */
    public function create_persists_campaign_with_draft_status(): void
    {
        $provider = $this->createProvider();

        $campaign = $this->service->create([
            'name' => 'Spring Newsletter',
            'subject' => 'Spring is here!',
            'html_content' => '<h1>Spring Newsletter</h1>',
            'text_content' => 'Spring Newsletter',
            'mail_provider_id' => $provider->id,
        ]);

        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertTrue($campaign->exists);
        $this->assertEquals('Spring Newsletter', $campaign->name);
        $this->assertEquals('Spring is here!', $campaign->subject);
        $this->assertEquals(CampaignStatus::DRAFT, $campaign->status);
        $this->assertEquals($provider->id, $campaign->mail_provider_id);
    }

    /** @test */
    public function create_defaults_to_tracking_enabled(): void
    {
        $provider = $this->createProvider();

        $campaign = $this->service->create([
            'name' => 'Tracked Campaign',
            'subject' => 'Test',
            'html_content' => '<p>Content</p>',
            'mail_provider_id' => $provider->id,
        ]);

        $this->assertTrue($campaign->track_opens);
        $this->assertTrue($campaign->track_clicks);
    }

    /** @test */
    public function create_accepts_optional_template_id_and_lang(): void
    {
        $provider = $this->createProvider();

        $campaign = $this->service->create([
            'name' => 'Template Campaign',
            'subject' => 'With Template',
            'mailer_template_id' => 42,
            'lang_id' => 2,
            'mail_provider_id' => $provider->id,
        ]);

        $this->assertEquals(42, $campaign->mailer_template_id);
        $this->assertEquals(2, $campaign->lang_id);
    }

    /** @test */
    public function create_stores_metadata_and_template_variables(): void
    {
        $provider = $this->createProvider();

        $campaign = $this->service->create([
            'name' => 'Meta Campaign',
            'subject' => 'Test',
            'html_content' => '<p>Hi</p>',
            'mail_provider_id' => $provider->id,
            'template_variables' => ['company' => 'Acme'],
            'metadata' => ['source' => 'api'],
        ]);

        $this->assertEquals(['company' => 'Acme'], $campaign->template_variables);
        $this->assertEquals(['source' => 'api'], $campaign->metadata);
    }

    // -------------------------------------------------------
    // update()
    // -------------------------------------------------------

    /** @test */
    public function update_modifies_draft_campaign(): void
    {
        $campaign = $this->createCampaign(['status' => CampaignStatus::DRAFT]);

        $updated = $this->service->update($campaign, [
            'name' => 'Updated Name',
            'subject' => 'Updated Subject',
        ]);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals('Updated Subject', $updated->subject);
    }

    /** @test */
    public function update_allows_editing_failed_campaign(): void
    {
        $campaign = $this->createCampaign(['status' => CampaignStatus::FAILED]);

        $updated = $this->service->update($campaign, [
            'name' => 'Retry Campaign',
        ]);

        $this->assertEquals('Retry Campaign', $updated->name);
    }

    /** @test */
    public function update_throws_for_sent_campaign(): void
    {
        $campaign = $this->createCampaign(['status' => CampaignStatus::SENT]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Solo se pueden editar campaigns en estado DRAFT o FAILED');

        $this->service->update($campaign, ['name' => 'Cannot Update']);
    }

    /** @test */
    public function update_throws_for_sending_campaign(): void
    {
        $campaign = $this->createCampaign(['status' => CampaignStatus::SENDING]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Solo se pueden editar campaigns en estado DRAFT o FAILED');

        $this->service->update($campaign, ['name' => 'Cannot Update']);
    }

    /** @test */
    public function update_throws_for_scheduled_campaign(): void
    {
        $campaign = $this->createCampaign(['status' => CampaignStatus::SCHEDULED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Solo se pueden editar campaigns en estado DRAFT o FAILED');

        $this->service->update($campaign, ['name' => 'Cannot Update']);
    }

    /** @test */
    public function update_merges_metadata(): void
    {
        $campaign = $this->createCampaign([
            'status' => CampaignStatus::DRAFT,
            'metadata' => ['original_key' => 'original_value'],
        ]);

        $updated = $this->service->update($campaign, [
            'metadata' => ['new_key' => 'new_value'],
        ]);

        $this->assertEquals('original_value', $updated->metadata['original_key']);
        $this->assertEquals('new_value', $updated->metadata['new_key']);
    }

    // -------------------------------------------------------
    // schedule()
    // -------------------------------------------------------

    /** @test */
    public function schedule_sets_scheduled_at_and_status(): void
    {
        $campaign = $this->createCampaign([
            'status' => CampaignStatus::DRAFT,
            'subject' => 'Scheduled Campaign',
            'html_content' => '<p>Content</p>',
        ]);

        $this->rendererService->shouldReceive('validate')
            ->once()
            ->with(Mockery::type(Campaign::class))
            ->andReturn(['valid' => true, 'errors' => []]);

        $scheduledAt = now()->addDays(3);
        $result = $this->service->schedule($campaign, $scheduledAt);

        $this->assertEquals(CampaignStatus::SCHEDULED, $result->status);
        $this->assertEquals(
            $scheduledAt->format('Y-m-d H:i:s'),
            $result->scheduled_at->format('Y-m-d H:i:s')
        );
    }

    /** @test */
    public function schedule_throws_when_validation_fails(): void
    {
        $campaign = $this->createCampaign([
            'status' => CampaignStatus::DRAFT,
            'subject' => '',
        ]);

        $this->rendererService->shouldReceive('validate')
            ->once()
            ->andReturn([
                'valid' => false,
                'errors' => ['El campaign debe tener un asunto'],
            ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Campaign no válida');

        $this->service->schedule($campaign, now()->addDay());
    }

    // -------------------------------------------------------
    // duplicate()
    // -------------------------------------------------------

    /** @test */
    public function duplicate_creates_copy_with_draft_status(): void
    {
        $original = $this->createCampaign([
            'status' => CampaignStatus::SENT,
            'name' => 'Original Campaign',
            'subject' => 'Original Subject',
            'html_content' => '<p>Original</p>',
            'sent_at' => now(),
            'provider_campaign_id' => 'ext-123',
            'recipient_count' => 500,
        ]);

        $copy = $this->service->duplicate($original);

        $this->assertNotEquals($original->id, $copy->id);
        $this->assertEquals('Original Campaign (Copia)', $copy->name);
        $this->assertEquals('Original Subject', $copy->subject);
        $this->assertEquals('<p>Original</p>', $copy->html_content);
        $this->assertEquals(CampaignStatus::DRAFT, $copy->status);
        $this->assertNull($copy->sent_at);
        $this->assertNull($copy->scheduled_at);
        $this->assertNull($copy->provider_campaign_id);
        $this->assertEquals(0, $copy->recipient_count);
    }

    /** @test */
    public function duplicate_accepts_custom_name(): void
    {
        $original = $this->createCampaign(['name' => 'Original']);

        $copy = $this->service->duplicate($original, 'Custom Copy Name');

        $this->assertEquals('Custom Copy Name', $copy->name);
    }

    /** @test */
    public function duplicate_persists_the_copy(): void
    {
        $original = $this->createCampaign();

        $copy = $this->service->duplicate($original);

        $this->assertTrue($copy->exists);
        $this->assertDatabaseHas('mails_campaigns', [
            'id' => $copy->id,
            'status' => CampaignStatus::DRAFT->value,
        ]);
    }

    // -------------------------------------------------------
    // delete()
    // -------------------------------------------------------

    /** @test */
    public function delete_removes_draft_campaign(): void
    {
        $campaign = $this->createCampaign(['status' => CampaignStatus::DRAFT]);

        $result = $this->service->delete($campaign);

        $this->assertTrue($result);
        // Campaign uses SoftDeletes, so check soft delete
        $this->assertSoftDeleted('mails_campaigns', ['id' => $campaign->id]);
    }

    /** @test */
    public function delete_removes_failed_campaign(): void
    {
        $campaign = $this->createCampaign(['status' => CampaignStatus::FAILED]);

        $result = $this->service->delete($campaign);

        $this->assertTrue($result);
        $this->assertSoftDeleted('mails_campaigns', ['id' => $campaign->id]);
    }

    /** @test */
    public function delete_throws_for_sent_campaign(): void
    {
        $campaign = $this->createCampaign(['status' => CampaignStatus::SENT]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Solo se pueden eliminar campaigns en estado DRAFT o FAILED');

        $this->service->delete($campaign);
    }

    /** @test */
    public function delete_throws_for_sending_campaign(): void
    {
        $campaign = $this->createCampaign(['status' => CampaignStatus::SENDING]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Solo se pueden eliminar campaigns en estado DRAFT o FAILED');

        $this->service->delete($campaign);
    }

    /** @test */
    public function delete_throws_for_scheduled_campaign(): void
    {
        $campaign = $this->createCampaign(['status' => CampaignStatus::SCHEDULED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Solo se pueden eliminar campaigns en estado DRAFT o FAILED');

        $this->service->delete($campaign);
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /**
     * Create a MailProvider in the database.
     */
    private function createProvider(array $overrides = []): MailProvider
    {
        return MailProvider::create(array_merge([
            'name' => 'Test Provider',
            'driver' => 'mailtrap',
            'credentials' => [
                'api_token' => 'test-token',
                'from_email' => 'test@example.com',
                'from_name' => 'Test',
            ],
            'is_active' => true,
            'is_default' => true,
            'priority' => 100,
            'connection_ok' => true,
        ], $overrides));
    }

    /**
     * Create a Campaign in the database.
     */
    private function createCampaign(array $overrides = []): Campaign
    {
        $provider = $this->createProvider();

        return Campaign::create(array_merge([
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'html_content' => '<p>Test content</p>',
            'status' => CampaignStatus::DRAFT,
            'mail_provider_id' => $provider->id,
            'template_variables' => [],
            'metadata' => [],
            'track_opens' => true,
            'track_clicks' => true,
            'recipient_count' => 0,
        ], $overrides));
    }
}
