<?php

namespace Modules\Social\Services\OAuth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class LinkedInOAuthService extends BaseOAuthService
{
    /**
     * Redirect to LinkedIn OAuth
     */
    public function redirectToProvider(): RedirectResponse
    {
        return Socialite::driver('linkedin-openid')
            ->scopes([
                'openid',
                'profile',
                'w_member_social',
                'r_organization_social',
                'rw_organization_admin',
            ])
            ->redirect();
    }

    /**
     * Handle LinkedIn OAuth callback
     */
    public function handleCallback(Request $request): array
    {
        $user = Socialite::driver('linkedin-openid')->user();

        // Get user's LinkedIn organizations/pages
        $organizations = $this->getUserOrganizations($user->token);

        // Use personal profile if no organizations
        $isOrganization = ! empty($organizations);
        $profile = $isOrganization ? $organizations[0] : null;

        return [
            'network_id' => $isOrganization ? $profile['id'] : $user->id,
            'username' => $isOrganization ? $profile['localizedName'] : ($user->nickname ?? $user->name),
            'name' => $isOrganization ? $profile['localizedName'] : $user->name,
            'access_token' => $user->token,
            'refresh_token' => $user->refreshToken ?? null,
            'expires_at' => $user->expiresIn ? now()->addSeconds($user->expiresIn) : now()->addDays(60),
            'profile_data' => [
                'email' => $user->email,
                'avatar' => $user->avatar,
                'is_organization' => $isOrganization,
                'organization_id' => $isOrganization ? $profile['id'] : null,
                'organization_name' => $isOrganization ? $profile['localizedName'] : null,
                'vanity_name' => $isOrganization ? $profile['vanityName'] : null,
                'all_organizations' => $organizations,
            ],
        ];
    }

    /**
     * Get user's LinkedIn organizations
     */
    protected function getUserOrganizations(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->withHeaders([
                'X-Restli-Protocol-Version' => '2.0.0',
            ])
            ->get('https://api.linkedin.com/v2/organizationAcls', [
                'q' => 'roleAssignee',
                'role' => 'ADMINISTRATOR',
                'projection' => '(elements*(organization~(id,localizedName,vanityName,logoV2)))',
            ]);

        if ($response->failed()) {
            return [];
        }

        $data = $response->json();
        $organizations = [];

        foreach ($data['elements'] ?? [] as $element) {
            $org = $element['organization~'] ?? null;
            if ($org) {
                $organizations[] = [
                    'id' => $org['id'],
                    'localizedName' => $org['localizedName'],
                    'vanityName' => $org['vanityName'] ?? null,
                    'logo' => $org['logoV2']['original~']['elements'][0]['identifiers'][0]['identifier'] ?? null,
                ];
            }
        }

        return $organizations;
    }

    /**
     * Refresh access token
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => config('services.linkedin-openid.client_id'),
            'client_secret' => config('services.linkedin-openid.client_secret'),
        ]);

        if ($response->failed()) {
            throw new \Exception('Error al renovar token de LinkedIn: '.$response->body());
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $refreshToken,
            'expires_at' => now()->addSeconds($data['expires_in']),
        ];
    }

    /**
     * Get LinkedIn profile
     */
    public function getUserProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->withHeaders([
                'X-Restli-Protocol-Version' => '2.0.0',
            ])
            ->get('https://api.linkedin.com/v2/me', [
                'projection' => '(id,firstName,lastName,profilePicture(displayImage~:playableStreams))',
            ]);

        if ($response->failed()) {
            throw new \Exception('Error al obtener perfil de LinkedIn: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Validate webhook signature (LinkedIn doesn't use webhook signatures)
     */
    public function validateWebhook(Request $request): bool
    {
        // LinkedIn doesn't use signature validation for webhooks
        return true;
    }
}
