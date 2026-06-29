<?php

namespace Modules\Mailer\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Mailer\Enums\EndpointLogStatus;
use Modules\Mailer\Jobs\SendEndpointEmailJob;
use Modules\Mailer\Models\MailerEndpoint;
use Modules\Mailer\Models\MailerEndpointLog;
use Modules\Mailer\Tests\TestCase;

class MailerEndpointApiTest extends TestCase
{
    private MailerEndpoint $endpoint;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $langId = $this->createLang();
        $template = $this->createTemplate($langId);
        $this->token = MailerEndpoint::generateToken();

        $this->endpoint = MailerEndpoint::create([
            'name' => 'Test Endpoint',
            'slug' => 'test-endpoint',
            'source' => 'phpunit',
            'type' => 'transactional',
            'mailer_template_id' => $template->id,
            'lang_id' => $langId,
            'is_active' => true,
            'api_token' => $this->token,
            'required_variables' => ['email', 'customer_name'],
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/email-endpoints/{slug}/send
    // -------------------------------------------------------------------------

    public function test_send_requires_api_token(): void
    {
        $this->postJson("/api/email-endpoints/{$this->endpoint->slug}/send", [
            'email' => 'test@example.com',
            'customer_name' => 'Test User',
        ])->assertUnauthorized();
    }

    public function test_send_rejects_invalid_token(): void
    {
        $this->postJson("/api/email-endpoints/{$this->endpoint->slug}/send", [
            'email' => 'test@example.com',
            'customer_name' => 'Test User',
        ], ['X-API-Token' => 'invalid-token'])
            ->assertUnauthorized();
    }

    public function test_send_returns_404_for_unknown_slug(): void
    {
        $this->postJson('/api/email-endpoints/nonexistent/send', [], [
            'X-API-Token' => $this->token,
        ])->assertNotFound();
    }

    public function test_send_rejects_inactive_endpoint(): void
    {
        $this->endpoint->update(['is_active' => false]);

        $this->postJson("/api/email-endpoints/{$this->endpoint->slug}/send", [
            'email' => 'test@example.com',
            'customer_name' => 'Test User',
        ], ['X-API-Token' => $this->token])
            ->assertForbidden();
    }

    public function test_send_validates_required_variables(): void
    {
        $response = $this->postJson("/api/email-endpoints/{$this->endpoint->slug}/send", [
            'email' => 'test@example.com',
            // missing customer_name
        ], ['X-API-Token' => $this->token]);

        $response->assertUnprocessable()
            ->assertJsonPath('missing_variables', ['customer_name']);
    }

    public function test_send_dispatches_job_and_creates_log(): void
    {
        Queue::fake();

        $response = $this->postJson("/api/email-endpoints/{$this->endpoint->slug}/send", [
            'email' => 'test@example.com',
            'customer_name' => 'Juan Garcia',
        ], ['X-API-Token' => $this->token]);

        $response->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['log_id', 'endpoint']);

        Queue::assertPushed(SendEndpointEmailJob::class);

        $this->assertDatabaseHas('mailer_endpoint_logs', [
            'mailer_endpoint_id' => $this->endpoint->id,
            'status' => EndpointLogStatus::Pending->value,
        ]);
    }

    public function test_send_increments_request_count(): void
    {
        Queue::fake();

        $countBefore = $this->endpoint->requests_count;

        $this->postJson("/api/email-endpoints/{$this->endpoint->slug}/send", [
            'email' => 'test@example.com',
            'customer_name' => 'Juan Garcia',
        ], ['X-API-Token' => $this->token]);

        $this->endpoint->refresh();
        $this->assertEquals($countBefore + 1, $this->endpoint->requests_count);
    }

    // -------------------------------------------------------------------------
    // GET /api/email-endpoints/{slug}/info
    // -------------------------------------------------------------------------

    public function test_info_returns_endpoint_metadata(): void
    {
        $response = $this->getJson("/api/email-endpoints/{$this->endpoint->slug}/info");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'test-endpoint')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonStructure(['data' => ['slug', 'name', 'type', 'source', 'expected_variables', 'required_variables']]);
    }

    public function test_info_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/email-endpoints/nonexistent/info')
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // GET /api/email-endpoints/{slug}/status
    // -------------------------------------------------------------------------

    public function test_status_returns_statistics(): void
    {
        // Create some logs
        MailerEndpointLog::create([
            'mailer_endpoint_id' => $this->endpoint->id,
            'payload' => [],
            'status' => EndpointLogStatus::Success,
            'sent_at' => now(),
        ]);
        MailerEndpointLog::create([
            'mailer_endpoint_id' => $this->endpoint->id,
            'payload' => [],
            'status' => EndpointLogStatus::Failed,
            'error_message' => 'SMTP error',
        ]);
        $this->endpoint->update(['requests_count' => 2]);

        $response = $this->getJson("/api/email-endpoints/{$this->endpoint->slug}/status");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.successful_emails', 1)
            ->assertJsonPath('data.failed_emails', 1)
            ->assertJsonPath('data.total_requests', 2);
    }
}
