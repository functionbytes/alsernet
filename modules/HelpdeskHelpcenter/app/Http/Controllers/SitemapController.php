<?php

namespace Modules\HelpdeskHelpcenter\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;

class SitemapController extends Controller
{
    public const CACHE_KEY = 'helpcenter.sitemap';

    public function index(): Response
    {
        $xml = Cache::remember(self::CACHE_KEY, 3600, function () {
            $articles = HelpCenterArticle::query()
                ->with('translations')
                ->where('is_published', true)
                ->orderByDesc('updated_at')
                ->get();

            return view('helpdeskhelpcenter::public.sitemap', compact('articles'))->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
