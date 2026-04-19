<?php

namespace Modules\Mailer\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Mailer\Enums\EndpointLogStatus;
use Modules\Mailer\Models\MailerEndpoint;
use Modules\Mailer\Models\MailerEndpointLog;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Services\MailerTemplateRendererService;

class SendEndpointEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var array<int> */
    public array $backoff = [60, 120, 300];

    private const MAX_PAYLOAD_BYTES = 262144;      // 256 KB

    private const MAX_SUBJECT_LENGTH = 255;

    private const MAX_BODY_BYTES = 5242880;        // 5 MB

    protected MailerEndpointLog $log;

    /**
     * Create a new job instance
     */
    public function __construct(MailerEndpointLog $log)
    {
        $this->log = $log;
        $this->onQueue((string) config('mailer-module.queue', 'emails'));
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        // Correlacionar el log con el ID del job para cruzarlo con failed_jobs
        $jobId = $this->job?->getJobId();
        if ($jobId && $this->log->job_id !== $jobId) {
            $this->log->update(['job_id' => $jobId]);
        }

        $payloadMax = (int) config('mailer-module.limits.payload_max_bytes', self::MAX_PAYLOAD_BYTES);
        $subjectMax = (int) config('mailer-module.limits.subject_max_length', self::MAX_SUBJECT_LENGTH);
        $bodyMax = (int) config('mailer-module.limits.body_max_bytes', self::MAX_BODY_BYTES);

        try {
            $endpoint = $this->log->endpoint;
            $payload = $this->log->payload;

            if (! $endpoint->is_active) {
                throw new \Exception('Endpoint is inactive');
            }

            if (! is_array($payload)) {
                throw new \Exception('Payload must be a JSON object');
            }

            if (strlen((string) json_encode($payload)) > $payloadMax) {
                throw new \Exception('Payload size exceeds '.$payloadMax.' bytes');
            }

            $template = $endpoint->template;
            if (! $template) {
                throw new \Exception('No template configured for this endpoint');
            }

            $variables = $this->mapVariables($payload, $endpoint);

            $recipientEmail = $variables['email'] ?? $variables['customer_email'] ?? null;
            if (! $recipientEmail || ! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('No valid recipient email found in payload');
            }

            // Resolve language: usa el configurado en el endpoint o cae al default del sistema
            $langId = $endpoint->lang_id ?? MailerLang::resolveDefaultId();

            $translation = $template->translate($langId);

            // Use centralized renderer service for variable replacement
            $subject = MailerTemplateRendererService::replaceVariables(
                $translation?->subject ?? $template->name,
                $variables
            );

            if (mb_strlen($subject) > $subjectMax) {
                $subject = mb_substr($subject, 0, $subjectMax);
            }

            $body = MailerTemplateRendererService::renderEmailTemplate(
                $template, $variables, $langId
            );

            if (strlen($body) > $bodyMax) {
                throw new \Exception('Rendered body exceeds '.$bodyMax.' bytes');
            }

            $plainText = MailerTemplateRendererService::htmlToPlainText($body);

            Mail::html($body, function ($message) use ($recipientEmail, $subject, $plainText) {
                $message->to($recipientEmail)
                    ->subject($subject)
                    ->text($plainText);
            });

            $this->log->update([
                'status' => EndpointLogStatus::Success,
                'sent_at' => now(),
                'recipient_email' => $recipientEmail,
                'mailer_subject' => $subject,
            ]);

            $endpoint->update([
                'last_request_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::warning('Endpoint email attempt failed', [
                'endpoint_id' => $this->log->mailer_endpoint_id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle job final failure after all retries are exhausted.
     */
    public function failed(\Throwable $e): void
    {
        $this->log->update([
            'status' => EndpointLogStatus::Failed,
            'error_message' => $e->getMessage(),
        ]);

        Log::error('Failed to send endpoint email after all retries', [
            'endpoint_id' => $this->log->mailer_endpoint_id,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Map incoming JSON variables to template variables
     * External payload values are HTML-escaped to prevent XSS injection in email content
     */
    private function mapVariables(array $payload, MailerEndpoint $endpoint): array
    {
        $variables = [];

        if ($endpoint->variable_mappings) {
            foreach ($endpoint->variable_mappings as $templateVar => $payloadKey) {
                $variables[$templateVar] = data_get($payload, $payloadKey);
            }
        } else {
            // If no mappings, use payload directly (flatten if nested)
            $variables = $this->flattenArray($payload);
        }

        // Sanitize external values to prevent XSS in email HTML
        // Skip 'email' keys as they are used for recipient, not rendered in HTML
        foreach ($variables as $key => $value) {
            if (is_string($value) && ! in_array(strtolower($key), ['email', 'customer_email', 'recipient_email'])) {
                $variables[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }

        return $variables;
    }

    /**
     * Flatten array for easier variable access
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
