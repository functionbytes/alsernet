<?php

namespace Modules\HelpdeskSocial\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Modules\HelpdeskSocial\Contracts\Repositories\SocialRuleRepositoryInterface;
use Modules\HelpdeskSocial\Models\SocialRule;

class SocialRuleRepository implements SocialRuleRepositoryInterface
{
    public function find(int $id): ?SocialRule
    {
        return SocialRule::find($id);
    }

    public function create(array $data): SocialRule
    {
        return SocialRule::create($data);
    }

    public function update(SocialRule $rule, array $data): SocialRule
    {
        $rule->update($data);

        return $rule->fresh();
    }

    public function delete(SocialRule $rule): bool
    {
        return $rule->delete();
    }

    public function getActiveForPlatform(?string $platform): Collection
    {
        return SocialRule::active()
            ->forPlatform($platform)
            ->validNow()
            ->ordered()
            ->get();
    }

    public function toggleActive(SocialRule $rule): SocialRule
    {
        $rule->update(['is_active' => ! $rule->is_active]);

        return $rule->fresh();
    }

    public function incrementTrigger(SocialRule $rule): void
    {
        $rule->incrementTrigger();
    }
}
