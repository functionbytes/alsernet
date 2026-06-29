<?php

namespace Modules\Social\Services;

use Modules\Social\Models\SocialAccount;

class SocialAccountService
{
    /**
     * @param  array<string, mixed>  $oauthData
     * @param  array<int, string>  $selectedIds
     */
    public function saveSelectedAccounts(array $oauthData, array $selectedIds, int $accountId): int
    {
        $network = $oauthData['network'];
        $availableAccounts = collect($oauthData['accounts'])->keyBy('network_id');
        $savedCount = 0;

        foreach ($selectedIds as $networkId) {
            if (! $availableAccounts->has($networkId)) {
                continue;
            }

            $accountData = $availableAccounts->get($networkId);

            $existingAccount = SocialAccount::where('account_id', $accountId)
                ->where('network', $network)
                ->where('network_id', $networkId)
                ->first();

            if ($existingAccount) {
                $existingAccount->update([
                    'username' => $accountData['username'],
                    'name' => $accountData['name'],
                    'avatar' => $accountData['avatar'] ?? $existingAccount->avatar,
                    'category' => $accountData['category'] ?? $existingAccount->category,
                    'access_token' => encrypt($accountData['access_token']),
                    'refresh_token' => isset($accountData['refresh_token']) ? encrypt($accountData['refresh_token']) : null,
                    'token_expires_at' => $accountData['expires_at'] ?? null,
                    'profile_data' => $accountData['profile_data'] ?? $existingAccount->profile_data,
                    'status' => 1,
                    'last_sync_at' => now(),
                    'last_error' => null,
                ]);
            } else {
                SocialAccount::create([
                    'account_id' => $accountId,
                    'network' => $network,
                    'network_id' => $networkId,
                    'username' => $accountData['username'],
                    'name' => $accountData['name'],
                    'avatar' => $accountData['avatar'] ?? null,
                    'category' => $accountData['category'] ?? null,
                    'access_token' => encrypt($accountData['access_token']),
                    'refresh_token' => isset($accountData['refresh_token']) ? encrypt($accountData['refresh_token']) : null,
                    'token_expires_at' => $accountData['expires_at'] ?? null,
                    'profile_data' => $accountData['profile_data'] ?? null,
                    'status' => 1,
                    'last_sync_at' => now(),
                ]);
            }

            $savedCount++;
        }

        return $savedCount;
    }
}
