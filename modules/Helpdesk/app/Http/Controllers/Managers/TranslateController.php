<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Requests\TranslateRequest;
use Modules\Helpdesk\Services\TranslationService;

class TranslateController
{
    public function __invoke(TranslateRequest $request, TranslationService $service): JsonResponse
    {
        $data = $request->validated();

        $result = $service->translate(
            text: $data['text'],
            from: $data['from'] ?? 'auto',
            to: $data['to'],
        );

        return response()->json([
            'success' => true,
            'translated' => $result['translated'],
            'detected' => $result['detected'],
            'mocked' => $result['mocked'] ?? false,
        ]);
    }
}
