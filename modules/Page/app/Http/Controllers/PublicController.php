<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Page\Models\Page;

class PublicController extends Controller
{
    /**
     * Display the specified page by path (with optional prefix).
     *
     * @return \Illuminate\View\View
     */
    public function show(string $path)
    {
        $prefix = setting('permalink-modules-page-models-page', '');
        $slug = $prefix ? ltrim(Str::replaceFirst($prefix.'/', '', $path), '/') : $path;

        $page = Page::where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Process shortcodes in content if Shortcode module is available
        if (function_exists('shortcode')) {
            $page->content = shortcode($page->content);
        }

        // Try to use active Template module if available
        try {
            $activeTemplate = \Modules\Template\Models\Template::where('status', 'active')->first();
            if ($activeTemplate) {
                // Render using template module layout
                return view('page::public.show-with-template', [
                    'page' => $page,
                    'template' => $activeTemplate,
                ]);
            }
        } catch (\Exception $e) {
            // Log and fallback to default page layout
            \Log::debug('Could not load active template for public page: ' . $e->getMessage());
        }

        // Fallback: Get template or use default
        $template = $page->template ?? 'default';
        $viewPath = "page::public.templates.{$template}";

        // Fallback to default template if custom doesn't exist
        if (! view()->exists($viewPath)) {
            $viewPath = 'page::public.show';
        }

        return view($viewPath, compact('page'));
    }

    /**
     * Display a listing of all published pages.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        $pages = Page::published()
            ->when($search, function ($query, $search) {
                // Use full-text search if available and term is long enough
                if (strlen($search) >= 3) {
                    try {
                        return $query->searchFullText($search);
                    } catch (\Exception $e) {
                        // Fallback to LIKE search
                        return $query->search($search);
                    }
                }

                return $query->search($search);
            })
            ->orderBy('published_at', 'desc')
            ->paginate(config('page.per_page', 20));

        return view('page::public.index', compact('pages', 'search'));
    }
}
