<?php

namespace Modules\HelpdeskTranslate\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Modules\HelpdeskTranslate\Http\Concerns\EnforcesTranslationQuota;
use Modules\HelpdeskTranslate\Http\Requests\TranslateRequest;
use Modules\HelpdeskTranslate\Services\CachedTranslator;

class TranslateController
{
    use EnforcesTranslationQuota;

    public function __invoke(TranslateRequest $request, CachedTranslator $translator): JsonResponse
    {
        $data = $request->validated();
        $from = $data['from'] ?? 'auto';

        $this->enforceDailyCharacterQuota($request, mb_strlen($data['text']));

        $translated = $translator->translate(
            text: $data['text'],
            targetLang: $data['to'],
            sourceLang: $from === 'auto' ? null : $from,
            feature: 'manual',
        );

        if ($translated === null) {
            return response()->json([
                'success' => false,
                'message' => __('helpdesktranslate::messages.errors.service_unavailable'),
            ], 503);
        }

        return response()->json([
            'success' => true,
            'translated' => $translated,
            'provider' => $translator->resolveProvider(),
        ]);
    }
}
