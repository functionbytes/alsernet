<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Mailrelay\Services\ProviderManager;
use Modules\Remarketing\Models\Message;
use Modules\Remarketing\Models\Suppression;
use Modules\Remarketing\Models\Template;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 30;

    public function __construct(
        public readonly Message $message,
    ) {
        $this->onQueue('remarketing');
    }

    public function handle(ProviderManager $providers): void
    {
        if ($this->isSuppressed()) {
            $this->message->update(['status' => 'suppressed']);

            return;
        }

        $html = $this->buildHtml();
        $unsubUrl = $this->buildUnsubscribeUrl();

        $options = [
            'from_name' => $this->message->campaign->from_name ?? config('mail.from.name'),
            'from_email' => $this->message->campaign->from_email ?? config('mail.from.address'),
            'headers' => [
                'List-Unsubscribe' => '<'.$unsubUrl.'>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
                'X-Remarketing-Message-Id' => (string) $this->message->id,
                'X-Remarketing-Store-Id' => (string) $this->message->store_id,
            ],
        ];

        try {
            $result = $providers->sendWithFailover(
                $this->message->email,
                $this->message->subject,
                $html,
                $options,
                $this->preferredProviderId(),
            );

            $this->message->update([
                'status' => 'sent',
                'sent_at' => now(),
                'provider' => $result['provider_name'] ?? null,
                'provider_message_id' => $result['message_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            // Si Mailrelay no está disponible (tabla mail_providers no migrada,
            // sin providers configurados, todos fallan), usar fallback Laravel Mail.
            Log::info('SendEmailJob: ProviderManager failed, falling back to Mail::html()', [
                'message_id' => $this->message->id,
                'reason' => $e->getMessage(),
            ]);

            $this->fallbackToFrameworkMail($html, $unsubUrl);
        }
    }

    /**
     * Fallback envío vía Mail::html() de Laravel cuando el ProviderManager
     * no tiene providers configurados o todos fallan. Ideal para entornos
     * de desarrollo o instalaciones sin Mailrelay configurado.
     */
    protected function fallbackToFrameworkMail(string $html, string $unsubUrl): void
    {
        Mail::html($html, function ($mail) use ($unsubUrl) {
            $mail->to($this->message->email)
                ->subject($this->message->subject);

            $email = $mail->getSymfonyMessage();
            $headers = $email->getHeaders();
            $headers->addTextHeader('List-Unsubscribe', '<'.$unsubUrl.'>');
            $headers->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        });

        $this->message->update([
            'status' => 'sent',
            'sent_at' => now(),
            'provider' => 'fallback-laravel-mail',
        ]);
    }

    protected function preferredProviderId(): ?int
    {
        $settings = $this->message->store?->settings ?? [];

        return isset($settings['email_provider_id']) ? (int) $settings['email_provider_id'] : null;
    }

    protected function isSuppressed(): bool
    {
        return Suppression::query()
            ->where('store_id', $this->message->store_id)
            ->where('email', $this->message->email)
            ->exists();
    }

    protected function buildHtml(): string
    {
        $html = $this->getTemplateHtml();
        $html = $this->replaceVariables($html);
        $html = $this->injectTrackingPixel($html);
        $html = $this->rewriteLinks($html);

        return $html;
    }

    protected function getTemplateHtml(): string
    {
        $campaign = $this->message->campaign;

        if ($campaign?->template) {
            return $campaign->template->html_content ?? '';
        }

        $automationRun = $this->message->automationRun;

        if ($automationRun) {
            $step = $automationRun->automation->steps()
                ->where('sort_order', $automationRun->current_step)
                ->first();

            $config = $step?->config ?? [];
            $templateId = $config['template_id'] ?? null;

            if ($templateId) {
                $template = Template::find($templateId);

                return $template?->html_content ?? '';
            }
        }

        return '';
    }

    /**
     * Reemplaza variables {{firstName}}, {{lastName}}, {{email}}, {{unsubscribeUrl}}.
     */
    protected function replaceVariables(string $html): string
    {
        $customer = $this->message->customer;
        $unsubUrl = $this->buildUnsubscribeUrl();

        $vars = [
            '{{firstName}}' => $customer?->first_name ?? '',
            '{{lastName}}' => $customer?->last_name ?? '',
            '{{email}}' => $this->message->email,
            '{{unsubscribeUrl}}' => $unsubUrl,
            '{{UNSUBSCRIBE_URL}}' => $unsubUrl,
        ];

        return strtr($html, $vars);
    }

    protected function injectTrackingPixel(string $html): string
    {
        $openUrl = url('/r/open/'.$this->message->id.'.gif').'?t='.$this->message->open_token;
        $pixel = '<img src="'.e($openUrl).'" width="1" height="1" alt="" style="display:none">';

        if (str_contains($html, '</body>')) {
            return str_replace('</body>', $pixel.'</body>', $html);
        }

        return $html.$pixel;
    }

    protected function rewriteLinks(string $html): string
    {
        $messageId = $this->message->id;
        $clickToken = $this->message->click_token;

        return preg_replace_callback(
            '/<a\s[^>]*href=(["\'])([^"\']+)\1[^>]*>/i',
            function (array $matches) use ($messageId, $clickToken) {
                $quote = $matches[1];
                $originalUrl = $matches[2];

                if (str_starts_with($originalUrl, url('/r/'))
                    || str_starts_with($originalUrl, '#')
                    || str_starts_with($originalUrl, 'mailto:')
                    || str_starts_with($originalUrl, 'tel:')) {
                    return $matches[0];
                }

                $hash = substr(hash_hmac('sha256', $originalUrl, $clickToken), 0, 16);
                $redirectUrl = url("/r/click/{$messageId}/{$hash}").'?u='.rawurlencode(base64_encode($originalUrl));

                return str_replace(
                    'href='.$quote.$originalUrl.$quote,
                    'href='.$quote.$redirectUrl.$quote,
                    $matches[0]
                );
            },
            $html
        ) ?? $html;
    }

    protected function buildUnsubscribeUrl(): string
    {
        return url('/r/unsubscribe/'.$this->message->click_token);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendEmailJob failed', [
            'message_id' => $this->message->id,
            'email' => $this->message->email,
            'error' => $exception->getMessage(),
        ]);

        $this->message->update(['status' => 'failed']);
    }
}
