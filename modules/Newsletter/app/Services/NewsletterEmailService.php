<?php

namespace Modules\Newsletter\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Models\Setting;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;
use Modules\Newsletter\Mail\NewsletterSubscriberMail;
use Modules\Newsletter\Models\Subscriber;

class NewsletterEmailService
{
    public static function sendSubscriptionEmail(Subscriber $subscriber): void
    {
        if (Setting::get('newsletter.email_subscription_enabled') !== '1') {
            return;
        }

        $template = self::resolveTemplate(
            'newsletter.email_subscription_template_id',
            'newsletter_subscription_confirmation'
        );

        if (! $template) {
            Log::warning('Newsletter: no subscription template configured');

            return;
        }

        self::send($subscriber->email, $subscriber, $template);
    }

    public static function sendUnsubscriptionEmail(string $email, ?string $name = null): void
    {
        if (Setting::get('newsletter.email_unsubscription_enabled') !== '1') {
            return;
        }

        $template = self::resolveTemplate(
            'newsletter.email_unsubscription_template_id',
            'newsletter_unsubscription_confirmation'
        );

        if (! $template) {
            Log::warning('Newsletter: no unsubscription template configured');

            return;
        }

        $subscriber = new Subscriber(['email' => $email, 'name' => $name]);
        self::send($email, $subscriber, $template);
    }

    private static function send(string $recipient, Subscriber $subscriber, MailerTemplate $template): void
    {
        try {
            $langId = 1;

            $variables = [
                'SUBSCRIBER_EMAIL' => $subscriber->email,
                'SUBSCRIBER_NAME' => $subscriber->name ?? '',
                'CUSTOMER_NAME' => $subscriber->name ?? $subscriber->email,
                'CUSTOMER_EMAIL' => $subscriber->email,
                'COMPANY_NAME' => config('app.name'),
                'SITE_NAME' => config('app.name'),
                'SITE_URL' => config('app.url'),
                'CURRENT_YEAR' => date('Y'),
                'CURRENT_DATE' => now()->format('d/m/Y'),
            ];

            $translation = $template->translate($langId);

            if (! $translation || ! $translation->subject) {
                Log::error('Newsletter email template has no translation', ['template_id' => $template->id]);

                return;
            }

            $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);
            $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

            Mail::to($recipient)->queue(new NewsletterSubscriberMail($subject, $content));
        } catch (\Exception $e) {
            Log::error('Newsletter: error sending email', [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function resolveTemplate(string $settingKey, string $fallbackKey): ?MailerTemplate
    {
        $templateId = Setting::get($settingKey);

        if ($templateId) {
            $template = MailerTemplate::find($templateId);
            if ($template && $template->is_enabled) {
                return $template;
            }
        }

        return MailerTemplate::where('key', $fallbackKey)
            ->where('is_enabled', true)
            ->first();
    }
}
