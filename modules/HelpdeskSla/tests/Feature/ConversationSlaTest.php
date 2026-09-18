<?php

namespace Modules\HelpdeskSla\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\SlaPolicy;
use Modules\HelpdeskSla\Listeners\MarkConversationFirstResponse;
use Modules\HelpdeskSla\Models\ConversationSlaBreach;
use Modules\HelpdeskSla\Services\ConversationSlaService;
use Tests\TestCase;

/**
 * Feature tests for the conversation SLA engine.
 *
 * NOTE: these require the helpdesk connection with the new SLA columns migrated.
 * The project's shared test snapshot is currently blocked, so they are verified
 * statically (php -l + pint) and will execute once the test DB is rebuilt.
 */
class ConversationSlaTest extends TestCase
{
    use DatabaseTransactions;

    private ConversationSlaService $service;

    private ConversationStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->service = app(ConversationSlaService::class);
        $this->openStatus = $this->ensureOpenStatus();
    }

    public function test_sla_is_initialized_when_conversation_is_created(): void
    {
        $this->makeGlobalPolicy(firstResponseHours: 2, resolutionHours: 8);

        $conversation = $this->makeOpenConversation();

        $conversation->refresh();

        $this->assertNotNull($conversation->sla_policy_id);
        $this->assertNotNull($conversation->sla_first_response_due_at);
        $this->assertNotNull($conversation->sla_resolution_due_at);
        $this->assertEqualsWithDelta(
            $conversation->created_at->copy()->addHours(2)->timestamp,
            $conversation->sla_first_response_due_at->timestamp,
            120
        );
    }

    public function test_first_response_listener_stops_the_clock_on_agent_reply(): void
    {
        $conversation = $this->makeOpenConversation(['first_response_at' => null]);
        $agent = User::factory()->create();

        $item = ConversationItem::create([
            'conversation_id' => $conversation->id,
            'user_id' => $agent->id,
            'type' => 'message',
            'is_internal' => false,
            'body' => 'Respuesta del agente',
        ]);

        (new MarkConversationFirstResponse($this->service))
            ->handle(new ConversationMessageCreated($item));

        $this->assertNotNull($conversation->fresh()->first_response_at);
    }

    public function test_internal_note_does_not_count_as_first_response(): void
    {
        $conversation = $this->makeOpenConversation(['first_response_at' => null]);
        $agent = User::factory()->create();

        $item = ConversationItem::create([
            'conversation_id' => $conversation->id,
            'user_id' => $agent->id,
            'type' => 'message',
            'is_internal' => true,
            'body' => 'Nota interna',
        ]);

        (new MarkConversationFirstResponse($this->service))
            ->handle(new ConversationMessageCreated($item));

        $this->assertNull($conversation->fresh()->first_response_at);
    }

    public function test_check_breaches_detects_first_response_breach(): void
    {
        $conversation = $this->makeOpenConversation(['first_response_at' => null]);

        $conversation->updateQuietly([
            'sla_first_response_due_at' => now()->subMinutes(30),
            'sla_first_response_breached' => false,
        ]);

        $detected = $this->service->checkBreaches();

        $conversation->refresh();

        $this->assertGreaterThanOrEqual(1, $detected);
        $this->assertTrue($conversation->sla_first_response_breached);
        $this->assertDatabaseHas('helpdesk_conversation_sla_breaches', [
            'conversation_id' => $conversation->id,
            'sla_type' => ConversationSlaBreach::TYPE_FIRST_RESPONSE,
        ]);
    }

    public function test_finalize_records_resolution_breach_when_closed_late(): void
    {
        $conversation = $this->makeOpenConversation();

        $conversation->updateQuietly([
            'sla_resolution_due_at' => now()->subHours(2),
            'sla_resolution_breached' => false,
            'closed_at' => now(),
        ]);

        $this->service->finalize($conversation);

        $this->assertTrue($conversation->fresh()->sla_resolution_breached);
        $this->assertDatabaseHas('helpdesk_conversation_sla_breaches', [
            'conversation_id' => $conversation->id,
            'sla_type' => ConversationSlaBreach::TYPE_RESOLUTION,
        ]);
    }

    public function test_send_warnings_warns_conversation_approaching_deadline(): void
    {
        $policy = $this->makeGlobalPolicy(firstResponseHours: 1, resolutionHours: 4);
        $conversation = $this->makeOpenConversation(['first_response_at' => null]);

        // 8 of 10 minutes consumed => 80% (== warning threshold), not yet breached.
        $conversation->forceFill([
            'created_at' => now()->subMinutes(8),
            'sla_policy_id' => $policy->id,
            'sla_first_response_due_at' => now()->addMinutes(2),
            'sla_first_response_breached' => false,
            'sla_warned_at' => null,
        ])->saveQuietly();

        $sent = $this->service->sendWarnings();

        $this->assertGreaterThanOrEqual(1, $sent);
        $this->assertNotNull($conversation->fresh()->sla_warned_at);
    }

    public function test_snooze_pauses_the_sla_clock(): void
    {
        $conversation = $this->makeOpenConversation();
        $conversation->forceFill(['sla_resolution_due_at' => now()->addHours(4)])->saveQuietly();

        $conversation->update(['snoozed_until' => now()->addDay()]);

        $this->assertNotNull($conversation->fresh()->sla_paused_at);
    }

    public function test_unsnooze_resumes_and_extends_the_sla_clock(): void
    {
        $conversation = $this->makeOpenConversation();
        $conversation->forceFill([
            'snoozed_until' => now()->addDay(),
            'sla_paused_at' => now()->subMinutes(30),
            'sla_resolution_due_at' => now()->addHours(2),
        ])->saveQuietly();

        $conversation->update(['snoozed_until' => null]);

        $fresh = $conversation->fresh();
        $this->assertNull($fresh->sla_paused_at);
        $this->assertGreaterThan(0, $fresh->sla_paused_duration_minutes);
    }

    public function test_priority_change_recalculates_due_dates(): void
    {
        $this->makeGlobalPolicy(firstResponseHours: 2, resolutionHours: 8);
        $conversation = $this->makeOpenConversation(['priority' => 'low']);

        $conversation->forceFill([
            'sla_policy_id' => null,
            'sla_first_response_due_at' => null,
            'sla_resolution_due_at' => null,
        ])->saveQuietly();

        $conversation->update(['priority' => 'high']);

        $fresh = $conversation->fresh();
        $this->assertNotNull($fresh->sla_policy_id);
        $this->assertNotNull($fresh->sla_resolution_due_at);
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
     * Create a conversation with an explicit open status (the factory's
     * afterCreating status assignment is not reliable across environments).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeOpenConversation(array $attributes = []): Conversation
    {
        return Conversation::factory()->create(array_merge(
            ['status_id' => $this->openStatus->id],
            $attributes
        ));
    }

    private function makeGlobalPolicy(int $firstResponseHours, int $resolutionHours): SlaPolicy
    {
        return SlaPolicy::create([
            'name' => 'Global test policy',
            'priority_id' => null,
            'category_id' => null,
            'first_response_time_hours' => $firstResponseHours,
            'resolution_time_hours' => $resolutionHours,
            'business_hours_only' => false,
            'warning_threshold_percent' => 80,
            'is_active' => true,
        ]);
    }
}
