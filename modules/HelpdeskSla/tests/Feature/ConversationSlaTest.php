<?php

namespace Modules\HelpdeskSla\Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\BusinessHour;
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

    /**
     * SlaPolicy/Conversation/ConversationSlaBreach all live on the 'helpdesk'
     * connection. Without listing it here, DatabaseTransactions only wraps the
     * default connection, so every policy/conversation created by this class
     * was being committed for real instead of rolled back — the shared test DB
     * had several leftover "Global test policy" rows from previous runs
     * (already cleaned up) before this fix.
     */
    protected $connectionsToTransact = [null, 'helpdesk'];

    private ConversationSlaService $service;

    private ConversationStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->service = app(ConversationSlaService::class);
        $this->openStatus = $this->ensureOpenStatus();
        $this->disableOtherActiveGlobalPolicies();
        $this->disableActivePriorityPolicies();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * The shared test DB may already contain other active global (priority_id
     * and category_id both NULL) SLA policies, either real seed data or a
     * leftover from before this class transacted the 'helpdesk' connection.
     * getApplicablePolicy() has no ORDER BY, so with more than one active
     * global policy it is undefined which one is resolved — deactivate the
     * rest before each test so makeGlobalPolicy()'s policy is unambiguously
     * the one applied.
     */
    private function disableOtherActiveGlobalPolicies(): void
    {
        SlaPolicy::query()
            ->whereNull('priority_id')
            ->whereNull('category_id')
            ->update(['is_active' => false]);
    }

    /**
     * getApplicablePolicy() also prefers a category-agnostic policy scoped
     * to the conversation's exact priority over the global fallback above,
     * and the shared test DB has one of those active for every real
     * priority (baja/normal/alta/urgente/critico). Conversation::factory()
     * assigns a random priority, so whichever real policy matched it (with
     * its own real hours/business_hours_only) would silently win over the
     * policy a test builds with makeGlobalPolicy()/makeBusinessHoursPolicy(),
     * making assertions that compare against a specific due date/threshold
     * flaky (or passing "by luck" whenever the real policy's numbers
     * happened to agree). Deactivate every priority-specific policy so only
     * what a test creates for itself is ever resolved.
     */
    private function disableActivePriorityPolicies(): void
    {
        SlaPolicy::query()
            ->whereNotNull('priority_id')
            ->whereNull('category_id')
            ->update(['is_active' => false]);
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
        ], 'helpdesk');
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
        ], 'helpdesk');
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

    /**
     * BUG-06 / item 11: sendWarnings() ahora filtra el umbral en SQL antes de
     * llegar a PHP (por eficiencia); este test fija el conjunto exacto de
     * conversaciones avisadas alrededor del borde del umbral (justo debajo,
     * exactamente en el umbral, justo encima) para detectar un falso
     * negativo/positivo introducido por ese pre-filtro.
     */
    public function test_send_warnings_only_warns_conversations_at_or_past_the_threshold(): void
    {
        $policy = $this->makeGlobalPolicy(firstResponseHours: 10, resolutionHours: 20);

        $belowThreshold = $this->makeConversationAtFirstResponseUsage($policy, usedMinutes: 420); // 70%
        $atThreshold = $this->makeConversationAtFirstResponseUsage($policy, usedMinutes: 480); // 80%
        $aboveThreshold = $this->makeConversationAtFirstResponseUsage($policy, usedMinutes: 540); // 90%

        $this->service->sendWarnings();

        $this->assertNull($belowThreshold->fresh()->sla_warned_at);
        $this->assertNotNull($atThreshold->fresh()->sla_warned_at);
        $this->assertNotNull($aboveThreshold->fresh()->sla_warned_at);
    }

    private function makeConversationAtFirstResponseUsage(SlaPolicy $policy, int $usedMinutes): Conversation
    {
        $created = now()->subMinutes($usedMinutes);

        $conversation = $this->makeOpenConversation(['first_response_at' => null]);

        $conversation->forceFill([
            'created_at' => $created,
            'sla_policy_id' => $policy->id,
            'sla_first_response_due_at' => $created->copy()->addHours(10),
            'sla_first_response_breached' => false,
            'sla_warned_at' => null,
        ])->saveQuietly();

        return $conversation;
    }

    /**
     * BUG-06 / item 1: escalar la prioridad de una conversación YA
     * incumplida no debe resetear el flag sin resolver el breach existente
     * (eso generaba un segundo registro + una segunda alerta en el próximo
     * checkBreaches()).
     */
    public function test_recalculate_resolves_existing_breach_instead_of_duplicating_it(): void
    {
        $this->makeGlobalPolicy(firstResponseHours: 2, resolutionHours: 8);
        $conversation = $this->makeOpenConversation(['priority' => 'low', 'first_response_at' => null]);

        $conversation->updateQuietly([
            'sla_first_response_due_at' => now()->subHour(),
            'sla_first_response_breached' => true,
        ]);

        $breach = ConversationSlaBreach::create([
            'conversation_id' => $conversation->id,
            'sla_type' => ConversationSlaBreach::TYPE_FIRST_RESPONSE,
            'due_at' => now()->subHour(),
            'breached_at' => now(),
            'resolved' => false,
        ]);

        $conversation->update(['priority' => 'urgent']);

        $this->assertTrue($breach->fresh()->resolved);
        $this->assertSame(
            1,
            ConversationSlaBreach::query()->where('conversation_id', $conversation->id)->count()
        );
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

    /**
     * QUAL-09 / item 2a: checkBreaches() flags a first-response breach and
     * records it, a priority change recalculates but leaves the due date in
     * the past (same global policy, no priority-specific one is seeded), and
     * a second checkBreaches() run must not create a second breach row for
     * the same conversation.
     */
    public function test_check_breaches_does_not_duplicate_breach_after_priority_change(): void
    {
        $this->makeGlobalPolicy(firstResponseHours: 2, resolutionHours: 8);
        $conversation = $this->makeOpenConversation([
            'priority' => 'low',
            'first_response_at' => null,
            'created_at' => now()->subHours(3),
        ]);

        $this->service->checkBreaches();

        $this->assertTrue($conversation->fresh()->sla_first_response_breached);
        $this->assertSame(
            1,
            ConversationSlaBreach::query()->where('conversation_id', $conversation->id)->count()
        );

        // checkBreaches() updated the row through its own query cursor, not
        // through this PHP instance: refresh it so the observer sees the real
        // (breached) state, otherwise recalculate() would reset the stale
        // "not breached" flag and let the next checkBreaches() re-detect it.
        $conversation->refresh();
        $conversation->update(['priority' => 'high']);

        $this->service->checkBreaches();

        $this->assertSame(
            1,
            ConversationSlaBreach::query()->where('conversation_id', $conversation->id)->count()
        );
    }

    /**
     * QUAL-09 / item 2b: snooze pauses the clock, resume extends the due date
     * by the paused minutes, and a subsequent priority change recomputes the
     * due date from scratch but must still add back the accumulated paused
     * minutes so the snooze extension is not silently lost.
     */
    public function test_priority_change_preserves_snooze_extension_after_resume(): void
    {
        $this->makeGlobalPolicy(firstResponseHours: 2, resolutionHours: 8);
        $conversation = $this->makeOpenConversation(['priority' => 'low']);

        $conversation->update(['snoozed_until' => now()->addDay()]);
        $this->assertNotNull($conversation->fresh()->sla_paused_at);

        // Simulate 45 minutes paused, then resume.
        $conversation->forceFill(['sla_paused_at' => now()->subMinutes(45)])->saveQuietly();
        $conversation->update(['snoozed_until' => null]);

        $resumed = $conversation->fresh();
        $this->assertNull($resumed->sla_paused_at);
        $this->assertGreaterThanOrEqual(45, $resumed->sla_paused_duration_minutes);

        $extendedDueAt = $resumed->sla_first_response_due_at;

        $conversation->update(['priority' => 'high']);

        $fresh = $conversation->fresh();
        $this->assertEqualsWithDelta(
            $extendedDueAt->timestamp,
            $fresh->sla_first_response_due_at->timestamp,
            5
        );
    }

    /**
     * QUAL-09 / item 2c: a business-hours-only policy must not fire a warning
     * over the weekend just because a lot of wall-clock time has passed. The
     * calendar's real Mon-Fri rows are reused as-is (read only); Saturday and
     * Sunday are temporarily closed within this test's own transaction (never
     * committed) so the scenario is deterministic regardless of whatever the
     * shared test DB's weekend hours happen to be. The cache is switched to
     * the array driver for this test only so the temporary calendar override
     * is never written to the real shared cache store.
     */
    public function test_send_warnings_does_not_warn_over_the_weekend_for_business_hours_policy(): void
    {
        config(['cache.default' => 'array']);

        $timezone = BusinessHour::whereNotNull('timezone')->value('timezone') ?? 'Europe/Madrid';

        BusinessHour::query()->whereIn('day_of_week', [0, 6])->update(['is_open' => false]);

        $this->makeBusinessHoursPolicy(firstResponseHours: 4, resolutionHours: 40);

        $fridayLate = Carbon::parse('last friday 17:00', $timezone);

        $conversation = $this->makeOpenConversation([
            'priority' => 'low',
            'first_response_at' => null,
            'created_at' => $fridayLate,
        ]);

        // Sanity check: initialize() used the business-hours calendar, not a
        // flat +4h from creation (which would already be past by now()).
        $this->assertNotNull($conversation->fresh()->sla_first_response_due_at);

        Carbon::setTestNow($fridayLate->copy()->addHours(40)); // deep into the (closed) weekend

        // sendWarnings() sweeps every open conversation in the shared test DB
        // for every active policy, so its return count is not scoped to this
        // test; only this conversation's own sla_warned_at is asserted.
        $this->service->sendWarnings();

        $this->assertNull($conversation->fresh()->sla_warned_at);
    }

    private function makeBusinessHoursPolicy(int $firstResponseHours, int $resolutionHours): SlaPolicy
    {
        return SlaPolicy::create([
            'name' => 'Business hours test policy',
            'priority_id' => null,
            'category_id' => null,
            'first_response_time_hours' => $firstResponseHours,
            'resolution_time_hours' => $resolutionHours,
            'business_hours_only' => true,
            'warning_threshold_percent' => 80,
            'is_active' => true,
        ]);
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
