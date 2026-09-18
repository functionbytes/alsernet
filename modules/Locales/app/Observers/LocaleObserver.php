<?php

namespace Modules\Locales\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Locales\Models\Locale;
use Modules\Locales\Services\LocaleService;
use Modules\Mailer\Models\MailerLang;

/**
 * Observer del módulo Locales.
 *
 * Responsabilidades:
 *  1. Invalidar el cache del LocaleService cuando cambian locales.
 *  2. Resetear el cache in-memory del MailerLang si la clase existe.
 *  3. Sincronizar la tabla legacy `langs.available` con `locales.is_active`
 *     (cruza `langs.iso_code` con `locales.code`) para que el Mailer y otros
 *     módulos legacy respeten qué idiomas están realmente activos.
 */
class LocaleObserver
{
    public function saved(Locale $locale): void
    {
        $this->invalidateCaches();
        $this->syncLegacyLangs($locale);
    }

    public function deleted(Locale $locale): void
    {
        $this->invalidateCaches();
        $this->syncLegacyLangs($locale);
    }

    public function restored(Locale $locale): void
    {
        $this->invalidateCaches();
        $this->syncLegacyLangs($locale);
    }

    private function invalidateCaches(): void
    {
        LocaleService::clearCache();
        Cache::forget('locales.translation_groups');

        // Reset cache in-memory del helper canónico
        Locale::clearResolvedLegacyLangId();

        // Reset cache in-memory del MailerLang si el módulo está cargado
        if (class_exists(MailerLang::class)) {
            MailerLang::clearResolvedDefault();
        }
    }

    /**
     * Propaga el estado active/default hacia la tabla legacy `langs`
     * para los módulos que todavía consultan esa tabla.
     */
    private function syncLegacyLangs(Locale $locale): void
    {
        if (! Schema::hasTable('langs')) {
            return;
        }

        // Si el locale fue soft-deleted, desactivar el lang correspondiente.
        $available = $locale->trashed() ? 0 : ($locale->is_active ? 1 : 0);

        DB::table('langs')
            ->where('iso_code', $locale->code)
            ->update(['available' => $available]);
    }
}
