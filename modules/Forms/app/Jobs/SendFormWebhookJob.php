<?php

namespace Modules\Forms\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Services\FormWebhookService;

class SendFormWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $timeout = 30;

    public int $maxExceptions = 4;

    /** @return array<int,int> backoff exponencial en segundos */
    public function backoff(): array
    {
        return [30, 120, 300, 900];
    }

    public function __construct(
        protected int $formId,
        protected int $submissionId,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(FormWebhookService $webhookService): void
    {
        $form = Form::find($this->formId);
        $submission = FormSubmission::with('values')->find($this->submissionId);

        if (! $form || ! $submission) {
            return;
        }

        $success = $webhookService->send($form, $submission);

        if (! $success) {
            throw new \RuntimeException("Webhook failed for form {$this->formId}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendFormWebhookJob failed permanently', [
            'form_id' => $this->formId,
            'submission_id' => $this->submissionId,
            'error' => $exception->getMessage(),
        ]);
    }
}
