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

class SendEndpointEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected MailerEndpointLog $log;

    /**
     * Create a new job instance
     */
    public function __construct(MailerEndpointLog $log)
    {
        $this->log = $log;
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        try {
            $endpoint = $this->log->endpoint;
            $payload = $this->log->payload;

            // Validate endpoint is active
            if (! $endpoint->is_active) {
                throw new \Exception('Endpoint is inactive');
            }

            // Get template
            $template = $endpoint->template;
            if (! $template) {
                throw new \Exception('No template configured for this endpoint');
            }

            // Map variables
            $variables = $this->mapVariables($payload, $endpoint);

            // Get recipient email
            $recipientEmail = $variables['email'] ?? $variables['customer_email'] ?? null;
            if (! $recipientEmail) {
                throw new \Exception('No recipient email found in payload');
            }

            // Resolve translation for current locale with fallback to first available
            $translation = $template->translate(
                \Modules\Mailer\Models\MailerLang::where('locale', app()->getLocale())->value('id')
            );

            $subject = $this->replaceVariables(
                $translation?->subject ?? $template->name,
                $variables
            );
            $body = $this->replaceVariables(
                $translation?->content ?? '',
                $variables
            );

            // Send email
            Mail::html($body, function ($message) use ($recipientEmail, $subject) {
                $message->to($recipientEmail)
                    ->subject($subject);
            });

            // Mark as success
            $this->log->update([
                'status' => EndpointLogStatus::Success,
                'sent_at' => now(),
                'recipient_email' => $recipientEmail,
                'mailer_subject' => $subject,
            ]);

            // Update endpoint stats
            $endpoint->update([
                'last_request_at' => now(),
            ]);

            Log::info('Email sent successfully', [
                'endpoint' => $endpoint->slug,
                'recipient' => $recipientEmail,
            ]);

        } catch (\Exception $e) {
            $this->log->update([
                'status' => EndpointLogStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Failed to send endpoint email', [
                'endpoint_id' => $this->log->mailer_endpoint_id,
                'error' => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }

    /**
     * Map incoming JSON variables to template variables
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

        return $variables;
    }

    /**
     * Replace variables in content
     */
    private function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace(
                ['{'.$key.'}', '{{'.$key.'}}'],
                $value ?? '',
                $content
            );
        }

        return $content;
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
