<?php

namespace Modules\Campaign\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Models\CampaignSubscriber;
use Modules\CampaignSendingServers\Library\Everification\EmailVerifierContract;
use Modules\CampaignSendingServers\Library\Everification\NeverBounceVerifier;
use Modules\CampaignSendingServers\Library\Everification\ZeroBounceVerifier;
use Modules\CampaignSendingServers\Models\Blacklist;

/**
 * Verifica un subscriber con NeverBounce o ZeroBounce y persiste el
 * resultado en `attributes->verification_status`. Si invalid → blacklist.
 *
 * Configuración (config/campaign_sending_servers.php):
 *   'email_verification' => [
 *       'driver' => 'neverbounce' | 'zerobounce',
 *       'api_key' => env('NEVERBOUNCE_API_KEY'),
 *   ]
 */
class VerifySubscriberEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(protected int $subscriberId) {}

    public function handle(): void
    {
        $sub = CampaignSubscriber::find($this->subscriberId);
        if (! $sub) {
            return;
        }

        $verifier = $this->makeVerifier();
        if (! $verifier) {
            return; // sin verifier configurado, no hacemos nada
        }

        $result = $verifier->verify($sub->email);

        $attributes = $sub->attributes ?? [];
        $attributes['verification_status'] = $result;
        $attributes['verified_at'] = now()->toIso8601String();
        $sub->attributes = $attributes;
        $sub->save();

        if ($result === EmailVerifierContract::RESULT_INVALID) {
            Blacklist::firstOrCreate(
                ['email' => $sub->email],
                ['reason' => 'invalid (verifier)', 'source' => Blacklist::SOURCE_IMPORT],
            );
        }
    }

    protected function makeVerifier(): ?EmailVerifierContract
    {
        $config = config('campaign_sending_servers.email_verification', []);
        $driver = $config['driver'] ?? null;
        $key = $config['api_key'] ?? null;

        if (! $driver || ! $key) {
            return null;
        }

        return match ($driver) {
            'neverbounce' => new NeverBounceVerifier($key),
            'zerobounce' => new ZeroBounceVerifier($key),
            default => null,
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('VerifySubscriberEmail failed', [
            'subscriber_id' => $this->subscriberId,
            'error' => $exception->getMessage(),
        ]);
    }
}
