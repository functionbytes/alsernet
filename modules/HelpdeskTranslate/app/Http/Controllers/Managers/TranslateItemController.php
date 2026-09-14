<?php

namespace Modules\HelpdeskTranslate\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskTranslate\Http\Concerns\EnforcesTranslationQuota;
use Modules\HelpdeskTranslate\Http\Requests\TranslateItemRequest;
use Modules\HelpdeskTranslate\Services\CachedTranslator;

class TranslateItemController extends Controller
{
    use EnforcesTranslationQuota;

    public function __construct(
        private readonly CachedTranslator $translator,
    ) {}

    public function __invoke(TranslateItemRequest $request, ConversationItem $item): JsonResponse
    {
        abort_if($item->conversation === null, 404);

        $this->authorize('view', $item->conversation);

        $text = strip_tags($item->body ?? '');

        if (trim($text) === '') {
            return response()->json(
                ['success' => false, 'message' => __('helpdesktranslate::messages.errors.item_empty')],
                422,
            );
        }

        $this->enforceDailyCharacterQuota($this->translator);

        $target = $request->input('target', 'es');

        // Traducción + detección de idioma de origen en UNA sola llamada al
        // proveedor (antes: translate() + detectLanguage() por separado, dos
        // round-trips HTTP para un solo clic de "Traducir").
        $result = $this->translator->translateWithDetectedSource($text, $target, feature: 'manual');
        $translated = $result['translated'];
        $sourceLocale = $result['detected_source_language'];

        if ($translated === null) {
            return response()->json(
                ['success' => false, 'message' => __('helpdesktranslate::messages.errors.service_unavailable')],
                503,
            );
        }

        // Persist on the conversation item so the translation survives a page
        // reload and any other agent opening the same conversation sees it.
        // Columns (translated_body, source_locale) come from the
        // HelpdeskTranslate migration on helpdesk_conversation_items.
        $item->forceFill([
            'translated_body' => $translated,
            'source_locale' => $sourceLocale,
        ])->saveQuietly();

        return response()->json([
            'success' => true,
            'translated' => $translated,
            'detected' => $sourceLocale,
        ]);
    }
}
