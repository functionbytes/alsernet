<?php

namespace Modules\Reviews\Tests\Feature\E2E;

use Illuminate\Support\Facades\Queue;
use Modules\Reviews\Enums\ReplyStatus;
use Modules\Reviews\Events\ReviewSynced;
use Modules\Reviews\Jobs\ApplyAutoReplyJob;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewAutoReplyRule;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Models\ReviewReplyTemplate;
use Modules\Reviews\Services\AutoReplyService;
use Modules\Reviews\Tests\TestCase;

class AutoReplyRuleTest extends TestCase
{
    private AutoReplyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AutoReplyService::class);
    }

    // -------------------------------------------------------------------------
    // Auto-reply rule applies correctly
    // -------------------------------------------------------------------------

    public function test_auto_reply_rule_applies_immediately_when_delay_is_zero(): void
    {
        $this->fakeGoogleReplySuccess();

        $location = $this->createLocation();

        $template = ReviewReplyTemplate::factory()->create([
            'body' => 'Gracias por tu opinion de 5 estrellas!',
        ]);

        ReviewAutoReplyRule::query()->create([
            'location_id' => $location->id,
            'name' => 'Respuesta automatica 5 estrellas',
            'is_active' => true,
            'conditions' => ['min_rating' => 5, 'max_rating' => 5, 'has_comment' => false],
            'template_id' => $template->id,
            'custom_text' => null,
            'delay_minutes' => 0,
        ]);

        $review = Review::factory()->fiveStars()->noComment()->for($location, 'location')->create();

        $this->service->processReview($review);

        $this->assertDatabaseHas('review_replies', [
            'review_id' => $review->id,
            'status' => ReplyStatus::PUBLISHED->value,
        ]);
    }

    public function test_auto_reply_rule_queues_job_when_delay_is_set(): void
    {
        Queue::fake();

        $location = $this->createLocation();

        ReviewAutoReplyRule::query()->create([
            'location_id' => $location->id,
            'name' => 'Respuesta diferida',
            'is_active' => true,
            'conditions' => ['min_rating' => 5, 'max_rating' => 5],
            'template_id' => null,
            'custom_text' => 'Gracias por su visita!',
            'delay_minutes' => 30,
        ]);

        $review = Review::factory()->fiveStars()->for($location, 'location')->create();

        $this->service->processReview($review);

        Queue::assertPushed(ApplyAutoReplyJob::class, function (ApplyAutoReplyJob $job) use ($review) {
            return $job->review->is($review);
        });
    }

    public function test_auto_reply_rule_respects_review_synced_event(): void
    {
        $this->fakeGoogleReplySuccess();

        $location = $this->createLocation();

        ReviewAutoReplyRule::query()->create([
            'location_id' => $location->id,
            'name' => 'Regla evento synced',
            'is_active' => true,
            'conditions' => ['min_rating' => 4, 'max_rating' => 5],
            'template_id' => null,
            'custom_text' => 'Muchas gracias por su valoracion!',
            'delay_minutes' => 0,
        ]);

        $review = Review::factory()->fiveStars()->for($location, 'location')->create();

        // Simulate the ReviewSynced event being dispatched (as happens after sync)
        event(new ReviewSynced($review));

        // The ProcessAutoReplies listener calls processReview() → publishes reply
        $this->assertDatabaseHas('review_replies', [
            'review_id' => $review->id,
            'status' => ReplyStatus::PUBLISHED->value,
        ]);
    }

    // -------------------------------------------------------------------------
    // Auto-reply respects conditions
    // -------------------------------------------------------------------------

    public function test_rule_with_min_rating_5_does_not_apply_to_4_star_review(): void
    {
        $location = $this->createLocation();

        ReviewAutoReplyRule::query()->create([
            'location_id' => $location->id,
            'name' => 'Solo 5 estrellas',
            'is_active' => true,
            'conditions' => ['min_rating' => 5, 'max_rating' => 5],
            'template_id' => null,
            'custom_text' => 'Excelente!',
            'delay_minutes' => 0,
        ]);

        $review = Review::factory()->fourStars()->for($location, 'location')->create();

        $this->service->processReview($review);

        $this->assertDatabaseMissing('review_replies', ['review_id' => $review->id]);
    }

    public function test_rule_with_min_rating_5_applies_to_5_star_review(): void
    {
        $this->fakeGoogleReplySuccess();

        $location = $this->createLocation();

        ReviewAutoReplyRule::query()->create([
            'location_id' => $location->id,
            'name' => 'Solo 5 estrellas',
            'is_active' => true,
            'conditions' => ['min_rating' => 5, 'max_rating' => 5],
            'template_id' => null,
            'custom_text' => 'Excelente valoracion, muchas gracias!',
            'delay_minutes' => 0,
        ]);

        $review = Review::factory()->fiveStars()->for($location, 'location')->create();

        $this->service->processReview($review);

        $this->assertDatabaseHas('review_replies', [
            'review_id' => $review->id,
            'status' => ReplyStatus::PUBLISHED->value,
        ]);
    }

    public function test_rule_with_has_comment_condition_applies_when_comment_present(): void
    {
        $this->fakeGoogleReplySuccess();

        $location = $this->createLocation();

        ReviewAutoReplyRule::query()->create([
            'location_id' => $location->id,
            'name' => 'Con comentario',
            'is_active' => true,
            'conditions' => ['has_comment' => true],
            'template_id' => null,
            'custom_text' => 'Gracias por compartir tu experiencia!',
            'delay_minutes' => 0,
        ]);

        $reviewWithComment = Review::factory()->fiveStars()->for($location, 'location')->create([
            'comment' => 'Gran servicio!',
        ]);
        $reviewWithoutComment = Review::factory()->fiveStars()->noComment()->for($location, 'location')->create();

        $this->service->processReview($reviewWithComment);
        $this->service->processReview($reviewWithoutComment);

        $this->assertDatabaseHas('review_replies', ['review_id' => $reviewWithComment->id]);
        $this->assertDatabaseMissing('review_replies', ['review_id' => $reviewWithoutComment->id]);
    }

    public function test_inactive_rule_is_not_applied(): void
    {
        $location = $this->createLocation();

        ReviewAutoReplyRule::query()->create([
            'location_id' => $location->id,
            'name' => 'Regla inactiva',
            'is_active' => false,
            'conditions' => ['min_rating' => 1],
            'template_id' => null,
            'custom_text' => 'Gracias!',
            'delay_minutes' => 0,
        ]);

        $review = Review::factory()->fiveStars()->for($location, 'location')->create();

        $this->service->processReview($review);

        $this->assertDatabaseMissing('review_replies', ['review_id' => $review->id]);
    }

    public function test_rule_does_not_reply_if_review_already_has_google_reply(): void
    {
        $location = $this->createLocation();

        ReviewAutoReplyRule::query()->create([
            'location_id' => $location->id,
            'name' => 'Regla activa',
            'is_active' => true,
            'conditions' => [],
            'template_id' => null,
            'custom_text' => 'Respuesta automatica',
            'delay_minutes' => 0,
        ]);

        $review = Review::factory()->fiveStars()->withGoogleReply()->for($location, 'location')->create();

        $this->service->processReview($review);

        // No new reply should have been created (review already replied on Google)
        $this->assertDatabaseMissing('review_replies', ['review_id' => $review->id]);
    }

    public function test_first_matching_rule_wins(): void
    {
        $this->fakeGoogleReplySuccess();

        $location = $this->createLocation();

        ReviewAutoReplyRule::query()->create([
            'location_id' => $location->id,
            'name' => 'Regla 1 — primera',
            'is_active' => true,
            'conditions' => ['min_rating' => 5, 'max_rating' => 5],
            'template_id' => null,
            'custom_text' => 'Primera respuesta',
            'delay_minutes' => 0,
        ]);

        ReviewAutoReplyRule::query()->create([
            'location_id' => $location->id,
            'name' => 'Regla 2 — segunda',
            'is_active' => true,
            'conditions' => ['min_rating' => 5, 'max_rating' => 5],
            'template_id' => null,
            'custom_text' => 'Segunda respuesta',
            'delay_minutes' => 0,
        ]);

        $review = Review::factory()->fiveStars()->for($location, 'location')->create();

        $this->service->processReview($review);

        // Only one reply should exist
        $this->assertDatabaseCount('review_replies', 1);

        $reply = ReviewReply::query()->where('review_id', $review->id)->first();
        $this->assertSame('Primera respuesta', $reply->reply_text);
    }
}
