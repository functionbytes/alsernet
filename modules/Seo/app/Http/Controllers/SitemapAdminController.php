<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class SitemapAdminController extends Controller
{
    public function index(): View
    {
        $sitemaps = [
            [
                'name' => 'Sitemap general',
                'url' => route('sitemap.index'),
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
        ];

        $cacheEnabled = config('sitemap.cache_enabled', true);
        $cacheDuration = config('sitemap.cache_duration', 86400);
        $sitemapPath = public_path('sitemap.xml');
        $fileExists = file_exists($sitemapPath);
        $lastModified = $fileExists ? filemtime($sitemapPath) : null;

        return view('Seo::admin.sitemap.index', compact(
            'sitemaps',
            'cacheEnabled',
            'cacheDuration',
            'fileExists',
            'lastModified'
        ));
    }

    public function generate(): RedirectResponse
    {
        try {
            Artisan::call('sitemap:generate');

            return redirect()
                ->back()
                ->with('success', __('seo::sitemap.generated_successfully'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', __('seo::sitemap.generation_error').': '.$e->getMessage());
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
            return redirect()
                ->back()
                ->with('error', __('seo::sitemap.cache_clear_error').': '.$e->getMessage());
        }
    }
}
