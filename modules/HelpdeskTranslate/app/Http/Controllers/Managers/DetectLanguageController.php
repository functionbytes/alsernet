<?php

namespace Modules\HelpdeskTranslate\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskTranslate\Http\Concerns\EnforcesTranslationQuota;
use Modules\HelpdeskTranslate\Http\Requests\DetectLanguageRequest;
use Modules\HelpdeskTranslate\Services\CachedTranslator;

class DetectLanguageController extends Controller
{
    use EnforcesTranslationQuota;

    public function __construct(
        private readonly CachedTranslator $translator,
    ) {}

    public function __invoke(DetectLanguageRequest $request): JsonResponse
    {
        $text = trim($request->validated()['text']);

        // Antes este endpoint no pasaba por ningún cupo: detectLanguage()
        // con DeepL como proveedor primario hace una llamada real y
        // facturable a /v2/translate (no un endpoint gratuito de detección),
        // así que quedaba fuera del control de gasto diario pese a costar
        // dinero igual que una traducción manual.
        $this->enforceDailyCharacterQuota($this->translator);

        $detected = $this->translator->detectLanguage($text, feature: 'manual');

        return response()->json([
            'success' => true,
            'detected' => $detected,
        ]);
    }
}
