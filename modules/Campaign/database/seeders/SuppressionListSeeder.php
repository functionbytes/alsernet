<?php

namespace Modules\Campaign\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Campaign\Models\CampaignSuppressionList;

class SuppressionListSeeder extends Seeder
{
    /**
     * Dominios de email temporales/desechables comunes.
     */
    protected array $disposableDomains = [
        'mailinator.com', 'guerrillamail.com', 'tempmail.com', 'throwawaymail.com',
        'yopmail.com', 'sharklasers.com', 'getairmail.com', 'temp-mail.org',
        'fakeinbox.com', 'tempinbox.com', 'mailnesia.com', 'trashmail.com',
        'emailondeck.com', 'dispostable.com', 'tempail.com', 'burnermail.io',
        'tempmailaddress.com', 'fake-email.net', 'mohmal.com', 'mailcatch.com',
        'getnada.com', 'maildrop.cc', 'harakirimail.com', 'mail-temp.com',
        '10minutemail.com', 'tempmailo.com', 'throwaway.email', 'inboxkitten.com',
    ];

    /**
     * Dominios de no-reply típicos que no deberían recibir campañas.
     */
    protected array $noReplyPatterns = [
        '/^noreply@/',
        '/^no-reply@/',
        '/^donotreply@/',
        '/^info@/',
        '/^admin@/',
        '/^postmaster@/',
        '/^abuse@/',
        '/^webmaster@/',
    ];

    public function run(): void
    {
        foreach ($this->disposableDomains as $domain) {
            CampaignSuppressionList::firstOrCreate(
                ['type' => 'domain', 'value' => $domain],
                [
                    'uid' => (string) Str::uuid(),
                    'name' => 'Disposable: '.$domain,
                    'type' => 'domain',
                    'value' => $domain,
                    'is_global' => true,
                ]
            );
        }

        foreach ($this->noReplyPatterns as $pattern) {
            CampaignSuppressionList::firstOrCreate(
                ['type' => 'pattern', 'value' => $pattern],
                [
                    'uid' => (string) Str::uuid(),
                    'name' => 'Pattern: '.$pattern,
                    'type' => 'pattern',
                    'value' => $pattern,
                    'is_global' => true,
                ]
            );
        }
    }
}
