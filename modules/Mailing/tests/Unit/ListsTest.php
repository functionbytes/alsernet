<?php

namespace Modules\Mailing\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mailing\Models\Campaign;
use Modules\Mailing\Models\Lists;
use Modules\Mailing\Models\Subscriber;
use Tests\TestCase;

class ListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_list(): void
    {
        $list = Lists::factory()->create([
            'name' => 'Newsletter Subscribers',
            'description' => 'Main newsletter list',
        ]);

        $this->assertDatabaseHas('mailing_lists', [
            'name' => 'Newsletter Subscribers',
            'description' => 'Main newsletter list',
        ]);

        $this->assertEquals('Newsletter Subscribers', $list->name);
    }

    public function test_list_has_subscribers_relationship(): void
    {
        $list = Lists::factory()->create();
        $subscribers = Subscriber::factory()->count(3)->create([
            'list_id' => $list->id,
        ]);

        $this->assertCount(3, $list->subscribers);
        $this->assertInstanceOf(Subscriber::class, $list->subscribers->first());
    }

    public function test_list_has_campaigns_relationship(): void
    {
        $list = Lists::factory()->create();
        $campaigns = Campaign::factory()->count(2)->create([
            'list_id' => $list->id,
        ]);

        $this->assertCount(2, $list->campaigns);
        $this->assertInstanceOf(Campaign::class, $list->campaigns->first());
    }

    public function test_can_create_list_with_factory_states(): void
    {
        $withDescription = Lists::factory()->withDescription()->create();
        $this->assertNotEmpty($withDescription->description);
        $this->assertGreaterThan(50, strlen($withDescription->description));

        $purposeList = Lists::factory()->forPurpose('newsletter')->create();
        $this->assertEquals('Newsletter List', $purposeList->name);
        $this->assertStringContainsString('newsletter', $purposeList->description);
    }

    public function test_list_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Lists::create([
            'description' => 'Test description',
        ]);
    }

    public function test_can_update_list(): void
    {
        $list = Lists::factory()->create(['name' => 'Old Name']);

        $list->update(['name' => 'New Name']);

        $this->assertEquals('New Name', $list->fresh()->name);
        $this->assertDatabaseHas('mailing_lists', [
            'id' => $list->id,
            'name' => 'New Name',
        ]);
    }

    public function test_can_delete_list(): void
    {
        $list = Lists::factory()->create();
        $listId = $list->id;

        $list->delete();

        $this->assertDatabaseMissing('mailing_lists', ['id' => $listId]);
    }

    public function test_list_with_subscribers_count(): void
    {
        $list = Lists::factory()->create();
        Subscriber::factory()->count(5)->create(['list_id' => $list->id]);

        $listWithCount = Lists::withCount('subscribers')->find($list->id);

        $this->assertEquals(5, $listWithCount->subscribers_count);
    }

    public function test_can_create_multiple_lists(): void
    {
        $lists = Lists::factory()->count(10)->create();

        $this->assertCount(10, $lists);
        $this->assertCount(10, Lists::all());
    }
}
