<?php

namespace Modules\HelpdeskSla\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Models\SlaPolicy;
use Modules\HelpdeskSla\Listeners\MarkConversationFirstResponse;
use Tests\TestCase;

/**
 * Regression coverage for the `sla.integration_enabled` admin toggle
 * (panel/settings/helpdesk/integrations). Verifies HelpdeskSla entry
 * points — ConversationSlaObserver, the helpdesksla:check-breaches command
 * and the MarkConversationFirstResponse listener — honour
 * helpdesk_sla_enabled() and become no-ops while disabled.
 */
class SlaIntegrationToggleTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    private ConversationStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->openStatus = $this->ensureOpenStatus();
    }

    protected function tearDown(): void
    {
        Setting::set('sla.integration_enabled', '1', 'integrations');

        parent::tearDown();
    }

    public function test_observer_skips_sla_initialization_when_toggle_is_disabled(): void
    {
        Setting::set('sla.integration_enabled', '0', 'integrations');
        $this->makeGlobalPolicy();

        $conversation = $this->makeOpenConversation();
        $conversation->refresh();

        $this->assertNull($conversation->sla_policy_id);
        $this->assertNull($conversation->sla_first_response_due_at);
        $this->assertNull($conversation->sla_resolution_due_at);
    }

    public function test_observer_initializes_sla_when_toggle_is_enabled(): void
    {
        Setting::set('sla.integration_enabled', '1', 'integrations');
        $this->makeGlobalPolicy();

        $conversation = $this->makeOpenConversation();
        $conversation->refresh();

        $this->assertNotNull($conversation->sla_policy_id);
        $this->assertNotNull($conversation->sla_first_response_due_at);
    }

    public function test_check_breaches_command_skips_when_toggle_is_disabled(): void
    {
        Setting::set('sla.integration_enabled', '0', 'integrations');

        $conversation = $this->makeOpenConversation(['first_response_at' => null]);
        $conversation->updateQuietly([
            'sla_first_response_due_at' => now()->subMinutes(30),
            'sla_first_response_breached' => false,
        ]);

        Artisan::call('helpdesksla:check-breaches');

        $conversation->refresh();

        $this->assertFalse($conversation->sla_first_response_breached);
        $this->assertDatabaseMissing('helpdesk_conversation_sla_breaches', [
            'conversation_id' => $conversation->id,
        ], 'helpdesk');
    }

    public function test_check_breaches_command_detects_breach_when_toggle_is_enabled(): void
    {
        Setting::set('sla.integration_enabled', '1', 'integrations');

        $conversation = $this->makeOpenConversation(['first_response_at' => null]);
        $conversation->updateQuietly([
            'sla_first_response_due_at' => now()->subMinutes(30),
            'sla_first_response_breached' => false,
        ]);

        Artisan::call('helpdesksla:check-breaches');

        $conversation->refresh();

        $this->assertTrue($conversation->sla_first_response_breached);
        $this->assertDatabaseHas('helpdesk_conversation_sla_breaches', [
            'conversation_id' => $conversation->id,
        ], 'helpdesk');
    }

    public function test_first_response_listener_skips_when_toggle_is_disabled(): void
    {
        Setting::set('sla.integration_enabled', '0', 'integrations');

        $conversation = $this->makeOpenConversation(['first_response_at' => null]);
        $agent = User::factory()->create();

        $item = ConversationItem::create([
            'conversation_id' => $conversation->id,
            'user_id' => $agent->id,
            'type' => 'message',
            'is_internal' => false,
            'body' => 'Respuesta del agente',
        ]);

        app(MarkConversationFirstResponse::class)->handle(new ConversationMessageCreated($item));

        $this->assertNull($conversation->fresh()->first_response_at);
    }

    public function test_first_response_listener_marks_when_toggle_is_enabled(): void
    {
        Setting::set('sla.integration_enabled', '1', 'integrations');

        $conversation = $this->makeOpenConversation(['first_response_at' => null]);
        $agent = User::factory()->create();

        $item = ConversationItem::create([
            'conversation_id' => $conversation->id,
            'user_id' => $agent->id,
            'type' => 'message',
            'is_internal' => false,
            'body' => 'Respuesta del agente',
        ]);

        app(MarkConversationFirstResponse::class)->handle(new ConversationMessageCreated($item));

        $this->assertNotNull($conversation->fresh()->first_response_at);
    }

    private function ensureOpenStatus(): ConversationStatus
    {
        return ConversationStatus::where('is_open', true)->orderBy('order')->first()
            ?? ConversationStatus::create([
                'name' => 'Abierto',
                'is_open' => true,
                'order' => 1,
            ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeOpenConversation(array $attributes = []): Conversation
    {
        return Conversation::factory()->create(array_merge(
            ['status_id' => $this->openStatus->id],
            $attributes
        ));
    }

    private function makeGlobalPolicy(): SlaPolicy
    {
        return SlaPolicy::create([
            'name' => 'Global test policy',
            'priority_id' => null,
            'category_id' => null,
            'first_response_time_hours' => 2,
            'resolution_time_hours' => 8,
            'business_hours_only' => false,
            'warning_threshold_percent' => 80,
            'is_active' => true,
        ]);
    }
}
