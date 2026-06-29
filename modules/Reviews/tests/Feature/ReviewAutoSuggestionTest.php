<?php

namespace Modules\Reviews\Tests\Feature;

use App\Models\User;
use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewAutoSuggestion;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewReplyTemplate;
use Modules\Reviews\Tests\TestCase;

class ReviewAutoSuggestionTest extends TestCase
{
    protected User $user;

    protected ReviewGoogleLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser(['reviews.reviews.view']);
        $this->location = $this->createLocation($this->createConnection($this->user));
    }

    public function test_suggests_positive_template_for_high_rating_with_positive_keywords(): void
    {
        $positiveTemplate = ReviewReplyTemplate::factory()->create([
            'name' => 'Positive Response',
            'body' => 'Thank you {reviewer_name}!',
            'category' => 'positive',
            'is_active' => true,
        ]);

        ReviewAutoSuggestion::create([
            'trigger_keywords' => ['excellent', 'great', 'amazing'],
            'rating_range' => '4-5',
            'suggested_template_id' => $positiveTemplate->id,
            'priority' => 100,
            'is_active' => true,
        ]);

        $review = Review::factory()->create([
            'location_id' => $this->location->id,
            'star_rating' => ReviewRating::FIVE,
            'comment' => 'This was an excellent service!',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reviews/{$review->id}/suggestions");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'review_id',
                    'star_rating',
                    'has_comment',
                    'suggestions' => [
                        '*' => [
                            'template_id',
                            'template_name',
                            'template_body',
                            'category',
                            'relevance_score',
                            'matched_keywords',
                            'usage_count',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.suggestions.0.template_id', $positiveTemplate->id)
            ->assertJsonPath('data.suggestions.0.matched_keywords', ['excellent']);
    }

    public function test_suggests_negative_template_for_low_rating(): void
    {
        $negativeTemplate = ReviewReplyTemplate::factory()->create([
            'name' => 'Apology Response',
            'body' => 'We apologize {reviewer_name}',
            'category' => 'negative',
            'is_active' => true,
        ]);

        ReviewAutoSuggestion::create([
            'trigger_keywords' => ['terrible', 'bad', 'worst'],
            'rating_range' => '1-2',
            'suggested_template_id' => $negativeTemplate->id,
            'priority' => 100,
            'is_active' => true,
        ]);

        $review = Review::factory()->create([
            'location_id' => $this->location->id,
            'star_rating' => ReviewRating::ONE,
            'comment' => 'This was terrible service!',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reviews/{$review->id}/suggestions");

        $response->assertOk()
            ->assertJsonPath('data.suggestions.0.template_id', $negativeTemplate->id)
            ->assertJsonPath('data.suggestions.0.category', 'negative');
    }

    public function test_suggests_neutral_template_for_mid_rating(): void
    {
        $neutralTemplate = ReviewReplyTemplate::factory()->create([
            'name' => 'Neutral Response',
            'body' => 'Thank you for your feedback',
            'category' => 'neutral',
            'is_active' => true,
        ]);

        ReviewAutoSuggestion::create([
            'trigger_keywords' => [],
            'rating_range' => '3',
            'suggested_template_id' => $neutralTemplate->id,
            'priority' => 50,
            'is_active' => true,
        ]);

        $review = Review::factory()->create([
            'location_id' => $this->location->id,
            'star_rating' => ReviewRating::THREE,
            'comment' => 'It was okay',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reviews/{$review->id}/suggestions");

        $response->assertOk()
            ->assertJsonPath('data.suggestions.0.template_id', $neutralTemplate->id);
    }

    public function test_orders_suggestions_by_relevance_score(): void
    {
        $template1 = ReviewReplyTemplate::factory()->create([
            'name' => 'High Priority',
            'category' => 'positive',
            'is_active' => true,
        ]);

        $template2 = ReviewReplyTemplate::factory()->create([
            'name' => 'Low Priority',
            'category' => 'positive',
            'is_active' => true,
        ]);

        ReviewAutoSuggestion::create([
            'trigger_keywords' => ['excellent'],
            'rating_range' => '4-5',
            'suggested_template_id' => $template1->id,
            'priority' => 100,
            'is_active' => true,
        ]);

        ReviewAutoSuggestion::create([
            'trigger_keywords' => [],
            'rating_range' => '4-5',
            'suggested_template_id' => $template2->id,
            'priority' => 10,
            'is_active' => true,
        ]);

        $review = Review::factory()->create([
            'location_id' => $this->location->id,
            'star_rating' => ReviewRating::FIVE,
            'comment' => 'This was excellent!',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reviews/{$review->id}/suggestions");

        $response->assertOk();
        $suggestions = $response->json('data.suggestions');

        $this->assertGreaterThan(
            $suggestions[1]['relevance_score'] ?? 0,
            $suggestions[0]['relevance_score']
        );
    }

    public function test_ignores_inactive_suggestions(): void
    {
        $activeTemplate = ReviewReplyTemplate::factory()->create([
            'category' => 'positive',
            'is_active' => true,
        ]);

        $inactiveTemplate = ReviewReplyTemplate::factory()->create([
            'category' => 'positive',
            'is_active' => true,
        ]);

        ReviewAutoSuggestion::create([
            'trigger_keywords' => ['great'],
            'rating_range' => '4-5',
            'suggested_template_id' => $activeTemplate->id,
            'priority' => 100,
            'is_active' => true,
        ]);

        ReviewAutoSuggestion::create([
            'trigger_keywords' => ['great'],
            'rating_range' => '4-5',
            'suggested_template_id' => $inactiveTemplate->id,
            'priority' => 100,
            'is_active' => false,
        ]);

        $review = Review::factory()->create([
            'location_id' => $this->location->id,
            'star_rating' => ReviewRating::FIVE,
            'comment' => 'Great service!',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reviews/{$review->id}/suggestions");

        $response->assertOk();
        $suggestions = $response->json('data.suggestions');

        $this->assertCount(1, $suggestions);
        $this->assertEquals($activeTemplate->id, $suggestions[0]['template_id']);
    }

    public function test_requires_authentication(): void
    {
        $review = Review::factory()->create([
            'location_id' => $this->location->id,
        ]);

        $response = $this->getJson("/api/reviews/{$review->id}/suggestions");

        $response->assertUnauthorized();
    }

    public function test_requires_permission(): void
    {
        $userWithoutPermission = User::factory()->create();

        $review = Review::factory()->create([
            'location_id' => $this->location->id,
        ]);

        $response = $this->actingAs($userWithoutPermission, 'sanctum')
            ->getJson("/api/reviews/{$review->id}/suggestions");

        $response->assertForbidden();
    }

    public function test_fallback_to_category_based_templates_when_no_suggestions_match(): void
    {
        ReviewReplyTemplate::factory()->create([
            'name' => 'Generic Positive',
            'category' => 'positive',
            'is_active' => true,
            'usage_count' => 10,
        ]);

        $review = Review::factory()->create([
            'location_id' => $this->location->id,
            'star_rating' => ReviewRating::FIVE,
            'comment' => null,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reviews/{$review->id}/suggestions");

        $response->assertOk()
            ->assertJsonPath('data.suggestions.0.category', 'positive');
    }
}
