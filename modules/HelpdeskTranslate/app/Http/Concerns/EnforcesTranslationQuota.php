<?php

namespace Modules\HelpdeskTranslate\Http\Concerns;

use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\HelpdeskTranslate\Services\CachedTranslator;

trait EnforcesTranslationQuota
{
    /**
     * Falla rápido con 429 si el cupo diario del usuario YA está agotado —
     * solo un peek de lectura, no descuenta nada aquí. El cargo real (que
     * solo cuenta un cache-miss real contra el proveedor, nunca un acierto
     * de caché) vive en CachedTranslator::quotaExceededForCall(), invocado
     * internamente por translate()/detectLanguage().
     *
     * Antes esta comprobación descontaba el tamaño del texto ANTES de saber
     * si la petición iba a resolver por caché (gratis) o por proveedor (con
     * coste) — un agente repitiendo frases habituales ya cacheadas podía
     * agotar su cupo diario sin que se gastara un solo carácter real contra
     * DeepL/LibreTranslate, bloqueando después traducciones nuevas que sí
     * costarían. Ver también CachedTranslator::isQuotaAlreadyExhausted().
     */
    protected function enforceDailyCharacterQuota(CachedTranslator $translator): void
    {
        if (! $translator->isQuotaAlreadyExhausted('manual')) {
            return;
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => __('helpdesktranslate::messages.errors.daily_quota_exceeded'),
        ], 429));
    }
}
