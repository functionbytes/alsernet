<?php

namespace Modules\Forms\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Services\FormEmailService;

class SendConditionalEmailsJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 10;

    public function __construct(
        public readonly Form $form,
        public readonly FormSubmission $submission,
    ) {
        $this->onQueue('emails');
    }

    public function handle(FormEmailService $service): void
    {
        $service->evaluateConditionalEmails($this->form, $this->submission);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendConditionalEmailsJob failed', [
            'form_id' => $this->form->id,
            'submission_id' => $this->submission->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
