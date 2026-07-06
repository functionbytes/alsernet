<?php

namespace Modules\HelpdeskTranslate\Http\Concerns;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

trait EnforcesTranslationQuota
{
    /**
     * Acota el volumen diario de caracteres traducidos por usuario.
     *
     * El throttle por minuto limita la frecuencia de peticiones, no el gasto
     * acumulado contra el provider (DeepL cobra por carácter). Sin este cupo un
     * agente podía sostener un consumo elevado durante horas dentro del límite
     * de 60 req/min. Lanza 429 al superarlo. Configurable; 0 = sin límite.
     */
    protected function enforceDailyCharacterQuota(Request $request, int $chars): void
    {
        $limit = (int) config('helpdesktranslate.daily_char_limit', 0);

        if ($limit <= 0 || $chars <= 0) {
            return;
        }

        $key = 'helpdesktranslate:chars:'.$request->user()->id.':'.now()->format('Ymd');
        $used = (int) Cache::get($key, 0);

        if ($used + $chars > $limit) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => __('helpdesktranslate::messages.errors.daily_quota_exceeded'),
            ], 429));
        }

        Cache::put($key, $used + $chars, now()->endOfDay());
    }
}
