<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Modules\Helpdesk\Models\CannedReply;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

class CannedRepliesSearchScopingTest extends HelpdeskTestCase
{
    private function agent(): User
    {
        $agent = User::factory()->create();
        $agent->givePermissionTo(['helpdesk.view', 'helpdesk.canned-replies.view']);

        return $agent;
    }

    public function test_search_hides_other_agents_private_replies(): void
    {
        $agent = $this->agent();
        $other = User::factory()->create();

        CannedReply::factory()->private($agent->id)->create(['title' => 'zzq mine']);
        CannedReply::factory()->private($other->id)->create(['title' => 'zzq theirs']);
        CannedReply::factory()->global()->create(['user_id' => $other->id, 'title' => 'zzq shared']);

        $names = collect(
            $this->actingAs($agent)->getJson(route('manager.helpdesk.canned-replies.search', ['q' => 'zzq']))
                ->assertOk()->json()
        )->pluck('name')->all();

        $this->assertContains('zzq mine', $names);
        $this->assertContains('zzq shared', $names);
        $this->assertNotContains('zzq theirs', $names);
    }

    public function test_like_wildcards_are_escaped(): void
    {
        $agent = $this->agent();

        CannedReply::factory()->private($agent->id)->create(['title' => 'abc', 'body' => 'x', 'shortcut' => null]);
        CannedReply::factory()->private($agent->id)->create(['title' => '100% done', 'body' => 'x', 'shortcut' => null]);

        $names = collect(
            $this->actingAs($agent)->getJson(route('manager.helpdesk.canned-replies.search', ['q' => '%']))
                ->assertOk()->json()
        )->pluck('name')->all();

        $this->assertSame(['100% done'], $names);
    }
}
