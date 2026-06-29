<?php

namespace Modules\Reviews\Tests\Unit\Rules;

use Modules\Reviews\Enums\ReplyStatus;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Rules\UniquePublishedReply;
use Modules\Reviews\Tests\TestCase;

class UniquePublishedReplyTest extends TestCase
{
    public function test_passes_when_status_is_draft(): void
    {
        $review = Review::factory()->create();
        $rule = new UniquePublishedReply('draft');

        $this->assertTrue($rule->passes('review_id', $review->id));
    }

    public function test_passes_when_status_is_approved(): void
    {
        $review = Review::factory()->create();
        $rule = new UniquePublishedReply('approved');

        $this->assertTrue($rule->passes('review_id', $review->id));
    }

    public function test_passes_when_review_has_no_reply(): void
    {
        $review = Review::factory()->create();
        $rule = new UniquePublishedReply('published');

        $this->assertTrue($rule->passes('review_id', $review->id));
    }

    public function test_passes_when_review_has_draft_reply(): void
    {
        $review = Review::factory()->create();
        ReviewReply::factory()->create([
            'review_id' => $review->id,
            'status' => ReplyStatus::DRAFT,
        ]);

        $rule = new UniquePublishedReply('published');

        $this->assertTrue($rule->passes('review_id', $review->id));
    }

    public function test_passes_when_review_has_approved_reply(): void
    {
        $review = Review::factory()->create();
        ReviewReply::factory()->create([
            'review_id' => $review->id,
            'status' => ReplyStatus::APPROVED,
        ]);

        $rule = new UniquePublishedReply('published');

        $this->assertTrue($rule->passes('review_id', $review->id));
    }

    public function test_fails_when_review_already_has_published_reply(): void
    {
        $review = Review::factory()->create();
        ReviewReply::factory()->create([
            'review_id' => $review->id,
            'status' => ReplyStatus::PUBLISHED,
        ]);

        $rule = new UniquePublishedReply('published');

        $this->assertFalse($rule->passes('review_id', $review->id));
    }

    public function test_passes_when_review_does_not_exist(): void
    {
        $rule = new UniquePublishedReply('published');

        $this->assertTrue($rule->passes('review_id', 99999));
    }

    public function test_returns_correct_error_message(): void
    {
        $rule = new UniquePublishedReply('published');

        $this->assertEquals(
            'Esta reseña ya tiene una respuesta publicada',
            $rule->message()
        );
    }
}
