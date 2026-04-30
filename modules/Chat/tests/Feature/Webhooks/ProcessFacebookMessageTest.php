<?php

namespace Modules\Chat\Tests\Feature\Webhooks;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Chat\Jobs\Webhooks\ProcessFacebookMessageJob;
use Modules\Chat\Models\Channels\Facebook;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Inbox\Inbox;
use Tests\TestCase;

class ProcessFacebookMessageTest extends TestCase
{
    protected Facebook $facebookPage;

    protected Inbox $inbox;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        // Create test Facebook page directly
        $this->facebookPage = Facebook::create([
            'account_id' => 1,
            'page_id' => '109442377559389_test_'.time(),
            'page_name' => 'Test Page '.time(),
            'page_access_token' => 'test_token_'.time(),
        ]);

        // Create test inbox directly
        $this->inbox = Inbox::create([
            'account_id' => 1,
            'channel_id' => $this->facebookPage->id,
            'channel_type' => Facebook::class,
            'name' => 'Facebook Test Inbox',
            'timezone' => 'UTC',
        ]);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_can_process_facebook_message()
    {
        // Mock Facebook API response - match any query string
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'graph.facebook.com/v24.0/123456789')) {
                return Http::response([
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    'profile_pic' => 'https://example.com/avatar.jpg',
                ]);
            }

            return Http::response([], 404);
        });

        $event = [
            'sender' => ['id' => '123456789'],
            'recipient' => ['id' => $this->facebookPage->page_id],
            'message' => [
                'mid' => 'msg_12345',
                'text' => 'Hello, this is a test message',
            ],
        ];

        try {
            $job = new ProcessFacebookMessageJob($this->facebookPage, $event);
            $job->handle();
        } catch (\Exception $e) {
            $this->fail('Job failed: '.$e->getMessage()."\n".$e->getTraceAsString());
        }

        // Verify customer was created
        $customer = Customer::where('account_id', 1)
            ->where('identifier', 'facebook_123456789')
            ->first();
        $this->assertNotNull($customer);
        $this->assertEquals('John Doe', $customer->name);

        // Verify conversation was created
        $conversation = Conversation::where('account_id', 1)
            ->where('customer_id', $customer->id)
            ->where('inbox_id', $this->inbox->id)
            ->first();
        $this->assertNotNull($conversation);

        // Verify message was created
        $this->assertTrue(
            $conversation->messages()
                ->where('content', 'Hello, this is a test message')
                ->exists()
        );
    }

    public function test_reuses_existing_customer()
    {
        $customer = Customer::create([
            'account_id' => 1,
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'identifier' => 'facebook_987654321',
        ]);

        Http::fake([
            'https://graph.facebook.com/v24.0/987654321' => Http::response([
                'first_name' => 'Jane',
            ]),
        ]);

        $event = [
            'sender' => ['id' => '987654321'],
            'recipient' => ['id' => $this->facebookPage->page_id],
            'message' => [
                'mid' => 'msg_67890',
                'text' => 'Test message',
            ],
        ];

        $job = new ProcessFacebookMessageJob($this->facebookPage, $event);
        $job->handle();

        // Verify customer was not duplicated
        $count = Customer::where('account_id', 1)
            ->where('identifier', 'facebook_987654321')
            ->count();
        $this->assertEquals(1, $count);
    }

    public function test_reuses_existing_conversation()
    {
        $customer = Customer::create([
            'account_id' => 1,
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'identifier' => 'facebook_555666777',
        ]);

        $conversation = Conversation::create([
            'account_id' => 1,
            'customer_id' => $customer->id,
            'inbox_id' => $this->inbox->id,
            'subject' => 'Test Conversation',
            'status_id' => 1,
        ]);

        Http::fake([
            'https://graph.facebook.com/v24.0/555666777' => Http::response([
                'first_name' => 'John',
            ]),
        ]);

        $event = [
            'sender' => ['id' => '555666777'],
            'recipient' => ['id' => $this->facebookPage->page_id],
            'message' => [
                'mid' => 'msg_99999',
                'text' => 'Follow-up message',
            ],
        ];

        $job = new ProcessFacebookMessageJob($this->facebookPage, $event);
        $job->handle();

        // Verify conversation was not duplicated
        $count = Conversation::where('account_id', 1)
            ->where('customer_id', $customer->id)
            ->where('inbox_id', $this->inbox->id)
            ->count();
        $this->assertEquals(1, $count);

        // Verify message was added
        $messages = $conversation->fresh()->messages()->count();
        $this->assertEquals(1, $messages);
    }
}
