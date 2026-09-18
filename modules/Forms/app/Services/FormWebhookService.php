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

        $start = microtime(true);
        $context = [
            'form_id' => $form->id,
            'submission_id' => $submission->id,
            'webhook_url' => $form->webhook_url,
        ];

        try {
            $payload = $this->buildPayload($form, $submission);

            $pendingRequest = Http::timeout(15)->withHeaders([
                'Content-Type' => 'application/json',
                'X-Form-ID' => (string) $form->id,
                'X-Submission-ID' => (string) $submission->id,
                'User-Agent' => 'Forms-Webhook/1.0',
            ]);

            if ($form->webhook_secret) {
                $signature = hash_hmac('sha256', json_encode($payload), $form->webhook_secret);
                $pendingRequest = $pendingRequest->withHeaders(['X-Webhook-Signature' => "sha256={$signature}"]);
            }

            $response = $pendingRequest->post($form->webhook_url, $payload);
            $latencyMs = (int) round((microtime(true) - $start) * 1000);

            $context['status'] = $response->status();
            $context['latency_ms'] = $latencyMs;

            if ($response->failed()) {
                $context['response_body'] = mb_substr((string) $response->body(), 0, 500);
                Log::warning('Forms: webhook respuesta no-2xx', $context);

                return false;
            }

            Log::info('Forms: webhook ok', $context);

            return true;
        } catch (\Throwable $e) {
            $context['latency_ms'] = (int) round((microtime(true) - $start) * 1000);
            $context['error'] = $e->getMessage();
            Log::error('Forms: webhook exception', $context);

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
