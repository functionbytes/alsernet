<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Social\Services\OAuth\FacebookOAuthService;
use Modules\Social\Services\OAuth\InstagramOAuthService;
use Modules\Social\Services\OAuth\LinkedInOAuthService;
use Modules\Social\Services\OAuth\TwitterOAuthService;

class OAuthController extends Controller
{
    /**
     * Redirect to OAuth provider
     */
    public function redirect(string $network)
    {
        $service = $this->getOAuthService($network);

        if (! $service) {
            return redirect()
                ->route('admin.social.accounts.index')
                ->with('error', 'Red social no soportada');
        }

        return $service->redirectToProvider();
    }

    /**
     * Handle OAuth callback
     */
    public function callback(Request $request, string $network)
    {
        $service = $this->getOAuthService($network);

        if (! $service) {
            return redirect()
                ->route('admin.social.accounts.index')
                ->with('error', 'Red social no soportada');
        }

        try {
            $accountData = $service->handleCallback($request);

            // For services that return multiple accounts (like Facebook Pages)
            // store them in session for user selection
            $accounts = [];

            // Check if profile_data contains multiple accounts
            if (isset($accountData['profile_data']['all_pages']) && ! empty($accountData['profile_data']['all_pages'])) {
                // Facebook returns multiple pages
                foreach ($accountData['profile_data']['all_pages'] as $page) {
                    $accounts[] = [
                        'network' => $network,
                        'network_id' => $page['id'],
                        'username' => $page['name'],
                        'name' => $page['name'],
                        'access_token' => $page['access_token'],
                        'avatar' => $accountData['avatar'] ?? null,
                        'category' => $page['category'] ?? null,
                        'profile_data' => [
                            'fan_count' => $page['fan_count'] ?? 0,
                            'link' => $page['link'] ?? null,
                        ],
                    ];
                }
            } else {
                // Single account (Instagram, Twitter, LinkedIn)
                $accounts[] = [
                    'network' => $network,
                    'network_id' => $accountData['network_id'],
                    'username' => $accountData['username'],
                    'name' => $accountData['name'],
                    'access_token' => $accountData['access_token'],
                    'refresh_token' => $accountData['refresh_token'] ?? null,
                    'expires_at' => $accountData['expires_at'] ?? null,
                    'avatar' => $accountData['avatar'] ?? null,
                    'category' => $accountData['category'] ?? null,
                    'profile_data' => $accountData['profile_data'] ?? null,
                ];
            }

            // Store accounts in session for selection
            session([
                'oauth_accounts' => [
                    'network' => $network,
                    'accounts' => $accounts,
                ],
            ]);

            return redirect()->route('admin.social.accounts.select');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.social.accounts.index')
                ->with('error', 'Error al conectar con '.ucfirst($network).': '.$e->getMessage());
        }
    }

    /**
     * Get OAuth service for network
     */
    protected function getOAuthService(string $network)
    {
        return match ($network) {
            'facebook' => app(FacebookOAuthService::class),
            'instagram' => app(InstagramOAuthService::class),
            'twitter' => app(TwitterOAuthService::class),
            'linkedin' => app(LinkedInOAuthService::class),
            default => null,
        };
    }
}
