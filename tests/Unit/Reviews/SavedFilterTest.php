<?php

namespace Tests\Unit\Reviews;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewSavedFilter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SavedFilterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_saved_filter(): void
    {
        $user = User::factory()->create();

        $filter = ReviewSavedFilter::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Filter',
        ]);

        $this->assertInstanceOf(ReviewSavedFilter::class, $filter);
        $this->assertEquals('Test Filter', $filter->name);
        $this->assertEquals($user->id, $filter->user_id);
    }

    #[Test]
    public function it_can_check_if_filter_has_specific_key(): void
    {
        $filter = ReviewSavedFilter::factory()->create([
            'filters_json' => [
                'star_rating' => ['FOUR', 'FIVE'],
                'has_comment' => true,
            ],
        ]);

        $this->assertTrue($filter->hasFilter('star_rating'));
        $this->assertTrue($filter->hasFilter('has_comment'));
        $this->assertFalse($filter->hasFilter('location_id'));
    }

    #[Test]
    public function it_can_get_filter_value_with_default(): void
    {
        $filter = ReviewSavedFilter::factory()->create([
            'filters_json' => [
                'star_rating' => ['FOUR', 'FIVE'],
            ],
        ]);

        $this->assertEquals(['FOUR', 'FIVE'], $filter->getFilter('star_rating'));
        $this->assertEquals('default_value', $filter->getFilter('non_existent', 'default_value'));
    }

    #[Test]
    public function it_sets_only_one_filter_as_default_per_user(): void
    {
        $user = User::factory()->create();

        $filter1 = ReviewSavedFilter::factory()->create([
            'user_id' => $user->id,
            'is_default' => true,
        ]);

        $filter2 = ReviewSavedFilter::factory()->create([
            'user_id' => $user->id,
            'is_default' => false,
        ]);

        // Set filter2 as default
        $filter2->setAsDefault();

        // Refresh both
        $filter1->refresh();
        $filter2->refresh();

        $this->assertFalse($filter1->is_default);
        $this->assertTrue($filter2->is_default);

        // Verify only one default exists for this user
        $defaultCount = ReviewSavedFilter::query()
            ->forUser($user->id)
            ->defaults()
            ->count();

        $this->assertEquals(1, $defaultCount);
    }

    #[Test]
    public function it_applies_star_rating_filter_to_query(): void
    {
        $filter = ReviewSavedFilter::factory()->create([
            'filters_json' => [
                'star_rating' => ['FOUR', 'FIVE'],
            ],
        ]);

        $query = Review::query();
        $filter->applyToQuery($query);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('star_rating', $sql);
        $this->assertStringContainsString('in', strtolower($sql));
        $this->assertEquals(['FOUR', 'FIVE'], $bindings);
    }

    #[Test]
    public function it_applies_has_comment_filter_to_query(): void
    {
        $filter = ReviewSavedFilter::factory()->create([
            'filters_json' => [
                'has_comment' => true,
            ],
        ]);

        $query = Review::query();
        $filter->applyToQuery($query);

        $sql = $query->toSql();
        $this->assertStringContainsString('comment', $sql);
        $this->assertStringContainsString('is not null', strtolower($sql));
    }

    #[Test]
    public function it_applies_date_range_filter_to_query(): void
    {
        $filter = ReviewSavedFilter::factory()->create([
            'filters_json' => [
                'date_from' => '2026-01-01',
                'date_to' => '2026-01-31',
            ],
        ]);

        $query = Review::query();
        $filter->applyToQuery($query);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('review_time', $sql);
        $this->assertContains('2026-01-01', $bindings);
        $this->assertContains('2026-01-31', $bindings);
    }

    #[Test]
    public function it_scopes_filters_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        ReviewSavedFilter::factory()->count(3)->create(['user_id' => $user1->id]);
        ReviewSavedFilter::factory()->count(2)->create(['user_id' => $user2->id]);

        $user1Filters = ReviewSavedFilter::query()->forUser($user1->id)->get();
        $user2Filters = ReviewSavedFilter::query()->forUser($user2->id)->get();

        $this->assertCount(3, $user1Filters);
        $this->assertCount(2, $user2Filters);
    }

    #[Test]
    public function it_applies_multiple_filters_to_query(): void
    {
        $filter = ReviewSavedFilter::factory()->create([
            'filters_json' => [
                'star_rating' => ['FOUR', 'FIVE'],
                'has_comment' => true,
                'date_from' => '2026-01-01',
            ],
        ]);

        $query = Review::query();
        $filter->applyToQuery($query);

        $sql = $query->toSql();

        $this->assertStringContainsString('star_rating', $sql);
        $this->assertStringContainsString('comment', $sql);
        $this->assertStringContainsString('review_time', $sql);
    }
}
