<?php

namespace Modules\HelpdeskSocial\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskSocial\Models\SocialListeningKeyword;
use Modules\HelpdeskSocial\Services\SocialListeningService;

/**
 * Invalida el caché de palabras clave de escucha activas (SocialListeningService)
 * cada vez que un administrador crea, edita, activa/desactiva o elimina una.
 */
class SocialListeningKeywordObserver
{
    public function saved(SocialListeningKeyword $keyword): void
    {
        Cache::forget(SocialListeningService::KEYWORDS_CACHE_KEY);
    }

    public function deleted(SocialListeningKeyword $keyword): void
    {
        Cache::forget(SocialListeningService::KEYWORDS_CACHE_KEY);
    }
}
