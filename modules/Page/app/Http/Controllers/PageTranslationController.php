<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DeepLTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageService;

class PageTranslationController extends Controller
{
    public function __construct(
        private readonly DeepLTranslationService $deepl
    ) {}

    /**
     * Translate a page into a single target locale using DeepL.
     */
    public function translate(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $data = $request->validate([
            'target_locale' => ['required', 'string', 'size:2'],
            'source_locale' => ['nullable', 'string', 'size:2'],
        ]);

        if (! $this->deepl->isConfigured()) {
            return response()->json(['error' => 'DeepL no está configurado. Añade DEEPL_API_KEY en la configuración.'], 422);
        }

        $targetLocale = strtolower($data['target_locale']);
        $supported = PageService::getSupportedLocales();

        if (! in_array($targetLocale, $supported)) {
            return response()->json(['error' => "El idioma '{$targetLocale}' no está en los idiomas soportados."], 422);
        }

        $sourceLang = $data['source_locale'] ?? null;

        try {
            $translated = $this->deepl->translate(
                [
                    $page->title,
                    $page->description ?? '',
                    $page->content ?? '',
                    $page->seo_title ?? '',
                    $page->seo_description ?? '',
                    $page->seo_keywords ?? '',
                ],
                $targetLocale,
                $sourceLang
            );

            [$tTitle, $tDesc, $tContent, $tSeoTitle, $tSeoDesc, $tSeoKws] = $translated;

            $fillable = array_filter([
                'title' => $tTitle,
                'slug' => Str::slug($tTitle),
                'description' => $tDesc ?: null,
                'content' => $tContent ?: null,
                'seo_title' => $tSeoTitle ?: null,
                'seo_description' => $tSeoDesc ?: null,
                'seo_keywords' => $tSeoKws ?: null,
            ]);

            $translation = $page->translations()->updateOrCreate(
                ['locale' => $targetLocale],
                $fillable
            );

            return response()->json([
                'success' => true,
                'locale' => $targetLocale,
                'translation' => $translation,
            ]);
        } catch (\RuntimeException $e) {
            Log::error('DeepL page translation failed', ['error' => $e->getMessage(), 'page_id' => $page->id]);

            return response()->json(['error' => 'El servicio de traducción no está disponible.'], 503);
        }
    }

    /**
     * Translate a page into all supported locales using DeepL.
     */
    public function autoTranslate(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $request->validate([
            'source_locale' => ['nullable', 'string', 'size:2'],
        ]);

        if (! $this->deepl->isConfigured()) {
            return response()->json(['error' => 'DeepL no está configurado. Añade DEEPL_API_KEY en la configuración.'], 422);
        }

        $supported = PageService::getSupportedLocales();
        $sourceLang = $request->input('source_locale');
        $results = [];

        foreach ($supported as $locale) {
            try {
                $translated = $this->deepl->translate(
                    [
                        $page->title,
                        $page->description ?? '',
                        $page->content ?? '',
                        $page->seo_title ?? '',
                        $page->seo_description ?? '',
                        $page->seo_keywords ?? '',
                    ],
                    $locale,
                    $sourceLang
                );

                [$tTitle, $tDesc, $tContent, $tSeoTitle, $tSeoDesc, $tSeoKws] = $translated;

                $fillable = array_filter([
                    'title' => $tTitle,
                    'slug' => Str::slug($tTitle),
                    'description' => $tDesc ?: null,
                    'content' => $tContent ?: null,
                    'seo_title' => $tSeoTitle ?: null,
                    'seo_description' => $tSeoDesc ?: null,
                    'seo_keywords' => $tSeoKws ?: null,
                ]);

                $page->translations()->updateOrCreate(['locale' => $locale], $fillable);

                $results[$locale] = 'ok';
            } catch (\RuntimeException $e) {
                Log::error('DeepL auto-translation failed for locale', ['error' => $e->getMessage(), 'page_id' => $page->id, 'locale' => $locale]);
                $results[$locale] = 'error';
            }
        }

        $failed = array_filter($results, fn ($v) => str_starts_with($v, 'error'));

        return response()->json([
            'success' => empty($failed),
            'results' => $results,
            'message' => empty($failed)
                ? 'Traducción automática completada para todos los idiomas.'
                : 'Traducción completada con algunos errores.',
        ]);
    }
}
