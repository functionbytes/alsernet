<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Blog\Enums\TranslationStatus;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogTranslationLog;
use Modules\Blog\Services\BlogPostService;
use Modules\Blog\Services\BlogTranslationExportService;
use Modules\Locales\Services\DeepLTranslationService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BlogPostTranslationController extends Controller
{
    public function __construct(
        private readonly DeepLTranslationService $deepl
    ) {}

    /**
     * Translate a blog post into a single target locale using DeepL.
     */
    public function translate(Request $request, BlogPost $post): JsonResponse
    {
        $this->authorize('update', $post);

        $data = $request->validate([
            'target_locale' => ['required', 'string', 'size:2'],
            'source_locale' => ['nullable', 'string', 'size:2'],
        ]);

        if (! $this->deepl->isConfigured()) {
            return response()->json(['error' => 'DeepL no está configurado. Añade DEEPL_API_KEY en la configuración.'], 422);
        }

        $targetLocale = strtolower($data['target_locale']);
        $supported = BlogPostService::getSupportedLocales();

        if (! in_array($targetLocale, $supported)) {
            return response()->json(['error' => "El idioma '{$targetLocale}' no está en los idiomas soportados."], 422);
        }

        $sourceLang = $data['source_locale'] ?? null;

        try {
            $translated = $this->deepl->translate(
                [
                    $post->title,
                    $post->description ?? '',
                    $post->content ?? '',
                    $post->seo_title ?? '',
                    $post->seo_description ?? '',
                    $post->seo_keywords ?? '',
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
                'status' => TranslationStatus::AutoTranslated->value,
                'translated_at' => now(),
                'source_hash' => self::computeSourceHash($post),
            ]);

            $translation = $post->translations()->updateOrCreate(
                ['locale' => $targetLocale],
                $fillable
            );

            return response()->json([
                'success' => true,
                'locale' => $targetLocale,
                'translation' => $translation,
            ]);
        } catch (\RuntimeException $e) {
            Log::error('DeepL blog post translation failed', ['error' => $e->getMessage(), 'post_id' => $post->id]);

            return response()->json(['error' => 'El servicio de traducción no está disponible.'], 503);
        }
    }

    /**
     * Translate a blog post into all supported locales using DeepL.
     * Returns per-locale results with translation data for live UI update.
     */
    public function autoTranslate(Request $request, BlogPost $post): JsonResponse
    {
        $this->authorize('update', $post);

        $request->validate([
            'source_locale' => ['nullable', 'string', 'size:2'],
        ]);

        if (! $this->deepl->isConfigured()) {
            return response()->json(['error' => 'DeepL no está configurado. Añade DEEPL_API_KEY en la configuración.'], 422);
        }

        $supported = BlogPostService::getSupportedLocales();
        $sourceLang = $request->input('source_locale');
        $results = [];
        $translations = [];
        $sourceHash = self::computeSourceHash($post);

        foreach ($supported as $locale) {
            try {
                $translated = $this->deepl->translate(
                    [
                        $post->title,
                        $post->description ?? '',
                        $post->content ?? '',
                        $post->seo_title ?? '',
                        $post->seo_description ?? '',
                        $post->seo_keywords ?? '',
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
                    'status' => TranslationStatus::AutoTranslated->value,
                    'translated_at' => now(),
                    'source_hash' => $sourceHash,
                ]);

                $translation = $post->translations()->updateOrCreate(['locale' => $locale], $fillable);

                $results[$locale] = 'ok';
                $translations[$locale] = $translation;
            } catch (\RuntimeException $e) {
                Log::error('DeepL blog post auto-translation failed for locale', ['error' => $e->getMessage(), 'post_id' => $post->id, 'locale' => $locale]);
                $results[$locale] = 'error';
            }
        }

        $failed = array_filter($results, fn ($v) => str_starts_with($v, 'error'));

        return response()->json([
            'success' => empty($failed),
            'results' => $results,
            'translations' => $translations,
            'message' => empty($failed)
                ? 'Traducción automática completada para todos los idiomas.'
                : 'Traducción completada con algunos errores.',
        ]);
    }

    /**
     * Mark a translation as reviewed.
     */
    public function markReviewed(BlogPost $post, string $locale): JsonResponse
    {
        $this->authorize('update', $post);

        $translation = $post->translations()->where('locale', $locale)->firstOrFail();
        $translation->update(['status' => TranslationStatus::Reviewed]);

        return response()->json([
            'success' => true,
            'translation' => $translation->fresh(),
        ]);
    }

    /**
     * Delete the translation for a given locale.
     */
    public function destroyTranslation(BlogPost $post, string $locale): JsonResponse
    {
        $this->authorize('update', $post);

        $post->translations()->where('locale', $locale)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Translate only the specified fields into a target locale using DeepL.
     */
    public function translateFields(Request $request, BlogPost $post): JsonResponse
    {
        $this->authorize('update', $post);

        $data = $request->validate([
            'target_locale' => ['required', 'string', 'size:2'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['required', 'string', 'in:title,description,content,seo_title,seo_description,seo_keywords'],
            'source_locale' => ['nullable', 'string', 'size:2'],
        ]);

        if (! $this->deepl->isConfigured()) {
            return response()->json(['error' => 'DeepL no está configurado. Añade DEEPL_API_KEY en la configuración.'], 422);
        }

        $targetLocale = strtolower($data['target_locale']);
        $supported = BlogPostService::getSupportedLocales();

        if (! in_array($targetLocale, $supported)) {
            return response()->json(['error' => "El idioma '{$targetLocale}' no está en los idiomas soportados."], 422);
        }

        $fields = $data['fields'];
        $sourceLang = $data['source_locale'] ?? null;

        $fieldMap = [
            'title' => $post->title ?? '',
            'description' => $post->description ?? '',
            'content' => $post->content ?? '',
            'seo_title' => $post->seo_title ?? '',
            'seo_description' => $post->seo_description ?? '',
            'seo_keywords' => $post->seo_keywords ?? '',
        ];

        $textsToTranslate = array_map(fn (string $f) => $fieldMap[$f] ?? '', $fields);

        try {
            $translated = $this->deepl->translate($textsToTranslate, $targetLocale, $sourceLang);

            $fillable = array_combine($fields, $translated);
            $fillable = array_filter($fillable, fn ($v) => $v !== '');

            if (isset($fillable['title'])) {
                $fillable['slug'] = Str::slug($fillable['title']);
            }

            $fillable['status'] = TranslationStatus::AutoTranslated->value;
            $fillable['translated_at'] = now();
            $fillable['source_hash'] = self::computeSourceHash($post);

            $translation = $post->translations()->updateOrCreate(
                ['locale' => $targetLocale],
                $fillable
            );

            return response()->json([
                'success' => true,
                'locale' => $targetLocale,
                'translation' => $translation,
            ]);
        } catch (\RuntimeException $e) {
            Log::error('DeepL blog post field translation failed', ['error' => $e->getMessage(), 'post_id' => $post->id]);

            return response()->json(['error' => 'El servicio de traducción no está disponible.'], 503);
        }
    }

    /**
     * Get translation logs for a post.
     */
    public function logs(BlogPost $post): JsonResponse
    {
        $this->authorize('update', $post);

        $logs = BlogTranslationLog::query()
            ->where('blog_post_id', $post->id)
            ->with('user:id,name')
            ->latest()
            ->limit(50)
            ->get();

        return response()->json(['logs' => $logs]);
    }

    /**
     * Export translations as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $locale = $request->get('locale');
        $exportService = new BlogTranslationExportService;
        $rows = $exportService->exportToCsv($locale);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            if (! empty($rows)) {
                fputcsv($handle, array_keys($rows[0]));
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
            }
            fclose($handle);
        }, 'blog-translations-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Import translations from CSV.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $file = $request->file('file');
        $rows = [];
        $handle = fopen($file->getPathname(), 'r');
        $headers = fgetcsv($handle);

        while ($row = fgetcsv($handle)) {
            $rows[] = array_combine($headers, $row);
        }
        fclose($handle);

        $exportService = new BlogTranslationExportService;
        $result = $exportService->importFromCsv($rows);

        return response()->json($result);
    }

    /**
     * Compute a hash of the source content for staleness detection.
     */
    public static function computeSourceHash(BlogPost $post): string
    {
        return hash('sha256', ($post->title ?? '').'|'.($post->content ?? ''));
    }
}
