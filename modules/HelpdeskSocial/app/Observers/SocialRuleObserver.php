<?php

namespace Modules\HelpdeskSocial\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskSocial\Models\SocialRule;
use Modules\HelpdeskSocial\Repositories\Eloquent\SocialRuleRepository;

/**
 * Invalida las claves de caché de reglas de auto-respuesta por plataforma
 * (SocialRuleRepository) cada vez que un administrador crea, edita,
 * activa/desactiva o elimina una regla.
 */
class SocialRuleObserver
{
    public function saved(SocialRule $rule): void
    {
        $this->flush();
    }

    public function deleted(SocialRule $rule): void
    {
        $this->flush();
    }

    private function flush(): void
    {
        foreach (SocialRuleRepository::cacheKeysToForget() as $key) {
            Cache::forget($key);
        }
    }
}
