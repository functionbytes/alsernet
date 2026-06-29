<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Models\SeoStaticUrl;
use Modules\Seo\Services\SitemapCallbackRegistry;
use Modules\Seo\Services\SitemapPriorityCalculator;
use Modules\Sitemap\Builder\SitemapBuilder;
use Modules\Sitemap\Models\SitemapGeneration;

class SitemapAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Seo.settings.view')->only(['index', 'verifyUrls', 'calculatePriorities', 'validateSitemap']);
        $this->middleware('can:Seo.settings.update')->only(['generate', 'clearCache']);
    }

    public function index(): View
    {
        $generalSitemapUrl = \Route::has('sitemap.index') ? route('sitemap.index')
            : (\Route::has('shop.sitemap') ? route('shop.sitemap') : url('/sitemap.xml'));

        $sitemaps = [
            [
                'name' => 'Sitemap general',
                'url' => $generalSitemapUrl,
                'route_name' => 'sitemap.index',
                'description' => 'Todas las URLs del sitio',
                'icon' => 'fas fa-globe',
            ],
            [
                'name' => 'Sitemap de páginas',
                'url' => route('sitemap.pages'),
                'route_name' => 'sitemap.pages',
                'description' => 'Solo páginas publicadas',
                'icon' => 'fas fa-file-alt',
            ],
            [
                'name' => 'Sitemap de posts',
                'url' => route('sitemap.posts'),
                'route_name' => 'sitemap.posts',
                'description' => 'Solo posts publicados',
                'icon' => 'fas fa-newspaper',
            ],
            [
                'name' => 'Índice de sitemaps',
                'url' => route('sitemap.sitemap-index'),
                'route_name' => 'sitemap.sitemap-index',
                'description' => 'Índice con todos los sub-sitemaps',
                'icon' => 'fas fa-list',
            ],
            [
                'name' => 'Sitemap de imágenes',
                'url' => route('sitemap.images'),
                'route_name' => 'sitemap.images',
                'description' => 'Imágenes OG indexadas',
                'icon' => 'fas fa-image',
            ],
            [
                'name' => 'Sitemap de vídeos',
                'url' => route('sitemap.videos'),
                'route_name' => 'sitemap.videos',
                'description' => 'Vídeos para Google Video Search',
                'icon' => 'fas fa-video',
            ],
            [
                'name' => 'Sitemap de noticias',
                'url' => route('sitemap.news'),
                'route_name' => 'sitemap.news',
                'description' => 'Posts publicados en las últimas 48 horas',
                'icon' => 'fas fa-newspaper',
            ],
        ];

        $cacheEnabled = config('sitemap.cache_enabled', true);
        $cacheDuration = config('sitemap.cache_duration', 86400);
        $sitemapPath = public_path('sitemap.xml');
        $fileExists = file_exists($sitemapPath);
        $lastModified = $fileExists ? filemtime($sitemapPath) : null;
        $history = SitemapGeneration::query()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('Seo::settings.sitemap.index', compact(
            'sitemaps',
            'cacheEnabled',
            'cacheDuration',
            'fileExists',
            'lastModified',
            'history'
        ));
    }

    public function generate(SitemapBuilder $sitemap): RedirectResponse
    {
        $start = hrtime(true);

        try {
            $sitemap->clear();
            $sitemap->add(url('/'), null, '1.0', 'daily');

            foreach (config('sitemap.models', []) as $modelClass) {
                if (class_exists($modelClass)) {
                    $sitemap->addModel($modelClass);
                }
            }

            foreach (SeoStaticUrl::active()->get() as $staticUrl) {
                $sitemap->add($staticUrl->url, null, (string) $staticUrl->priority, $staticUrl->changefreq);
            }

            $callbacks = array_merge(config('sitemap.post_callbacks', []), SitemapCallbackRegistry::all());
            foreach ($callbacks as $callback) {
                if (is_callable($callback)) {
                    $callback($sitemap);
                }
            }

            $sitemap->generate();
            SitemapGeneration::create([
                'status' => 'success',
                'url_count' => count($sitemap->getItems()),
                'duration_ms' => (int) round((hrtime(true) - $start) / 1_000_000),
                'source' => 'admin',
            ]);
            Cache::forget('sitemap-xml');

            $sitemapUrl = urlencode(url('/sitemap.xml'));
            foreach (['https://www.google.com/ping?sitemap='.$sitemapUrl, 'https://www.bing.com/ping?sitemap='.$sitemapUrl] as $pingUrl) {
                try {
                    Http::timeout(3)->get($pingUrl);
                } catch (\Exception) {
                    // Ping failure is non-critical
                }
            }

            return redirect()
                ->back()
                ->with('success', __('seo::sitemap.generated_successfully'));
        } catch (\Exception $e) {
            Log::error('Sitemap generation failed', ['error' => $e->getMessage()]);
            SitemapGeneration::create([
                'status' => 'failed',
                'url_count' => 0,
                'duration_ms' => (int) round((hrtime(true) - $start) / 1_000_000),
                'error_message' => $e->getMessage(),
                'source' => 'admin',
            ]);

            return redirect()
                ->back()
                ->with('error', __('seo::sitemap.generation_error').'. Por favor, inténtalo de nuevo.');
        }
    }

    public function verifyUrls(): JsonResponse
    {
        $items = collect();

        SeoStaticUrl::active()->each(function ($url) use (&$items) {
            $items->push(['url' => $url->url, 'source' => 'static']);
        });

        $results = $items->take(50)->map(function ($item) {
            try {
                $response = Http::timeout(5)
                    ->withoutRedirecting()
                    ->head($item['url']);

                return [
                    'url' => $item['url'],
                    'status' => $response->status(),
                    'ok' => $response->successful() || in_array($response->status(), [301, 302]),
                    'source' => $item['source'],
                ];
            } catch (\Exception $e) {
                return [
                    'url' => $item['url'],
                    'status' => 0,
                    'ok' => false,
                    'source' => $item['source'],
                    'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
                ];
            }
        });

        return response()->json([
            'status' => true,
            'results' => $results->values(),
            'summary' => [
                'total' => $results->count(),
                'ok' => $results->where('ok', true)->count(),
                'broken' => $results->where('ok', false)->count(),
            ],
        ]);
    }

    /**
     * Calculate and return sitemap priorities for all metas with canonical URLs.
     */
    public function calculatePriorities(): JsonResponse
    {
        $calculator = new SitemapPriorityCalculator;

        $results = SeoMeta::whereNotNull('canonical_url')
            ->where('canonical_url', '!=', '')
            ->get(['id', 'canonical_url', 'updated_at', 'gsc_clicks'])
            ->map(fn ($meta) => [
                'id' => $meta->id,
                'url' => $meta->canonical_url,
                'priority' => $calculator->calculate(
                    $meta->canonical_url,
                    $meta->updated_at,
                    $meta->gsc_clicks ?? 0
                ),
            ])
            ->sortByDesc('priority')
            ->values();

        return response()->json(['priorities' => $results]);
    }

    public function validateSitemap(): JsonResponse
    {
        try {
            $exitCode = Artisan::call('sitemap:validate', [
                '--check-urls' => true,
                '--sample' => '5',
            ]);
            $output = Artisan::output();

            return response()->json([
                'ok' => $exitCode === 0,
                'output' => trim($output),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'output' => $e->getMessage(),
            ], 500);
        }
    }

    public function clearCache(): RedirectResponse
    {
        try {
            Cache::forget('sitemap-xml');

            return redirect()
                ->back()
                ->with('success', __('seo::sitemap.cache_cleared'));
        } catch (\Exception $e) {
            Log::error('Sitemap cache clear failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->with('error', __('seo::sitemap.cache_clear_error').'. Por favor, inténtalo de nuevo.');
        }
    }
}
