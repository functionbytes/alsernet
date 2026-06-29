<?php

namespace Modules\HelpdeskTranslate\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskTranslate\Http\Requests\DetectLanguageRequest;
use Modules\HelpdeskTranslate\Services\CachedTranslator;

class DetectLanguageController extends Controller
{
    public function __construct(
        private readonly CachedTranslator $translator,
    ) {}

    public function __invoke(DetectLanguageRequest $request): JsonResponse
    {
        $text = trim($request->validated()['text']);

        $detected = $this->translator->detectLanguage($text);

        return response()->json([
            'success' => true,
            'detected' => $detected,
        ]);
    }
}
