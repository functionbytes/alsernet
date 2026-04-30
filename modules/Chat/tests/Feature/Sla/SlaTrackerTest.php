<?php

namespace Modules\Chat\Tests\Feature\Sla;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Sla\SlaAppliedConversation;
use Modules\Chat\Models\Sla\SlaPolicy;
use Modules\Chat\Services\Sla\SlaTracker;
use Tests\TestCase;

class SlaTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;

    protected SlaTracker $tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();
        $this->tracker = new SlaTracker;
    }

    public function test_track_conversation_creates_sla_tracking_record(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 60,
            'resolution_time_minutes' => 240,
            'business_hours_only' => false,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $this->assertNotNull($slaTracking);
        $this->assertEquals($policy->id, $slaTracking->sla_id);
        $this->assertEquals($conversation->id, $slaTracking->conversation_id);
        $this->assertNotNull($slaTracking->first_response_due_at);
        $this->assertNotNull($slaTracking->resolution_due_at);

        $this->assertDatabaseHas('chat_sla_applied_conversations', [
            'conversation_id' => $conversation->id,
            'sla_id' => $policy->id,
        ]);
    }

    public function test_track_conversation_skips_if_no_sla_policy(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => null,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $this->assertNull($slaTracking);
        $this->assertDatabaseMissing('chat_sla_applied_conversations', [
            'conversation_id' => $conversation->id,
        ]);
    }

    public function test_calculate_due_date_without_business_hours(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 120,
            'business_hours_only' => false,
        ]);

        $startTime = Carbon::parse('2026-02-11 10:00:00');

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => $startTime,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $expectedDueDate = $startTime->copy()->addMinutes(120);
        $this->assertEquals($expectedDueDate->format('Y-m-d H:i'), $slaTracking->first_response_due_at->format('Y-m-d H:i'));
    }

    public function test_calculate_due_date_with_business_hours_same_day(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 120,
            'business_hours_only' => true,
        ]);

        $startTime = Carbon::parse('2026-02-11 10:00:00');

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => $startTime,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $expectedDueDate = Carbon::parse('2026-02-11 12:00:00');
        $this->assertEquals($expectedDueDate->format('Y-m-d H:i'), $slaTracking->first_response_due_at->format('Y-m-d H:i'));
    }

    public function test_calculate_due_date_with_business_hours_across_days(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 600,
            'business_hours_only' => true,
        ]);

        $startTime = Carbon::parse('2026-02-11 10:00:00');

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => $startTime,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $expectedDueDate = Carbon::parse('2026-02-12 11:00:00');
        $this->assertEquals($expectedDueDate->format('Y-m-d H:i'), $slaTracking->first_response_due_at->format('Y-m-d H:i'));
    }

    public function test_calculate_due_date_with_business_hours_before_9am(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 60,
            'business_hours_only' => true,
        ]);

        $startTime = Carbon::parse('2026-02-11 08:00:00');

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => $startTime,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $expectedDueDate = Carbon::parse('2026-02-11 10:00:00');
        $this->assertEquals($expectedDueDate->format('Y-m-d H:i'), $slaTracking->first_response_due_at->format('Y-m-d H:i'));
    }

    public function test_calculate_due_date_with_business_hours_after_5pm(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 60,
            'business_hours_only' => true,
        ]);

        $startTime = Carbon::parse('2026-02-11 18:00:00');

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => $startTime,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $expectedDueDate = Carbon::parse('2026-02-12 10:00:00');
        $this->assertEquals($expectedDueDate->format('Y-m-d H:i'), $slaTracking->first_response_due_at->format('Y-m-d H:i'));
    }

    public function test_calculate_due_date_with_business_hours_weekend(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 60,
            'business_hours_only' => true,
        ]);

        $startTime = Carbon::parse('2026-02-14 10:00:00');

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => $startTime,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $expectedDueDate = Carbon::parse('2026-02-16 10:00:00');
        $this->assertEquals($expectedDueDate->format('Y-m-d H:i'), $slaTracking->first_response_due_at->format('Y-m-d H:i'));
    }

    public function test_record_first_response_updates_tracking(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 60,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $this->tracker->recordFirstResponse($conversation);

        $slaTracking->refresh();
        $this->assertNotNull($slaTracking->first_response_at);
        $this->assertFalse($slaTracking->first_response_breached);
    }

    public function test_record_first_response_marks_breach_if_late(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 60,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => now()->subHours(2),
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $this->tracker->recordFirstResponse($conversation);

        $slaTracking->refresh();
        $this->assertNotNull($slaTracking->first_response_at);
        $this->assertTrue($slaTracking->first_response_breached);
    }

    public function test_record_first_response_does_not_update_if_already_recorded(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 60,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);
        $firstResponseTime = now()->subMinutes(10);
        $slaTracking->update(['first_response_at' => $firstResponseTime]);

        $this->tracker->recordFirstResponse($conversation);

        $slaTracking->refresh();
        $this->assertEquals($firstResponseTime->format('Y-m-d H:i:s'), $slaTracking->first_response_at->format('Y-m-d H:i:s'));
    }

    public function test_record_resolution_updates_tracking(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'resolution_time_minutes' => 240,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $this->tracker->recordResolution($conversation);

        $slaTracking->refresh();
        $this->assertNotNull($slaTracking->resolved_at);
        $this->assertFalse($slaTracking->resolution_breached);
    }

    public function test_record_resolution_marks_breach_if_late(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'resolution_time_minutes' => 60,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => now()->subHours(3),
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $this->tracker->recordResolution($conversation);

        $slaTracking->refresh();
        $this->assertNotNull($slaTracking->resolved_at);
        $this->assertTrue($slaTracking->resolution_breached);
    }

    public function test_check_breaches_detects_first_response_breach(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 60,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => now()->subHours(2),
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $breachCount = $this->tracker->checkBreaches();

        $this->assertEquals(1, $breachCount);

        $slaTracking->refresh();
        $this->assertTrue($slaTracking->first_response_breached);
    }

    public function test_check_breaches_detects_resolution_breach(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'resolution_time_minutes' => 60,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => now()->subHours(2),
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $breachCount = $this->tracker->checkBreaches();

        $this->assertEquals(1, $breachCount);

        $slaTracking->refresh();
        $this->assertTrue($slaTracking->resolution_breached);
    }

    public function test_check_breaches_does_not_duplicate_breach_flags(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 60,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => now()->subHours(2),
        ]);

        $this->tracker->trackConversation($conversation);

        $firstCount = $this->tracker->checkBreaches();
        $secondCount = $this->tracker->checkBreaches();

        $this->assertEquals(1, $firstCount);
        $this->assertEquals(0, $secondCount);
    }

    public function test_check_breaches_skips_already_responded_conversations(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 60,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => now()->subHours(2),
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);
        $slaTracking->update(['first_response_at' => now()]);

        $breachCount = $this->tracker->checkBreaches();

        $this->assertEquals(0, $breachCount);
    }

    public function test_get_account_statistics_returns_correct_metrics(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
        ]);

        SlaAppliedConversation::factory()->count(10)->create([
            'sla_id' => $policy->id,
            'first_response_breached' => false,
            'resolution_breached' => false,
        ]);

        SlaAppliedConversation::factory()->count(3)->create([
            'sla_id' => $policy->id,
            'first_response_breached' => true,
        ]);

        SlaAppliedConversation::factory()->count(2)->create([
            'sla_id' => $policy->id,
            'resolution_breached' => true,
        ]);

        $stats = $this->tracker->getAccountStatistics($this->account->id);

        $this->assertEquals(15, $stats['total']);
        $this->assertEquals(5, $stats['breached']);
        $this->assertEquals(66.67, $stats['compliance_rate']);
    }

    public function test_get_account_statistics_handles_empty_account(): void
    {
        $stats = $this->tracker->getAccountStatistics($this->account->id);

        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['breached']);
        $this->assertEquals(100, $stats['compliance_rate']);
    }

    public function test_calculate_due_date_with_business_hours_spanning_weekend(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 120,
            'business_hours_only' => true,
        ]);

        $startTime = Carbon::parse('2026-02-13 16:30:00');

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => $startTime,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $expectedDueDate = Carbon::parse('2026-02-16 10:30:00');
        $this->assertEquals($expectedDueDate->format('Y-m-d H:i'), $slaTracking->first_response_due_at->format('Y-m-d H:i'));
    }

    public function test_calculate_due_date_handles_large_duration(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 4800,
            'business_hours_only' => false,
        ]);

        $startTime = Carbon::parse('2026-02-11 10:00:00');

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => $startTime,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $expectedDueDate = $startTime->copy()->addMinutes(4800);
        $this->assertEquals($expectedDueDate->format('Y-m-d H:i'), $slaTracking->first_response_due_at->format('Y-m-d H:i'));
    }

    public function test_calculate_due_date_with_business_hours_multiple_weeks(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 2400,
            'business_hours_only' => true,
        ]);

        $startTime = Carbon::parse('2026-02-11 10:00:00');

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => $startTime,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $this->assertNotNull($slaTracking->first_response_due_at);
        $this->assertTrue($slaTracking->first_response_due_at->isAfter($startTime));
    }

    public function test_record_resolution_does_not_update_if_already_resolved(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'resolution_time_minutes' => 240,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);
        $resolvedTime = now()->subMinutes(30);
        $slaTracking->update(['resolved_at' => $resolvedTime]);

        $this->tracker->recordResolution($conversation);

        $slaTracking->refresh();
        $this->assertEquals($resolvedTime->format('Y-m-d H:i:s'), $slaTracking->resolved_at->format('Y-m-d H:i:s'));
    }

    public function test_track_conversation_does_not_overwrite_existing_due_dates(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 60,
            'resolution_time_minutes' => 240,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);
        $originalFirstResponseDue = $slaTracking->first_response_due_at;
        $originalResolutionDue = $slaTracking->resolution_due_at;

        $this->tracker->trackConversation($conversation);

        $slaTracking->refresh();
        $this->assertEquals($originalFirstResponseDue->format('Y-m-d H:i:s'), $slaTracking->first_response_due_at->format('Y-m-d H:i:s'));
        $this->assertEquals($originalResolutionDue->format('Y-m-d H:i:s'), $slaTracking->resolution_due_at->format('Y-m-d H:i:s'));
    }

    public function test_check_breaches_handles_multiple_conversations(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 60,
        ]);

        $conversation1 = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => now()->subHours(2),
        ]);

        $conversation2 = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => now()->subHours(3),
        ]);

        $this->tracker->trackConversation($conversation1);
        $this->tracker->trackConversation($conversation2);

        $breachCount = $this->tracker->checkBreaches();

        $this->assertEquals(2, $breachCount);
    }

    public function test_check_breaches_handles_both_first_response_and_resolution(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 60,
            'resolution_time_minutes' => 120,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => now()->subHours(3),
        ]);

        $this->tracker->trackConversation($conversation);

        $breachCount = $this->tracker->checkBreaches();

        $this->assertEquals(2, $breachCount);

        $slaTracking = $conversation->slaTracking;
        $this->assertTrue($slaTracking->first_response_breached);
        $this->assertTrue($slaTracking->resolution_breached);
    }

    public function test_record_first_response_with_no_tracking_record(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => null,
        ]);

        $this->tracker->recordFirstResponse($conversation);

        $this->assertNull($conversation->slaTracking);
    }

    public function test_record_resolution_with_no_tracking_record(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => null,
        ]);

        $this->tracker->recordResolution($conversation);

        $this->assertNull($conversation->slaTracking);
    }

    public function test_track_conversation_with_only_first_response_time(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 60,
            'resolution_time_minutes' => null,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $this->assertNotNull($slaTracking->first_response_due_at);
        $this->assertNull($slaTracking->resolution_due_at);
    }

    public function test_track_conversation_with_only_resolution_time(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => null,
            'resolution_time_minutes' => 240,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $this->assertNull($slaTracking->first_response_due_at);
        $this->assertNotNull($slaTracking->resolution_due_at);
    }

    public function test_get_account_statistics_with_approaching_deadlines(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
        ]);

        SlaAppliedConversation::factory()->count(5)->create([
            'sla_id' => $policy->id,
            'first_response_breached' => false,
            'resolution_breached' => false,
        ]);

        SlaAppliedConversation::factory()->count(2)->approaching()->create([
            'sla_id' => $policy->id,
        ]);

        SlaAppliedConversation::factory()->count(3)->firstResponseBreached()->create([
            'sla_id' => $policy->id,
        ]);

        $stats = $this->tracker->getAccountStatistics($this->account->id);

        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(3, $stats['breached']);
        $this->assertEquals(2, $stats['approaching']);
        $this->assertEquals(5, $stats['on_track']);
    }

    public function test_calculate_due_date_at_exact_business_hour_boundary(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 480,
            'business_hours_only' => true,
        ]);

        $startTime = Carbon::parse('2026-02-11 09:00:00');

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => $startTime,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $expectedDueDate = Carbon::parse('2026-02-11 17:00:00');
        $this->assertEquals($expectedDueDate->format('Y-m-d H:i'), $slaTracking->first_response_due_at->format('Y-m-d H:i'));
    }

    public function test_calculate_due_date_with_zero_minutes(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 0,
        ]);

        $startTime = Carbon::parse('2026-02-11 10:00:00');

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => $startTime,
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $this->assertEquals($startTime->format('Y-m-d H:i:s'), $slaTracking->first_response_due_at->format('Y-m-d H:i:s'));
    }

    public function test_check_breaches_only_breaches_once_per_metric(): void
    {
        $policy = SlaPolicy::factory()->create([
            'account_id' => $this->account->id,
            'first_response_time_minutes' => 60,
            'resolution_time_minutes' => 120,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $policy->id,
            'created_at' => now()->subHours(3),
        ]);

        $slaTracking = $this->tracker->trackConversation($conversation);

        $firstRun = $this->tracker->checkBreaches();

        $slaTracking->refresh();
        $this->assertTrue($slaTracking->first_response_breached);
        $this->assertTrue($slaTracking->resolution_breached);

        $secondRun = $this->tracker->checkBreaches();

        $this->assertEquals(2, $firstRun);
        $this->assertEquals(0, $secondRun);
    }
}
