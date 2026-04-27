<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Modules\Blog\Models\BlogPost;
use Modules\Locales\Models\Locale;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageService;
use Modules\Seo\Http\Requests\BulkUpdateRobotsRequest;
use Modules\Seo\Http\Requests\CreateLocaleSeoMetaRequest;
use Modules\Seo\Http\Requests\GenerateOgImageRequest;
use Modules\Seo\Http\Requests\ImportSeoCsvRequest;
use Modules\Seo\Http\Requests\ImportSeoJsonRequest;
use Modules\Seo\Http\Requests\InlineUpdateSeoMetaRequest;
use Modules\Seo\Http\Requests\TranslateSeoMetaRequest;
use Modules\Seo\Http\Requests\UpdateSeoMetaRequest;
use Modules\Seo\Models\SeoAuditLog;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Models\SeoRedirect;
use Modules\Seo\Models\SeoStaticUrl;
use Modules\Seo\Models\SeoTemplate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeoMetaWebController extends Controller
{
    /** @var array<string> */
    private const SORTABLE_COLUMNS = ['updated_at', 'created_at', 'title', 'seo_score', 'seo_grade'];

    public function __construct()
    {
        $this->middleware('can:Seo.metas.index')->only('index', 'show', 'export', 'exportJson', 'showImport', 'showImportJson', 'checklist', 'hreflangIndex', 'scoreHistory');
        $this->middleware('can:Seo.metas.update')->only('edit', 'update', 'bulkUpdateRobots', 'import', 'importJson', 'inlineUpdate', 'generateCanonical', 'generateOgImage', 'translateMeta', 'bulkGenerateCanonicals', 'createLocale');
        $this->middleware('can:Seo.metas.delete')->only('destroy', 'bulkDelete');
    }

    /**
     * Display a listing of SEO meta records with filters and statistics.
     */
    public function index(Request $request): View
    {
        $query = SeoMeta::with('seoable');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($seoableType = $request->get('seoable_type')) {
            $query->forType($seoableType);
        }

        if ($robots = $request->get('robots')) {
            $query->withRobots($robots);
        }

        if ($targetKeyword = $request->get('target_keyword')) {
            $query->where('target_keyword', $targetKeyword);
        }

        $tab = $request->get('tab', 'all');

        match ($tab) {
            'indexable' => $query->where(fn ($q) => $q->whereNull('robots')->orWhere('robots', 'LIKE', '%index%'))
                ->whereNotLike('robots', '%noindex%'),
            'noindex' => $query->where('robots', 'LIKE', '%noindex%'),
            'unoptimized' => $query->where(fn ($q) => $q->whereNull('description')
                ->orWhereNull('og_image')
                ->orWhere('description', '')
                ->orWhere('og_image', '')),
            default => null,
        };

        $sortBy = in_array($request->get('sort_by'), self::SORTABLE_COLUMNS, true)
            ? $request->get('sort_by')
            : 'updated_at';
        $sortDirection = $request->get('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['seo_score', 'seo_grade'], true)) {
            $query->orderByRaw("CASE WHEN {$sortBy} IS NULL THEN 1 ELSE 0 END")
                ->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $metas = $query->paginate(15)->withQueryString();

        $seoableTypes = SeoMeta::selectRaw('DISTINCT seoable_type')
            ->whereNotNull('seoable_type')
            ->pluck('seoable_type');

        $aggregates = SeoMeta::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN (robots IS NULL OR robots LIKE '%index%') AND robots NOT LIKE '%noindex%' THEN 1 ELSE 0 END) as indexable")
            ->selectRaw("SUM(CASE WHEN robots LIKE '%noindex%' THEN 1 ELSE 0 END) as noindex")
            ->selectRaw('SUM(CASE WHEN description IS NULL THEN 1 ELSE 0 END) as missing_description')
            ->selectRaw('SUM(CASE WHEN og_image IS NULL THEN 1 ELSE 0 END) as missing_og_image')
            ->selectRaw('AVG(CASE WHEN seo_score IS NOT NULL THEN seo_score ELSE NULL END) as avg_score')
            ->first();

        $stats = [
            'total' => (int) ($aggregates->total ?? 0),
            'indexable' => (int) ($aggregates->indexable ?? 0),
            'noindex' => (int) ($aggregates->noindex ?? 0),
            'missing_description' => (int) ($aggregates->missing_description ?? 0),
            'missing_og_image' => (int) ($aggregates->missing_og_image ?? 0),
            'avg_score' => round((float) ($aggregates->avg_score ?? 0)),
        ];

        return view('Seo::settings.metas.index', compact('metas', 'seoableTypes', 'stats', 'tab'));
    }

    /**
     * Display the specified SEO meta record.
     */
    public function show(SeoMeta $meta): View
    {
        $meta->load('seoable');

        return view('Seo::settings.metas.show', compact('meta'));
    }

    /**
     * Show the form for editing the specified SEO meta.
     */
    public function edit(SeoMeta $meta): View
    {
        $meta->load('seoable');

        $siblingMetas = SeoMeta::where('seoable_type', $meta->seoable_type)
            ->where('seoable_id', $meta->seoable_id)
            ->orderBy('locale')
            ->get()
            ->keyBy(fn ($m) => $m->locale ?? 'global');

        $activeLocales = collect();
        if (class_exists(Locale::class)) {
            try {
                $activeLocales = Locale::active()->ordered()->get();
            } catch (\Exception) {
            }
        }
        if ($activeLocales->isEmpty()) {
            $activeLocales = collect(PageService::getSupportedLocales())->map(fn ($code) => (object) [
                'id' => null, 'code' => $code, 'name' => strtoupper($code), 'native_name' => strtoupper($code),
            ]);
        }
        // For non-page seoables, only show language switcher if there are siblings (don't add missing locales)
        $showMissingLocales = $meta->seoable instanceof Page;

        $globalMeta = $meta->locale ? $siblingMetas->get('global') : null;

        return view('Seo::settings.metas.edit', compact('meta', 'siblingMetas', 'activeLocales', 'globalMeta', 'showMissingLocales'));
    }

    /**
     * Create a new SeoMeta record for the same seoable with a different locale.
     */
    public function createLocale(CreateLocaleSeoMetaRequest $request, SeoMeta $meta): RedirectResponse
    {
        $locale = $request->string('locale')->toString();

        $existing = SeoMeta::where('seoable_type', $meta->seoable_type)
            ->where('seoable_id', $meta->seoable_id)
            ->where('locale', $locale)
            ->first();

        if ($existing) {
            return redirect()->route('settings.seo.metas.edit', $existing)
                ->with('info', 'Ya existe un meta para este idioma.');
        }

        $newMeta = SeoMeta::create([
            'seoable_type' => $meta->seoable_type,
            'seoable_id' => $meta->seoable_id,
            'locale' => $locale,
            'robots' => 'index,follow',
        ]);

        return redirect()->route('settings.seo.metas.edit', $newMeta)
            ->with('success', 'Meta SEO creado para el idioma '.strtoupper($locale).'.');
    }

    /**
     * Update the specified SEO meta in storage.
     */
    public function update(UpdateSeoMetaRequest $request, SeoMeta $meta): RedirectResponse
    {
        $meta->update($request->validated());

        return redirect()
            ->route('settings.seo.metas.show', $meta)
            ->with('success', 'Meta SEO actualizado correctamente.');
    }

    /**
     * Remove the specified SEO meta from storage.
     */
    public function destroy(SeoMeta $meta): RedirectResponse
    {
        $meta->delete();

        return redirect()
            ->route('settings.seo.metas.index')
            ->with('success', 'Meta SEO eliminado correctamente.');
    }

    /**
     * Inline update a single field of a SEO meta record via AJAX.
     */
    public function inlineUpdate(InlineUpdateSeoMetaRequest $request, SeoMeta $meta): JsonResponse
    {
        $validated = $request->validated();

        $meta->update([$validated['field'] => $validated['value']]);

        return response()->json(['success' => true, 'value' => $meta->{$validated['field']}]);
    }

    /**
     * Bulk update robots directive for multiple SEO meta records.
     */
    public function bulkUpdateRobots(BulkUpdateRobotsRequest $request): RedirectResponse
    {
        $ids = json_decode($request->ids, true);

        if (empty($ids) || ! is_array($ids)) {
            return back()->with('error', 'No se seleccionaron registros.');
        }

        SeoMeta::whereIn('id', $ids)->update(['robots' => $request->robots]);

        return back()->with('success', count($ids).' registros actualizados.');
    }

    /**
     * Bulk delete SEO meta records.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = is_string($request->ids) ? json_decode($request->ids, true) : $request->ids;

        if (empty($ids) || ! is_array($ids)) {
            return back()->with('error', 'No se seleccionaron registros.');
        }

        SeoMeta::whereIn('id', $ids)->delete();

        return back()->with('success', count($ids).' registros meta SEO eliminados correctamente.');
    }

    /**
     * Export all SEO meta records as a UTF-8 CSV file.
     */
    public function export(): StreamedResponse
    {
        $filename = 'seo-metas-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, [
                'id', 'seoable_type', 'seoable_id', 'title', 'description', 'keywords',
                'og_title', 'og_description', 'og_image', 'og_type',
                'twitter_card', 'twitter_title', 'twitter_description', 'twitter_image',
                'canonical_url', 'robots', 'seo_score', 'seo_grade',
            ]);

            SeoMeta::with('seoable')->orderBy('id')->each(function (SeoMeta $meta) use ($handle) {
                fputcsv($handle, [
                    $meta->id,
                    class_basename($meta->seoable_type ?? ''),
                    $meta->seoable_id,
                    $meta->title ?? '',
                    $meta->description ?? '',
                    $meta->keywords ?? '',
                    $meta->og_title ?? '',
                    $meta->og_description ?? '',
                    $meta->og_image ?? '',
                    $meta->og_type ?? '',
                    $meta->twitter_card ?? '',
                    $meta->twitter_title ?? '',
                    $meta->twitter_description ?? '',
                    $meta->twitter_image ?? '',
                    $meta->canonical_url ?? '',
                    $meta->robots ?? 'index,follow',
                    $meta->seo_score ?? '',
                    $meta->seo_grade ?? '',
                ]);
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Show the CSV import form.
     */
    public function showImport(): View
    {
        return view('Seo::settings.metas.import');
    }

    /**
     * Auto-generate a canonical URL for the given SEO meta from its seoable model.
     */
    public function generateCanonical(Request $request, SeoMeta $meta): JsonResponse
    {
        if (! empty($meta->canonical_url)) {
            return response()->json(['error' => 'Esta meta ya tiene una URL canónica establecida.'], 422);
        }

        $seoable = $meta->seoable;
        $canonical = null;

        if ($seoable) {
            $modelClass = class_basename($seoable);
            $slug = $seoable->slug ?? $seoable->id ?? null;

            $routeAttempts = [
                strtolower($modelClass).'.show',
                'page.show',
                'blog.show',
                'post.show',
            ];

            foreach ($routeAttempts as $routeName) {
                try {
                    $canonical = route($routeName, $slug);
                    break;
                } catch (\Throwable) {
                    // try next
                }
            }

            if (! $canonical && $slug) {
                $prefix = strtolower(str_replace('\\', '/', $modelClass));
                $canonical = url("/{$prefix}/{$slug}");
            }
        }

        if (! $canonical) {
            return response()->json(['error' => 'No se pudo generar la URL canónica automáticamente.'], 422);
        }

        $meta->update(['canonical_url' => $canonical]);

        return response()->json(['canonical_url' => $canonical, 'message' => 'URL canónica generada correctamente.']);
    }

    /**
     * Return SEO badge data for multiple models by ID and type (for DataGrid lazy-loading).
     */
    public function badgeData(Request $request): JsonResponse
    {
        $ids = $request->validate(['ids' => 'required|array|max:100', 'ids.*' => 'integer']);
        $type = $request->validate(['type' => 'required|string|max:200'])['type'];

        $metas = SeoMeta::whereIn('seoable_id', $ids['ids'])
            ->where('seoable_type', $type)
            ->get(['seoable_id', 'seo_score', 'seo_grade', 'id']);

        return response()->json($metas->keyBy('seoable_id'));
    }

    /**
     * Return a pre-publish SEO checklist for the given meta record.
     */
    public function checklist(SeoMeta $meta): JsonResponse
    {
        $items = [
            [
                'key' => 'title',
                'label' => 'Título SEO',
                'status' => ! empty($meta->title) && strlen($meta->title) >= 30 && strlen($meta->title) <= 60
                    ? 'pass'
                    : (! empty($meta->title) ? 'warning' : 'fail'),
                'message' => empty($meta->title)
                    ? 'Falta el título SEO'
                    : (strlen($meta->title) < 30
                        ? 'Título demasiado corto (mín. 30 caracteres)'
                        : (strlen($meta->title) > 60 ? 'Título demasiado largo (máx. 60 caracteres)' : 'Título correcto')),
            ],
            [
                'key' => 'description',
                'label' => 'Meta descripción',
                'status' => ! empty($meta->description) && strlen($meta->description) >= 120 && strlen($meta->description) <= 160
                    ? 'pass'
                    : (! empty($meta->description) ? 'warning' : 'fail'),
                'message' => empty($meta->description)
                    ? 'Falta la meta descripción'
                    : (strlen($meta->description) < 120
                        ? 'Descripción demasiado corta (mín. 120 caracteres)'
                        : (strlen($meta->description) > 160 ? 'Descripción demasiado larga (máx. 160 caracteres)' : 'Descripción correcta')),
            ],
            [
                'key' => 'canonical',
                'label' => 'URL canónica',
                'status' => ! empty($meta->canonical_url) ? 'pass' : 'warning',
                'message' => empty($meta->canonical_url)
                    ? 'Sin URL canónica — recomendado para evitar contenido duplicado'
                    : 'URL canónica establecida',
            ],
            [
                'key' => 'og_image',
                'label' => 'Imagen Open Graph',
                'status' => ! empty($meta->og_image) ? 'pass' : 'warning',
                'message' => empty($meta->og_image)
                    ? 'Sin imagen OG — la página no tendrá preview en redes sociales'
                    : 'Imagen OG establecida',
            ],
            [
                'key' => 'target_keyword',
                'label' => 'Keyword objetivo',
                'status' => ! empty($meta->target_keyword) ? 'pass' : 'warning',
                'message' => empty($meta->target_keyword)
                    ? 'Sin keyword objetivo definida'
                    : "Keyword: \"{$meta->target_keyword}\"",
            ],
            [
                'key' => 'robots',
                'label' => 'Robots',
                'status' => str_contains(strtolower($meta->robots ?? 'index'), 'noindex') ? 'warning' : 'pass',
                'message' => str_contains(strtolower($meta->robots ?? ''), 'noindex')
                    ? 'Página configurada como noindex — no aparecerá en buscadores'
                    : 'Indexable correctamente',
            ],
            [
                'key' => 'seo_score',
                'label' => 'Puntuación SEO',
                'status' => ($meta->seo_score ?? 0) >= 70
                    ? 'pass'
                    : (($meta->seo_score ?? 0) >= 50 ? 'warning' : 'fail'),
                'message' => $meta->seo_score
                    ? "Score SEO: {$meta->seo_score}/100 (grado {$meta->seo_grade})"
                    : 'Sin auditoría SEO realizada',
            ],
        ];

        $passCount = count(array_filter($items, fn ($i) => $i['status'] === 'pass'));
        $total = count($items);

        return response()->json([
            'items' => $items,
            'pass_count' => $passCount,
            'total' => $total,
            'score' => round(($passCount / $total) * 100),
            'ready' => $passCount >= 5,
        ]);
    }

    /**
     * Generate an OG image for the given meta record using Intervention Image.
     */
    public function generateOgImage(GenerateOgImageRequest $request, SeoMeta $meta): JsonResponse
    {
        if (! class_exists(ImageManager::class)) {
            return response()->json([
                'error' => 'Intervention Image no está instalado. Ejecuta: composer require intervention/image',
            ], 422);
        }

        $text = $request->input('text', $meta->title ?? config('app.name'));
        $template = $request->input('template', 'default');
        $bgColor = match ($template) {
            'dark' => '#1a1a2e',
            'gradient' => '#16213e',
            'minimal' => '#ffffff',
            default => $request->input('bg_color', '#90bb13'),
        };
        $textColor = match ($template) {
            'dark', 'gradient' => '#ffffff',
            'minimal' => '#333333',
            default => $request->input('text_color', '#ffffff'),
        };

        try {
            $manager = new ImageManager(
                new Driver
            );

            $image = $manager->create(1200, 630)->fill($bgColor);

            $image->text(config('app.name', 'Mi Sitio'), 60, 60, function ($font) use ($textColor) {
                $font->color($textColor);
                $font->size(28);
            });

            $words = explode(' ', $text);
            $lines = [];
            $currentLine = '';

            foreach ($words as $word) {
                $test = $currentLine ? "$currentLine $word" : $word;
                if (strlen($test) > 40) {
                    $lines[] = $currentLine;
                    $currentLine = $word;
                } else {
                    $currentLine = $test;
                }
            }

            if ($currentLine) {
                $lines[] = $currentLine;
            }

            $y = 200;
            foreach (array_slice($lines, 0, 4) as $line) {
                $image->text($line, 60, $y, function ($font) use ($textColor) {
                    $font->color($textColor);
                    $font->size(52);
                });
                $y += 70;
            }

            $filename = 'og-images/meta-'.$meta->id.'-'.time().'.png';
            $path = storage_path('app/public/'.$filename);

            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            $image->save($path);

            $url = asset('storage/'.$filename);
            $meta->update(['og_image' => $url]);

            return response()->json(['url' => $url, 'message' => 'Imagen OG generada correctamente.']);
        } catch (\Throwable $e) {
            Log::error('SEO OG image generation failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Error al generar la imagen. Por favor, inténtalo de nuevo.'], 422);
        }
    }

    /**
     * Process the imported CSV file and upsert SEO meta records.
     */
    public function import(ImportSeoCsvRequest $request): RedirectResponse
    {
        $handle = fopen($request->file('csv_file')->getPathname(), 'r');

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        $required = ['seoable_type', 'seoable_id'];

        if (! $header || count(array_intersect($required, $header)) < count($required)) {
            fclose($handle);

            return back()->with('error', 'CSV inválido. Se requieren columnas: seoable_type, seoable_id');
        }

        $map = array_flip($header);
        $updated = $created = $skipped = $errors = 0;
        $updateExisting = $request->boolean('update_existing', false);

        DB::transaction(function () use ($handle, $map, $updateExisting, &$updated, &$created, &$skipped, &$errors) {
            $updatableFields = ['title', 'description', 'keywords', 'og_title', 'og_description',
                'og_image', 'og_type', 'twitter_card', 'twitter_title', 'twitter_description',
                'twitter_image', 'canonical_url', 'robots'];

            // Map short type names back to full class names
            $typeMap = array_filter([
                'Page' => class_exists(Page::class) ? Page::class : null,
                'BlogPost' => class_exists(BlogPost::class) ? BlogPost::class : null,
            ]);

            while (($row = fgetcsv($handle)) !== false) {
                try {
                    $shortType = trim($row[$map['seoable_type']] ?? '');
                    $seoableId = (int) ($row[$map['seoable_id']] ?? 0);

                    if (! $shortType || ! $seoableId) {
                        $errors++;

                        continue;
                    }

                    $seoableType = $typeMap[$shortType] ?? null;
                    if (! $seoableType) {
                        $errors++;

                        continue;
                    }

                    $existing = SeoMeta::where('seoable_type', $seoableType)
                        ->where('seoable_id', $seoableId)
                        ->first();

                    if ($existing && ! $updateExisting) {
                        $skipped++;

                        continue;
                    }

                    $data = ['seoable_type' => $seoableType, 'seoable_id' => $seoableId];

                    foreach ($updatableFields as $field) {
                        if (isset($map[$field]) && isset($row[$map[$field]]) && $row[$map[$field]] !== '') {
                            $data[$field] = $row[$map[$field]];
                        }
                    }

                    if ($existing) {
                        $existing->update($data);
                        $updated++;
                    } else {
                        SeoMeta::create($data);
                        $created++;
                    }
                } catch (\Throwable) {
                    $errors++;
                }
            }
        });

        fclose($handle);

        return redirect()->route('settings.seo.metas.index')
            ->with('success', "Importación completada: {$created} creados, {$updated} actualizados, {$skipped} omitidos, {$errors} errores.");
    }

    /**
     * Show hreflang management index grouped by locale.
     */
    public function hreflangIndex(): View
    {
        $metas = SeoMeta::whereNotNull('locale')
            ->where('locale', '!=', '')
            ->orderBy('locale')
            ->get(['id', 'title', 'canonical_url', 'locale', 'seoable_type', 'seoable_id']);

        $grouped = $metas->groupBy('locale');

        $conflicts = [];
        $byPath = $metas->groupBy(fn ($m) => parse_url($m->canonical_url ?? '', PHP_URL_PATH));

        foreach ($byPath as $path => $group) {
            if ($group->count() > 1) {
                $locales = $group->pluck('locale')->unique();
                if ($locales->count() < $group->count()) {
                    $conflicts[] = ['path' => $path, 'count' => $group->count()];
                }
            }
        }

        $withoutLocale = SeoMeta::whereNull('locale')->orWhere('locale', '')->count();

        return view('Seo::settings.metas.hreflang', compact('metas', 'grouped', 'conflicts', 'withoutLocale'));
    }

    /**
     * Translate meta fields using the DeepL SDK.
     */
    public function translateMeta(TranslateSeoMetaRequest $request, SeoMeta $meta): JsonResponse
    {
        $validated = $request->validated();

        $deeplClass = 'DeepL\Translator';
        if (! class_exists($deeplClass)) {
            return response()->json(['error' => 'DeepL SDK no instalado. Ejecuta: composer require deeplcom/deepl-php'], 422);
        }

        $apiKey = config('deepl.api_key') ?? config('services.deepl.key') ?? env('DEEPL_API_KEY', '');
        if (empty($apiKey)) {
            return response()->json(['error' => 'DeepL API key no configurada. Establece DEEPL_API_KEY en .env'], 422);
        }

        $translator = new $deeplClass($apiKey);
        $results = [];

        foreach ($validated['fields'] as $field) {
            $originalText = $meta->{$field} ?? '';
            if (empty($originalText)) {
                continue;
            }

            try {
                $result = $translator->translateText($originalText, null, $validated['target_lang']);
                $results[$field] = $result->text;
            } catch (\Throwable $e) {
                Log::error('SEO meta translation failed', ['error' => $e->getMessage()]);

                return response()->json(['error' => 'Error al traducir. Por favor, inténtalo de nuevo.'], 422);
            }
        }

        return response()->json([
            'translations' => $results,
            'target_lang' => $validated['target_lang'],
            'message' => 'Traducción completada. Revisa y guarda los cambios.',
        ]);
    }

    /**
     * Return the last 10 audit scores for a meta record (sparkline data).
     */
    public function scoreHistory(SeoMeta $meta): JsonResponse
    {
        $history = SeoAuditLog::where('seo_meta_id', $meta->id)
            ->orderBy('audited_at')
            ->limit(10)
            ->get(['score', 'grade', 'audited_at']);

        return response()->json($history->map(fn ($log) => [
            'score' => $log->score,
            'grade' => $log->grade,
            'date' => $log->audited_at?->format('d/m'),
        ]));
    }

    /**
     * Bulk-generate canonical URLs for all metas that lack one.
     */
    public function bulkGenerateCanonicals(Request $request): JsonResponse
    {
        $metas = SeoMeta::where(fn ($q) => $q->whereNull('canonical_url')->orWhere('canonical_url', ''))
            ->with('seoable')
            ->limit(200)
            ->get();

        $generated = 0;
        $failed = 0;

        foreach ($metas as $meta) {
            $seoable = $meta->seoable;
            if (! $seoable) {
                $failed++;

                continue;
            }

            $modelClass = class_basename($seoable);
            $slug = $seoable->slug ?? $seoable->id ?? null;
            $canonical = null;
            $routeAttempts = [
                strtolower($modelClass).'.show',
                'page.show',
                'blog.show',
                'post.show',
            ];

            foreach ($routeAttempts as $routeName) {
                try {
                    $canonical = route($routeName, $slug);
                    break;
                } catch (\Throwable) {
                    // try next
                }
            }

            if (! $canonical && $slug) {
                $prefix = strtolower($modelClass);
                $canonical = url("/{$prefix}/{$slug}");
            }

            if ($canonical) {
                $meta->update(['canonical_url' => $canonical]);
                $generated++;
            } else {
                $failed++;
            }
        }

        return response()->json([
            'generated' => $generated,
            'failed' => $failed,
            'message' => "{$generated} URLs canónicas generadas, {$failed} sin resolver.",
        ]);
    }

    /**
     * Export all SEO data as a JSON backup file.
     */
    public function exportJson(): StreamedResponse
    {
        $filename = 'seo-backup-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(function () {
            echo "{\n";
            echo '  "exported_at": '.json_encode(now()->toIso8601String()).",\n";
            echo "  \"version\": \"1.0\",\n";

            // Metas — chunked to avoid memory bloat
            echo "  \"metas\": [\n";
            $first = true;
            SeoMeta::query()->chunk(500, function ($chunk) use (&$first) {
                foreach ($chunk as $meta) {
                    if (! $first) {
                        echo ",\n";
                    }
                    echo '    '.json_encode($meta->toArray());
                    $first = false;
                }
            });
            echo "\n  ],\n";

            // Redirects
            echo "  \"redirects\": [\n";
            $first = true;
            SeoRedirect::query()->select([
                'source_path', 'target_path', 'status_code', 'is_active',
                'is_regex', 'is_wildcard', 'active_from', 'active_until',
            ])->chunk(500, function ($chunk) use (&$first) {
                foreach ($chunk as $redirect) {
                    if (! $first) {
                        echo ",\n";
                    }
                    echo '    '.json_encode($redirect->toArray());
                    $first = false;
                }
            });
            echo "\n  ],\n";

            // Templates
            echo "  \"templates\": [\n";
            $first = true;
            SeoTemplate::query()->chunk(500, function ($chunk) use (&$first) {
                foreach ($chunk as $template) {
                    if (! $first) {
                        echo ",\n";
                    }
                    echo '    '.json_encode($template->toArray());
                    $first = false;
                }
            });
            echo "\n  ],\n";

            // Static URLs
            echo "  \"static_urls\": [\n";
            $first = true;
            SeoStaticUrl::query()->chunk(500, function ($chunk) use (&$first) {
                foreach ($chunk as $url) {
                    if (! $first) {
                        echo ",\n";
                    }
                    echo '    '.json_encode($url->toArray());
                    $first = false;
                }
            });
            echo "\n  ]\n";

            echo "}\n";
        }, $filename, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Show the JSON import form.
     */
    public function showImportJson(): View
    {
        return view('Seo::settings.metas.import-json');
    }

    /**
     * Process an uploaded JSON backup file and import its data.
     */
    public function importJson(ImportSeoJsonRequest $request): RedirectResponse
    {
        $content = file_get_contents($request->file('json_file')->getPathname());
        $data = json_decode($content, true);

        if (! $data || ! isset($data['version'])) {
            return back()->with('error', 'Archivo JSON inválido o formato no reconocido.');
        }

        $imported = ['redirects' => 0, 'templates' => 0];
        $skipExisting = $request->boolean('skip_existing', true);

        DB::transaction(function () use ($data, $request, &$imported, $skipExisting) {
            if ($request->boolean('import_redirects', true) && ! empty($data['redirects'])) {
                foreach ($data['redirects'] as $row) {
                    if (empty($row['source_path']) || empty($row['target_path'])) {
                        continue;
                    }
                    if ($skipExisting && SeoRedirect::where('source_path', $row['source_path'])->exists()) {
                        continue;
                    }
                    SeoRedirect::updateOrCreate(
                        ['source_path' => $row['source_path']],
                        array_intersect_key($row, array_flip(['target_path', 'status_code', 'is_active', 'is_regex', 'is_wildcard']))
                    );
                    $imported['redirects']++;
                }
            }

            if ($request->boolean('import_templates', true) && ! empty($data['templates'])) {
                foreach ($data['templates'] as $row) {
                    if (empty($row['name'])) {
                        continue;
                    }
                    if ($skipExisting && SeoTemplate::where('name', $row['name'])->exists()) {
                        continue;
                    }
                    SeoTemplate::create(array_intersect_key($row, array_flip(['name', 'model_type', 'title_pattern', 'description_pattern', 'robots', 'is_active', 'priority'])));
                    $imported['templates']++;
                }
            }
        });

        $summary = collect($imported)->map(fn ($c, $k) => "{$c} {$k}")->implode(', ');

        return redirect()->route('settings.seo.metas.index')
            ->with('success', "Importación JSON: {$summary}.");
    }

    /**
     * Return keyword suggestions based on GSC data and stored keywords.
     */
    public function keywordSuggestions(Request $request): JsonResponse
    {
        $keyword = $request->validate(['q' => 'required|string|min:2|max:100'])['q'];

        $related = SeoMeta::where(function ($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
                ->orWhere('target_keyword', 'like', "%{$keyword}%")
                ->orWhere('keywords', 'like', "%{$keyword}%");
        })
            ->whereNotNull('gsc_clicks')
            ->orderByDesc('gsc_clicks')
            ->limit(10)
            ->get(['title', 'target_keyword', 'keywords', 'gsc_clicks', 'gsc_position']);

        $suggestions = collect();

        foreach ($related as $meta) {
            if ($meta->target_keyword && stripos($meta->target_keyword, $keyword) !== false) {
                $suggestions->push([
                    'keyword' => $meta->target_keyword,
                    'source' => 'target_keyword',
                    'clicks' => $meta->gsc_clicks,
                    'position' => $meta->gsc_position,
                ]);
            }

            if ($meta->keywords) {
                foreach (explode(',', $meta->keywords) as $kw) {
                    $kw = trim($kw);
                    if (strlen($kw) > 2 && stripos($kw, $keyword) !== false) {
                        $suggestions->push([
                            'keyword' => $kw,
                            'source' => 'keywords',
                            'clicks' => $meta->gsc_clicks,
                            'position' => $meta->gsc_position,
                        ]);
                    }
                }
            }
        }

        $variations = [
            'cómo '.$keyword,
            $keyword.' online',
            'mejor '.$keyword,
            $keyword.' precio',
            'comprar '.$keyword,
        ];

        return response()->json([
            'suggestions' => $suggestions->unique('keyword')->take(10)->values(),
            'variations' => $variations,
        ]);
    }
}
