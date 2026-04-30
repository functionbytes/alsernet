<?php

namespace Modules\Chat\Tests\Feature\Sla;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationStatus;
use Modules\Chat\Models\Sla\SlaPolicy;
use Modules\Chat\Services\Sla\SlaPolicyMatcher;
use Tests\TestCase;

class SlaPolicyMatcherTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;

    protected SlaPolicyMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();
        $this->matcher = new SlaPolicyMatcher;

        ConversationStatus::create([
            'account_id' => $this->account->id,
            'name' => 'Open',
            'slug' => 'open',
            'order' => 1,
        ]);

        ConversationStatus::create([
            'account_id' => $this->account->id,
            'name' => 'Resolved',
            'slug' => 'resolved',
            'order' => 2,
        ]);
    }

    public function test_find_applicable_policy_returns_active_policy(): void
    {
        $policy = SlaPolicy::factory()->active()->create([
            'account_id' => $this->account->id,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $foundPolicy = $this->matcher->findApplicablePolicy($conversation);

        $this->assertNotNull($foundPolicy);
        $this->assertEquals($policy->id, $foundPolicy->id);
    }

    public function test_find_applicable_policy_skips_inactive_policies(): void
    {
        SlaPolicy::factory()->inactive()->create([
            'account_id' => $this->account->id,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $foundPolicy = $this->matcher->findApplicablePolicy($conversation);

        $this->assertNull($foundPolicy);
    }

    public function test_find_applicable_policy_returns_first_active_policy(): void
    {
        $policy1 = SlaPolicy::factory()->active()->create([
            'account_id' => $this->account->id,
            'name' => 'Policy 1',
        ]);

        $policy2 = SlaPolicy::factory()->active()->create([
            'account_id' => $this->account->id,
            'name' => 'Policy 2',
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $foundPolicy = $this->matcher->findApplicablePolicy($conversation);

        $this->assertNotNull($foundPolicy);
        $this->assertEquals($policy1->id, $foundPolicy->id);
    }

    public function test_find_applicable_policy_scoped_to_account(): void
    {
        $otherAccount = Account::factory()->create();

        SlaPolicy::factory()->active()->create([
            'account_id' => $otherAccount->id,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $foundPolicy = $this->matcher->findApplicablePolicy($conversation);

        $this->assertNull($foundPolicy);
    }

    public function test_should_apply_sla_returns_true_for_open_conversation(): void
    {
        $openStatus = ConversationStatus::where('slug', 'open')->first();

        SlaPolicy::factory()->active()->create([
            'account_id' => $this->account->id,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'status_id' => $openStatus->id,
        ]);

        $shouldApply = $this->matcher->shouldApplySla($conversation);

        $this->assertTrue($shouldApply);
    }

    public function test_should_apply_sla_returns_false_for_resolved_conversation(): void
    {
        $resolvedStatus = ConversationStatus::where('slug', 'resolved')->first();

        SlaPolicy::factory()->active()->create([
            'account_id' => $this->account->id,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'status_id' => $resolvedStatus->id,
        ]);

        $shouldApply = $this->matcher->shouldApplySla($conversation);

        $this->assertFalse($shouldApply);
    }

    public function test_should_apply_sla_returns_false_when_no_policy_exists(): void
    {
        $openStatus = ConversationStatus::where('slug', 'open')->first();

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'status_id' => $openStatus->id,
        ]);

        $shouldApply = $this->matcher->shouldApplySla($conversation);

        $this->assertFalse($shouldApply);
    }

    public function test_apply_policy_updates_conversation_sla_id(): void
    {
        $policy = SlaPolicy::factory()->active()->create([
            'account_id' => $this->account->id,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => null,
        ]);

        $result = $this->matcher->applyPolicy($conversation);

        $this->assertTrue($result);

        $conversation->refresh();
        $this->assertEquals($policy->id, $conversation->sla_id);
    }

    public function test_apply_policy_returns_false_when_no_policy_found(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => null,
        ]);

        $result = $this->matcher->applyPolicy($conversation);

        $this->assertFalse($result);

        $conversation->refresh();
        $this->assertNull($conversation->sla_id);
    }

    public function test_apply_policy_overwrites_existing_sla_id(): void
    {
        $oldPolicy = SlaPolicy::factory()->inactive()->create([
            'account_id' => $this->account->id,
        ]);

        $newPolicy = SlaPolicy::factory()->active()->create([
            'account_id' => $this->account->id,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'sla_id' => $oldPolicy->id,
        ]);

        $result = $this->matcher->applyPolicy($conversation);

        $this->assertTrue($result);

        $conversation->refresh();
        $this->assertEquals($newPolicy->id, $conversation->sla_id);
    }

    public function test_should_apply_sla_returns_false_for_resolved_conversation_with_policy(): void
    {
        $resolvedStatus = ConversationStatus::where('slug', 'resolved')->first();

        $policy = SlaPolicy::factory()->active()->create([
            'account_id' => $this->account->id,
        ]);

        $conversation = Conversation::factory()->resolved()->create([
            'account_id' => $this->account->id,
            'status_id' => $resolvedStatus->id,
        ]);

        $shouldApply = $this->matcher->shouldApplySla($conversation);

        $this->assertFalse($shouldApply);
    }

    public function test_find_applicable_policy_returns_null_for_empty_account(): void
    {
        $emptyAccount = Account::factory()->create();

        $conversation = Conversation::factory()->create([
            'account_id' => $emptyAccount->id,
        ]);

        $foundPolicy = $this->matcher->findApplicablePolicy($conversation);

        $this->assertNull($foundPolicy);
    }
}
