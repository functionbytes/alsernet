<?php

namespace Modules\HelpdeskChat\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\HelpdeskChat\Mail\CsatSurveyMail;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Csat\CsatSurveyResponse;

class SendCsatSurvey implements ShouldQueue
{
    use Queueable;

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
        if (! $this->conversation->contact->email) {
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
            'contact_id' => $this->conversation->contact_id,
            'assigned_agent_id' => $this->conversation->assignee_id,
            'survey_token' => Str::random(32),
        ]);

        // Send email
        Mail::to($this->conversation->contact->email)
            ->send(new CsatSurveyMail($survey));
    }
}
