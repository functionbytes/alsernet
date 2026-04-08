<?php

namespace Modules\Forms\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Forms\Jobs\SendFormWebhookJob;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;

class FormWebhookService
{
    /**
     * Despacha el webhook de forma asíncrona via job.
     */
    public function dispatch(Form $form, FormSubmission $submission): void
    {
        if (empty($form->webhook_url)) {
            return;
        }

        SendFormWebhookJob::dispatch($form->id, $submission->id);
    }

    /**
     * Envía el webhook de forma síncrona (usado desde el job).
     */
    public function send(Form $form, FormSubmission $submission): bool
    {
        if (empty($form->webhook_url)) {
            return false;
        }

        try {
            $payload = $this->buildPayload($form, $submission);

            $pendingRequest = Http::timeout(15)->withHeaders([
                'Content-Type' => 'application/json',
                'X-Form-ID' => (string) $form->id,
                'X-Submission-ID' => (string) $submission->id,
            ]);

            if ($form->webhook_secret) {
                $signature = hash_hmac('sha256', json_encode($payload), $form->webhook_secret);
                $pendingRequest = $pendingRequest->withHeaders(['X-Webhook-Signature' => "sha256={$signature}"]);
            }

            $response = $pendingRequest->post($form->webhook_url, $payload);

            if ($response->failed()) {
                Log::warning("Forms webhook failed for form {$form->id}: HTTP {$response->status()}");

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("Forms webhook error for form {$form->id}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPayload(Form $form, FormSubmission $submission): array
    {
        $submission->loadMissing('values');

        $fields = [];
        foreach ($submission->values as $value) {
            $fields[$value->field_key] = $value->getDisplayValue();
        }

        return [
            'event' => 'form.submission',
            'form' => [
                'id' => $form->id,
                'name' => $form->name,
                'slug' => $form->slug,
            ],
            'submission' => [
                'id' => $submission->id,
                'status' => $submission->status,
                'submitted_at' => $submission->created_at->toIso8601String(),
                'ip_address' => $submission->ip_address,
                'country' => $submission->country,
                'utm_source' => $submission->utm_source,
                'utm_campaign' => $submission->utm_campaign,
            ],
            'fields' => $fields,
        ];
    }
}
