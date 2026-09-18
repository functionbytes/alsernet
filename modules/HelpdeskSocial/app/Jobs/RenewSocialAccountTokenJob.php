<?php

namespace Modules\HelpdeskSocial\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Services\Channels\MetaApiClient;

/**
 * Renueva el token de larga duración de una cuenta social antes de que
 * expire: intercambia el token actual por uno nuevo (exchangeToken) y deriva
 * de él un page access token fresco (getPageAccessToken). Sin este job la
 * cuenta se quedaba sin token válido a los ~60 días y dejaba de sincronizar
 * silenciosamente (solo se veía vía isTokenExpiringSoon() en el health-check,
 * nunca se corregía sola).
 */
class RenewSocialAccountTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 30;

    public function __construct(
        public readonly int $accountId,
    ) {
        $this->onQueue(config('helpdesksocial.queues.processing', 'helpdesk-social-processing'));
    }

    public function handle(MetaApiClient $client): void
    {
        $account = SocialAccount::find($this->accountId);

        if (! $account || ! $account->is_active) {
            return;
        }

        $currentToken = $account->page_access_token ?? $account->user_access_token;

        if (! $currentToken) {
            return;
        }

        $appId = config('helpdesksocial.integrations.meta.app_id');
        $appSecret = config('helpdesksocial.integrations.meta.app_secret');

        if (! $appId || ! $appSecret) {
            Log::warning('RenewSocialAccountTokenJob: Meta app_id/app_secret not configured', [
                'account_id' => $account->id,
            ]);

            return;
        }

        $newUserToken = $client->exchangeToken($currentToken, $appId, $appSecret);

        if (! $newUserToken) {
            Log::warning('RenewSocialAccountTokenJob: token exchange failed', ['account_id' => $account->id]);

            return;
        }

        $newPageToken = $client->getPageAccessToken($account->external_id, $newUserToken);

        if (! $newPageToken) {
            Log::warning('RenewSocialAccountTokenJob: could not derive page access token', ['account_id' => $account->id]);

            return;
        }

        $account->update([
            'user_access_token' => $newUserToken,
            'page_access_token' => $newPageToken,
            // Los tokens de larga duración de Meta duran ~60 días; se guarda un
            // margen conservador de 55 para que el próximo ciclo de renovación
            // (dispara con <10 días restantes) tenga tiempo de sobra.
            'token_expires_at' => now()->addDays(55),
        ]);

        Log::info('RenewSocialAccountTokenJob: token renewed', ['account_id' => $account->id]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RenewSocialAccountTokenJob failed permanently', [
            'account_id' => $this->accountId,
            'error' => $exception->getMessage(),
        ]);
    }
}
