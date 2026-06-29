<?php

namespace Modules\Engagement\Services;

use Illuminate\Support\Str;
use Modules\Engagement\Models\VisitorContext;
use Modules\Engagement\Models\VisitorScore;
use Modules\Engagement\Models\VisitorSession;
use Modules\Helpdesk\Models\Inbox;

class SessionLinkService
{
    public function initSession(
        ?string $existingToken,
        array $page,
        array $device,
        Inbox $inbox,
    ): VisitorSession {
        if ($existingToken) {
            $session = VisitorSession::query()
                ->where('session_token', $existingToken)
                ->first();

            if ($session) {
                $session->update([
                    'current_url' => $page['url'] ?? $session->current_url,
                    'last_activity_at' => now(),
                ]);

                return $session;
            }
        }

        return VisitorSession::query()->create([
            'session_token' => Str::random(64),
            'customer_id' => null,
            'current_url' => $page['url'] ?? null,
            'referrer' => $page['referrer'] ?? null,
            'device' => [
                'userAgent' => $device['userAgent'] ?? null,
                'language' => $device['language'] ?? null,
                'timezone' => $device['timezone'] ?? null,
                'viewport' => $device['viewport'] ?? null,
            ],
            'ip_address' => request()->ip(),
            'country_code' => null,
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);
    }

    public function linkCustomer(string $sessionToken, int $customerId): void
    {
        VisitorSession::query()
            ->where('session_token', $sessionToken)
            ->update(['customer_id' => $customerId]);

        VisitorScore::query()
            ->where('session_token', $sessionToken)
            ->update(['customer_id' => $customerId]);

        VisitorContext::query()
            ->where('session_token', $sessionToken)
            ->update(['customer_id' => $customerId]);
    }
}
