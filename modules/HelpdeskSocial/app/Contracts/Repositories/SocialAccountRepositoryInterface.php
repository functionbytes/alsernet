<?php

namespace Modules\HelpdeskSocial\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\HelpdeskSocial\Models\SocialAccount;

interface SocialAccountRepositoryInterface
{
    public function find(int $id): ?SocialAccount;

    public function findByExternalId(string $platform, string $externalId): ?SocialAccount;

    /**
     * @return Collection<int, SocialAccount>
     */
    public function getActiveByPlatform(string $platform): Collection;

    /**
     * @return Collection<int, SocialAccount>
     */
    public function getAllActive(): Collection;

    public function create(array $data): SocialAccount;

    public function update(SocialAccount $account, array $data): SocialAccount;

    public function delete(SocialAccount $account): bool;

    public function toggleActive(SocialAccount $account): SocialAccount;
}
