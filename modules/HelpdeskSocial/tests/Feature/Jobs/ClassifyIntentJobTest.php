<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Contracts\IntentClassifierInterface;
use Modules\HelpdeskSocial\Jobs\ClassifyIntentJob;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Services\IntentClassificationService;
use Modules\HelpdeskSocial\Tests\TestCase;

class ClassifyIntentJobTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(IntentClassifierInterface::class, function () {
            return new class implements IntentClassifierInterface
            {
                public function getIdentifier(): string
                {
                    return 'test';
                }

                public function classify(SocialComment $comment): array
                {
                    return [
                        'intent' => 'question',
                        'confidence' => 0.95,
                        'classifier' => 'test',
                        'urgency' => 'high',
                        'keywords_matched' => ['help', 'question'],
                        'entities' => [],
                        'raw_response' => [],
                    ];
                }

                public function isAvailable(): bool
                {
                    return true;
                }

                public function getConfidenceThreshold(): float
                {
                    return 0.5;
                }
            };
        });
    }

    public function test_classifies_comment_intent(): void
    {
        $comment = SocialComment::factory()->create([
            'intent' => null,
            'intent_confidence' => null,
            'urgency' => null,
        ]);

        $job = new ClassifyIntentJob($comment->id);
        $job->handle(app(IntentClassificationService::class));

        $comment->refresh();

        $this->assertSame('question', $comment->intent);
        $this->assertSame(0.95, (float) $comment->intent_confidence);
        $this->assertSame('high', $comment->urgency);

        $this->assertDatabaseHas('helpdesk_social_intents', [
            'classifiable_type' => SocialComment::class,
            'classifiable_id' => $comment->id,
            'intent' => 'question',
            'confidence' => 0.95,
        ]);
    }

    public function test_skips_missing_comment(): void
    {
        $nonExistentId = 999999;

        $job = new ClassifyIntentJob($nonExistentId);
        $job->handle(app(IntentClassificationService::class));

        $this->assertDatabaseMissing('helpdesk_social_intents', [
            'classifiable_id' => $nonExistentId,
        ]);
    }
}
