<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Page\Models\Page;
use Modules\Seo\Models\SeoRedirect;

class SeoPageUrlsController extends Controller
{
    /**
     * Lista todas las URLs del módulo Page y permite crear redirecciones desde ahí.
     */
    public function index(Request $request): View
    {
        $pages = collect();
        $prefix = '';

        if (class_exists(Page::class)) {
            $prefix = setting('permalink-modules-page-models-page', '');
            $supported = config('page.supported_locales', ['es']);

            $query = Page::with('translations')
                ->withTrashed(false);

            if ($search = $request->get('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('translations', function ($tq) use ($search) {
                            $tq->where('title', 'like', "%{$search}%")
                                ->orWhere('slug', 'like', "%{$search}%");
                        });
                });
            }

            if ($status = $request->get('status')) {
                $query->where('status', $status);
            }

            $rawPages = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

            // Collect all paths first to batch-load redirects (avoids N+1)
            $allPaths = collect();
            foreach ($rawPages as $page) {
                if ($page->translations->isNotEmpty()) {
                    foreach ($page->translations as $trans) {
                        $slug = $trans->slug ?? $page->slug;
                        $allPaths->push('/'.($prefix ? $prefix.'/'.$slug : $slug));
                    }
                } else {
                    $allPaths->push('/'.($prefix ? $prefix.'/'.$page->slug : $page->slug));
                }
            }

            $redirectMap = SeoRedirect::whereIn('source_path', $allPaths->unique()->values())
                ->get()
                ->keyBy('source_path');

            // Transformar para la vista: una fila por URL (por traducción)
            $rows = collect();
            foreach ($rawPages as $page) {
                $hasTranslations = $page->translations->isNotEmpty();

                if ($hasTranslations) {
                    foreach ($page->translations as $trans) {
                        $slug = $trans->slug ?? $page->slug;
                        $fullPath = '/'.($prefix ? $prefix.'/'.$slug : $slug);
                        $existingRedirect = $redirectMap->get($fullPath);

                        $rows->push([
                            'page_id' => $page->id,
                            'page_title' => $page->title,
                            'locale' => $trans->locale,
                            'title' => $trans->title ?? $page->title,
                            'slug' => $slug,
                            'full_path' => $fullPath,
                            'url' => url($prefix ? $prefix.'/'.$slug : $slug),
                            'status' => $trans->status,
                            'page_status' => $page->status,
                            'has_redirect' => (bool) $existingRedirect,
                            'redirect' => $existingRedirect,
                            'seo_noindex' => $trans->seo_noindex ?? false,
                        ]);
                    }
                } else {
                    $slug = $page->slug;
                    $fullPath = '/'.($prefix ? $prefix.'/'.$slug : $slug);
                    $existingRedirect = $redirectMap->get($fullPath);

                    $rows->push([
                        'page_id' => $page->id,
                        'page_title' => $page->title,
                        'locale' => null,
                        'title' => $page->title,
                        'slug' => $slug,
                        'full_path' => $fullPath,
                        'url' => url($prefix ? $prefix.'/'.$slug : $slug),
                        'status' => $page->status,
                        'page_status' => $page->status,
                        'has_redirect' => (bool) $existingRedirect,
                        'redirect' => $existingRedirect,
                        'seo_noindex' => false,
                    ]);
                }
            }

            $pages = $rawPages;
            $pageRows = $rows;
        } else {
            $pages = null;
            $pageRows = collect();
        }

        $stats = [
            'total_pages' => $pages ? $pages->total() : 0,
            'total_redirects' => SeoRedirect::count(),
            'with_redirect' => $pageRows->where('has_redirect', true)->count(),
            'noindex' => $pageRows->where('seo_noindex', true)->count(),
        ];

        return view('Seo::settings.page-urls.index', compact('pageRows', 'pages', 'stats', 'prefix'));
    }

    /**
     * Crear redirección rápida desde la lista de URLs.
     */
    public function quickRedirect(Request $request): RedirectResponse
    {
        $request->validate([
            'source_path' => ['required', 'string'],
            'target_path' => ['required', 'string'],
            'status_code' => ['nullable', 'integer', 'in:301,302,307,308'],
        ]);

        $sourcePath = '/'.ltrim($request->source_path, '/');
        $targetPath = $request->target_path;
        if (! str_starts_with($targetPath, 'http') && ! str_starts_with($targetPath, '/')) {
            $targetPath = '/'.$targetPath;
        }

        SeoRedirect::updateOrCreate(
            ['source_path' => $sourcePath],
            [
                'target_path' => $targetPath,
                'status_code' => $request->get('status_code', 301),
                'is_active' => true,
            ]
        );

        return back()->with('success', "Redirección creada: {$sourcePath} → {$targetPath}");
    }

    /**
     * Perform a bulk action on selected page URLs.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'string', Rule::in(['create_redirect', 'delete_redirect'])],
            'paths' => ['required', 'array', 'min:1'],
            'paths.*' => ['string'],
        ]);

        $action = $request->input('action');
        $paths = $request->input('paths');
        $count = 0;

        if ($action === 'create_redirect') {
            foreach ($paths as $path) {
                $sourcePath = '/'.ltrim($path, '/');

                if (SeoRedirect::where('source_path', $sourcePath)->exists()) {
                    continue;
                }

                SeoRedirect::create([
                    'source_path' => $sourcePath,
                    'target_path' => '/',
                    'status_code' => 301,
                    'is_active' => true,
                ]);

                $count++;
            }
        } elseif ($action === 'delete_redirect') {
            $count = SeoRedirect::whereIn('source_path', $paths)->delete();
        }

        return response()->json(['success' => true, 'count' => $count]);
    }
}
