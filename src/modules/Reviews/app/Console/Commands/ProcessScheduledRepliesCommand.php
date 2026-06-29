<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Enums\ReplyStatus;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Services\ReviewReplyService;

class ProcessScheduledRepliesCommand extends Command
{
    protected $signature = 'reviews:process-scheduled';

    protected $description = 'Publicar respuestas programadas cuya fecha de publicacion ya ha llegado';

    public function __construct(private readonly ReviewReplyService $replyService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $replies = ReviewReply::query()
            ->where('status', ReplyStatus::SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($replies->isEmpty()) {
            $this->info('No hay respuestas programadas pendientes de publicar.');

            return self::SUCCESS;
        }

        $published = 0;
        $failed = 0;

        foreach ($replies as $reply) {
            try {
                $this->replyService->publish($reply, null);
                $published++;
            } catch (\Throwable $e) {
                $failed++;

                Log::error('reviews:process-scheduled failed to publish reply', [
                    'reply_id' => $reply->id,
                    'review_id' => $reply->review_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Published {$published} scheduled replies, {$failed} failed.");

        Log::info('reviews:process-scheduled completed', [
            'published' => $published,
            'failed' => $failed,
        ]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
