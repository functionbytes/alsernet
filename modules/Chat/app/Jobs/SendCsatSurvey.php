<?php

namespace Modules\Chat\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Chat\Mail\CsatSurveyMail;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Csat\CsatSurveyResponse;

class SendCsatSurvey implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(public Conversation $conversation) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Skip if conversation doesn't have contact email
        if (! $this->conversation->customer->email) {
            return;
        }

        // Skip if CSAT survey already exists for this conversation
        if ($this->conversation->csatSurvey()->exists()) {
            return;
        }

        // Create CSAT survey response record
        $survey = CsatSurveyResponse::create([
            'account_id' => $this->conversation->account_id,
            'conversation_id' => $this->conversation->id,
            'customer_id' => $this->conversation->customer_id,
            'assigned_agent_id' => $this->conversation->assignee_id,
            'survey_token' => Str::random(config('chat.csat.token_length')),
        ]);

        // Send email
        Mail::to($this->conversation->customer->email)
            ->send(new CsatSurveyMail($survey));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(static::class.' failed', [
            'conversation_id' => $this->conversation->id,
            'customer_id' => $this->conversation->customer_id,
            'error' => $exception->getMessage(),
        ]);
    }
}
