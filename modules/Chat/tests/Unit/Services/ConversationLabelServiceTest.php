<?php

namespace Modules\Chat\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Services\Conversations\ConversationLabelService;
use Tests\TestCase;

class ConversationLabelServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ConversationLabelService $service;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ConversationLabelService;
        $this->account = Account::factory()->create();
    }

    public function test_add_label_to_empty_list(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'cached_label_list' => null,
        ]);

        $this->service->addLabel($conversation, 'urgent');

        $conversation->refresh();
        $this->assertEquals('urgent', $conversation->cached_label_list);
    }

    public function test_add_label_to_existing_labels(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'cached_label_list' => 'existing',
        ]);

        $this->service->addLabel($conversation, 'urgent');

        $conversation->refresh();
        $this->assertStringContainsString('existing', $conversation->cached_label_list);
        $this->assertStringContainsString('urgent', $conversation->cached_label_list);
    }

    public function test_add_label_does_not_duplicate(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'cached_label_list' => 'urgent',
        ]);

        $this->service->addLabel($conversation, 'urgent');

        $conversation->refresh();
        $this->assertEquals('urgent', $conversation->cached_label_list);
    }

    public function test_remove_label_from_list(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'cached_label_list' => 'urgent,support',
        ]);

        $this->service->removeLabel($conversation, 'urgent');

        $conversation->refresh();
        $this->assertEquals('support', $conversation->cached_label_list);
    }

    public function test_remove_label_from_empty_list(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'cached_label_list' => null,
        ]);

        $this->service->removeLabel($conversation, 'urgent');

        $conversation->refresh();
        $this->assertNull($conversation->cached_label_list);
    }

    public function test_remove_last_label_sets_null(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'cached_label_list' => 'urgent',
        ]);

        $this->service->removeLabel($conversation, 'urgent');

        $conversation->refresh();
        $this->assertNull($conversation->cached_label_list);
    }

    public function test_sync_labels_replaces_all(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'cached_label_list' => 'old,labels',
        ]);

        $this->service->syncLabels($conversation, ['bug', 'critical', 'production']);

        $conversation->refresh();
        $this->assertEquals('bug,critical,production', $conversation->cached_label_list);
    }

    public function test_sync_labels_handles_empty_array(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'cached_label_list' => 'existing',
        ]);

        $this->service->syncLabels($conversation, []);

        $conversation->refresh();
        $this->assertNull($conversation->cached_label_list);
    }

    public function test_sync_labels_removes_duplicates(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'cached_label_list' => null,
        ]);

        $this->service->syncLabels($conversation, ['urgent', 'urgent', 'support']);

        $conversation->refresh();
        $labels = explode(',', $conversation->cached_label_list);
        $this->assertCount(2, $labels);
        $this->assertContains('urgent', $labels);
        $this->assertContains('support', $labels);
    }

    public function test_has_label_returns_true_when_present(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'cached_label_list' => 'urgent,support',
        ]);

        $this->assertTrue($this->service->hasLabel($conversation, 'urgent'));
        $this->assertTrue($this->service->hasLabel($conversation, 'support'));
    }

    public function test_has_label_returns_false_when_not_present(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'cached_label_list' => 'urgent,support',
        ]);

        $this->assertFalse($this->service->hasLabel($conversation, 'billing'));
    }

    public function test_has_label_returns_false_for_empty_list(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'cached_label_list' => null,
        ]);

        $this->assertFalse($this->service->hasLabel($conversation, 'urgent'));
    }
}
