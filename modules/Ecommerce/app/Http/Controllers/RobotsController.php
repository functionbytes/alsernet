<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function index(): Response
    {
        $sitemap = url('/sitemap.xml');

        $body = app()->environment('production')
            ? "User-agent: *\nAllow: /\nDisallow: /panel/\nDisallow: /api/\nDisallow: /tienda/cuenta/\nDisallow: /checkout\nDisallow: /carrito\n\nSitemap: {$sitemap}\n"
            : "User-agent: *\nDisallow: /\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
