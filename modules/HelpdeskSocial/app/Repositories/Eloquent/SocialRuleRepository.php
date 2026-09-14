<?php

namespace Modules\HelpdeskSocial\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskSocial\Contracts\Repositories\SocialRuleRepositoryInterface;
use Modules\HelpdeskSocial\Models\SocialRule;

class SocialRuleRepository implements SocialRuleRepositoryInterface
{
    private const CACHE_TTL = 900;

    /**
     * Plataformas soportadas actualmente. Usadas por SocialRuleObserver para
     * invalidar todas las claves de caché por plataforma (además de la clave
     * "sin plataforma") ante cualquier save/delete de una regla.
     *
     * @var array<int, string>
     */
    private const KNOWN_PLATFORMS = ['facebook', 'instagram'];

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
        return Cache::remember(
            self::cacheKey($platform),
            self::CACHE_TTL,
            fn () => SocialRule::active()
                ->forPlatform($platform)
                ->validNow()
                ->ordered()
                ->get()
        );
    }

    /**
     * Clave de caché de las reglas activas para una plataforma dada (o sin filtro).
     */
    public static function cacheKey(?string $platform): string
    {
        return 'helpdesksocial:rules:active:'.($platform ?? 'all');
    }

    /**
     * Todas las claves de caché a invalidar cuando cambia cualquier regla.
     *
     * @return array<int, string>
     */
    public static function cacheKeysToForget(): array
    {
        return array_map(
            fn (?string $platform) => self::cacheKey($platform),
            [null, ...self::KNOWN_PLATFORMS]
        );
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
