<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\CampaignSubscriber;
use Tests\TestCase;

class DoubleOptInEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribers_to_send_excludes_unconfirmed(): void
    {
        $list = CampaignMaillist::forceCreate(['uid' => (string) Str::uuid(), 'name' => 'Test']);
        $campaign = Campaign::forceCreate([
            'name' => 'Test',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
        ]);
        $campaign->mailLists()->attach($list->id);

        $confirmed = CampaignSubscriber::create(['email' => 'confirmed@example.com', 'source' => 'test', 'confirmed_at' => now()]);
        $unconfirmed = CampaignSubscriber::create(['email' => 'unconfirmed@example.com', 'source' => 'test', 'confirmed_at' => null]);

        DB::table('campaign_maillists_subscribers')->insert([
            ['mail_list_id' => $list->id, 'subscriber_id' => $confirmed->id, 'status' => 'subscribed', 'uid' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()],
            ['mail_list_id' => $list->id, 'subscriber_id' => $unconfirmed->id, 'status' => 'subscribed', 'uid' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Sin config, ambos deberían aparecer
        $emails = $campaign->subscribersToSend()->pluck('email')->all();
        $this->assertContains('confirmed@example.com', $emails);
        $this->assertContains('unconfirmed@example.com', $emails);

        // Con double opt-in habilitado, solo el confirmed
        config(['campaign.double_opt_in' => true]);
        $emails = $campaign->subscribersToSend()->pluck('email')->all();
        $this->assertContains('confirmed@example.com', $emails);
        $this->assertNotContains('unconfirmed@example.com', $emails);
    }
}
