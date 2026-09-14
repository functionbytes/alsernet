<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\PasswordHistory;

class PasswordHistoryService
{
    /**
     * Check whether the raw password was used within the last N hashes.
     */
    public function isReused(User $user, string $rawPassword): bool
    {
        $limit = (int) config('auth.auth-policy.password.history_count', 5);

        if ($limit <= 0) {
            return false;
        }

        $recent = PasswordHistory::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->pluck('password_hash');

        if ($user->password && Hash::check($rawPassword, $user->password)) {
            return true;
        }

        foreach ($recent as $hash) {
            if (Hash::check($rawPassword, $hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Persist the user's current password hash as history and prune old rows.
     */
    public function record(User $user, string $hash): void
    {
        PasswordHistory::create([
            'user_id' => $user->id,
            'password_hash' => $hash,
            'created_at' => now(),
        ]);

        $this->prune($user);
    }

    private function prune(User $user): void
    {
        $limit = (int) config('auth.auth-policy.password.history_count', 5);

        $toKeep = PasswordHistory::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->pluck('id');

        PasswordHistory::query()
            ->where('user_id', $user->id)
            ->whereNotIn('id', $toKeep)
            ->delete();
    }
}
