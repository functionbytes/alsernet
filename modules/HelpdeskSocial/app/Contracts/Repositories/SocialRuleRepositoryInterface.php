<?php

namespace Modules\HelpdeskSocial\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\HelpdeskSocial\Models\SocialRule;

interface SocialRuleRepositoryInterface
{
    public function find(int $id): ?SocialRule;

    public function create(array $data): SocialRule;

    public function update(SocialRule $rule, array $data): SocialRule;

    public function delete(SocialRule $rule): bool;

    /**
     * @return Collection<int, SocialRule>
     */
    public function getActiveForPlatform(?string $platform): Collection;

    public function toggleActive(SocialRule $rule): SocialRule;

    public function incrementTrigger(SocialRule $rule): void;
}
