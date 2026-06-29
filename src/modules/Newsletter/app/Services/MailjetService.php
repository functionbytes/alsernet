<?php

namespace Modules\Newsletter\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;

class MailjetService
{
    public function isEnabled(): bool
    {
        return Setting::get('newsletter.mailjet_enabled') === '1'
            && filled(Setting::get('newsletter.mailjet_api_key'))
            && filled(Setting::get('newsletter.mailjet_api_secret'))
            && filled(Setting::get('newsletter.mailjet_list_id'));
    }

    public function addContact(string $email, ?string $name): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        try {
            [$key, $secret] = $this->credentials();
            $listId = Setting::get('newsletter.mailjet_list_id', '');

            Http::withBasicAuth($key, $secret)
                ->post('https://api.mailjet.com/v3/REST/contact', [
                    'Email' => $email,
                    'Name' => $name ?? '',
                ]);

            Http::withBasicAuth($key, $secret)
                ->post('https://api.mailjet.com/v3/REST/listrecipient', [
                    'ContactsListID' => $listId,
                    'Email' => $email,
                    'IsUnsubscribed' => false,
                ]);
        } catch (\Throwable $e) {
            Log::error('Mailjet addContact failed', ['email' => $email, 'error' => $e->getMessage()]);
        }
    }

    public function removeContact(string $email): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        try {
            [$key, $secret] = $this->credentials();
            $listId = Setting::get('newsletter.mailjet_list_id', '');

            Http::withBasicAuth($key, $secret)
                ->post('https://api.mailjet.com/v3/REST/listrecipient', [
                    'ContactsListID' => $listId,
                    'Email' => $email,
                    'IsUnsubscribed' => true,
                ]);
        } catch (\Throwable $e) {
            Log::error('Mailjet removeContact failed', ['email' => $email, 'error' => $e->getMessage()]);
        }
    }

    private function credentials(): array
    {
        return [
            Setting::get('newsletter.mailjet_api_key', ''),
            Setting::get('newsletter.mailjet_api_secret', ''),
        ];
    }
}
