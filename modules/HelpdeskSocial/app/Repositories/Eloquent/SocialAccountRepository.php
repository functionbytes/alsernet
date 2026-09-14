<?php

namespace Modules\HelpdeskSocial\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Modules\HelpdeskSocial\Contracts\Repositories\SocialAccountRepositoryInterface;
use Modules\HelpdeskSocial\Models\SocialAccount;

class SocialAccountRepository implements SocialAccountRepositoryInterface
{
    public function find(int $id): ?SocialAccount
    {
        return SocialAccount::find($id);
    }

    public function findByExternalId(string $platform, string $externalId): ?SocialAccount
    {
        return SocialAccount::where('platform', $platform)
            ->where('external_id', $externalId)
            ->first();
    }

    public function getActiveByPlatform(string $platform): Collection
    {
        return SocialAccount::active()
            ->forPlatform($platform)
            ->get();
    }

    public function getAllActive(): Collection
    {
        return SocialAccount::active()->get();
    }

    public function create(array $data): SocialAccount
    {
        return SocialAccount::create($data);
    }

    public function update(SocialAccount $account, array $data): SocialAccount
    {
        $account->update($data);

        return $account->fresh();
    }

    public function delete(SocialAccount $account): bool
    {
        return $account->delete();
    }

    public function toggleActive(SocialAccount $account): SocialAccount
    {
        $account->update(['is_active' => ! $account->is_active]);

        return $account->fresh();
    }
}
